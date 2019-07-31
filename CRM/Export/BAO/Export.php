<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class contains the functions for Component export
 *
 */
class CRM_Export_BAO_Export {
  // increase this number a lot to avoid making too many queries
  // LIMIT is not much faster than a no LIMIT query
  // CRM-7675
  const EXPORT_ROW_COUNT = 100000;

  /**
   * Get the list the export fields.
   *
   * @param int $selectAll
   *   User preference while export.
   * @param array $ids
   *   Contact ids.
   * @param array $params
   *   Associated array of fields.
   * @param string $order
   *   Order by clause.
   * @param array $fields
   *   Associated array of fields.
   * @param array $moreReturnProperties
   *   Additional return fields.
   * @param int $exportMode
   *   Export mode.
   * @param string $componentClause
   *   Component clause.
   * @param string $componentTable
   *   Component table.
   * @param bool $mergeSameAddress
   *   Merge records if they have same address.
   * @param bool $mergeSameHousehold
   *   Merge records if they belong to the same household.
   *
   * @param array $exportParams
   * @param string $queryOperator
   *
   * @return array|null
   *   An array can be requested from within a unit test.
   *
   * @throws \CRM_Core_Exception
   */
  public static function exportComponents(
    $selectAll,
    $ids,
    $params,
    $order = NULL,
    $fields = NULL,
    $moreReturnProperties = NULL,
    $exportMode = CRM_Export_Form_Select::CONTACT_EXPORT,
    $componentClause = NULL,
    $componentTable = NULL,
    $mergeSameAddress = FALSE,
    $mergeSameHousehold = FALSE,
    $exportParams = [],
    $queryOperator = 'AND'
  ) {

    $isPostalOnly = (
      isset($exportParams['postal_mailing_export']['postal_mailing_export']) &&
      $exportParams['postal_mailing_export']['postal_mailing_export'] == 1
    );

    if (!$selectAll && $componentTable && !empty($exportParams['additional_group'])) {
      // If an Additional Group is selected, then all contacts in that group are
      // added to the export set (filtering out duplicates).
      // Really - the calling function could do this ... just saying
      // @todo take a whip to the calling function.
      CRM_Core_DAO::executeQuery("
INSERT INTO {$componentTable} SELECT distinct gc.contact_id FROM civicrm_group_contact gc WHERE gc.group_id = {$exportParams['additional_group']} ON DUPLICATE KEY UPDATE {$componentTable}.contact_id = gc.contact_id"
      );
    }
    // rectify params to what proximity search expects if there is a value for prox_distance
    // CRM-7021
    // @todo - move this back to the calling functions
    if (!empty($params)) {
      CRM_Contact_BAO_ProximityQuery::fixInputParams($params);
    }
    // @todo everything from this line up should go back to the calling functions.
    $processor = new CRM_Export_BAO_ExportProcessor($exportMode, $fields, $queryOperator, $mergeSameHousehold, $isPostalOnly, $mergeSameAddress, $exportParams);
    if ($moreReturnProperties) {
      $processor->setAdditionalRequestedReturnProperties($moreReturnProperties);
    }
    $processor->setComponentTable($componentTable);
    $processor->setComponentClause($componentClause);

    list($query, $queryString) = $processor->runQuery($params, $order);

    // This perhaps only needs calling when $mergeSameHousehold == 1
    self::buildRelatedContactArray($selectAll, $ids, $processor, $componentTable);

    $addPaymentHeader = FALSE;

    list($outputColumns, $metadata) = $processor->getExportStructureArrays();

    if ($processor->isMergeSameAddress()) {
      foreach (array_keys($processor->getAdditionalFieldsForSameAddressMerge()) as $field) {
        $processor->setColumnAsCalculationOnly($field);
      }
    }

    $paymentDetails = [];
    if ($processor->isExportPaymentFields()) {
      // get payment related in for event and members
      $paymentDetails = CRM_Contribute_BAO_Contribution::getContributionDetails($exportMode, $ids);
      //get all payment headers.
      // If we haven't selected specific payment fields, load in all the
      // payment headers.
      if (!$processor->isExportSpecifiedPaymentFields()) {
        if (!empty($paymentDetails)) {
          $addPaymentHeader = TRUE;
          foreach (array_keys($processor->getPaymentHeaders()) as $paymentField) {
            $processor->addOutputSpecification($paymentField);
          }
        }
      }
    }

    $componentDetails = [];

    $rowCount = self::EXPORT_ROW_COUNT;
    $offset = 0;
    // we write to temp table often to avoid using too much memory
    $tempRowCount = 100;

    $count = -1;

    $sqlColumns = $processor->getSQLColumns();
    $processor->createTempTable();
    $limitReached = FALSE;

    while (!$limitReached) {
      $limitQuery = "{$queryString} LIMIT {$offset}, {$rowCount}";
      CRM_Core_DAO::disableFullGroupByMode();
      $iterationDAO = CRM_Core_DAO::executeQuery($limitQuery);
      CRM_Core_DAO::reenableFullGroupByMode();
      // If this is less than our limit by the end of the iteration we do not need to run the query again to
      // check if some remain.
      $rowsThisIteration = 0;

      while ($iterationDAO->fetch()) {
        $count++;
        $rowsThisIteration++;
        $row = $processor->buildRow($query, $iterationDAO, $outputColumns, $metadata, $paymentDetails, $addPaymentHeader);
        if ($row === FALSE) {
          continue;
        }

        // add component info
        // write the row to a file
        $componentDetails[] = $row;

        // output every $tempRowCount rows
        if ($count % $tempRowCount == 0) {
          self::writeDetailsToTable($processor, $componentDetails, $sqlColumns);
          $componentDetails = [];
        }
      }
      if ($rowsThisIteration < self::EXPORT_ROW_COUNT) {
        $limitReached = TRUE;
      }
      $offset += $rowCount;
    }

    if ($processor->getTemporaryTable()) {
      self::writeDetailsToTable($processor, $componentDetails);

      // do merge same address and merge same household processing
      if ($mergeSameAddress) {
        $processor->mergeSameAddress();
      }

      // In order to be able to write a unit test against this function we need to suppress
      // the csv writing. In future hopefully the csv writing & the main processing will be in separate functions.
      if (empty($exportParams['suppress_csv_for_testing'])) {
        $processor->writeCSVFromTable();
      }
      else {
        // return tableName sqlColumns headerRows in test context
        return [$processor->getTemporaryTable(), $sqlColumns, $processor->getHeaderRows(), $processor];
      }

      // delete the export temp table and component table
      $sql = "DROP TABLE IF EXISTS " . $processor->getTemporaryTable();
      CRM_Core_DAO::executeQuery($sql);
      CRM_Core_DAO::reenableFullGroupByMode();
      CRM_Utils_System::civiExit(0, ['processor' => $processor]);
    }
    else {
      CRM_Core_DAO::reenableFullGroupByMode();
      throw new CRM_Core_Exception(ts('No records to export'));
    }
  }

  /**
   * Handle import error file creation.
   */
  public static function invoke() {
    $type = CRM_Utils_Request::retrieve('type', 'Positive');
    $parserName = CRM_Utils_Request::retrieve('parser', 'String');
    if (empty($parserName) || empty($type)) {
      return;
    }

    // clean and ensure parserName is a valid string
    $parserName = CRM_Utils_String::munge($parserName);
    $parserClass = explode('_', $parserName);

    // make sure parserClass is in the CRM namespace and
    // at least 3 levels deep
    if ($parserClass[0] == 'CRM' &&
      count($parserClass) >= 3
    ) {
      require_once str_replace('_', DIRECTORY_SEPARATOR, $parserName) . ".php";
      // ensure the functions exists
      if (method_exists($parserName, 'errorFileName') &&
        method_exists($parserName, 'saveFileName')
      ) {
        $errorFileName = $parserName::errorFileName($type);
        $saveFileName = $parserName::saveFileName($type);
        if (!empty($errorFileName) && !empty($saveFileName)) {
          CRM_Utils_System::setHttpHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
          CRM_Utils_System::setHttpHeader('Content-Description', 'File Transfer');
          CRM_Utils_System::setHttpHeader('Content-Type', 'text/csv');
          CRM_Utils_System::setHttpHeader('Content-Length', filesize($errorFileName));
          CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . $saveFileName);

          readfile($errorFileName);
        }
      }
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * @param $customSearchClass
   * @param $formValues
   * @param $order
   */
  public static function exportCustom($customSearchClass, $formValues, $order) {
    $ext = CRM_Extension_System::singleton()->getMapper();
    if (!$ext->isExtensionClass($customSearchClass)) {
      require_once str_replace('_', DIRECTORY_SEPARATOR, $customSearchClass) . '.php';
    }
    else {
      require_once $ext->classToPath($customSearchClass);
    }
    $search = new $customSearchClass($formValues);

    $includeContactIDs = FALSE;
    if ($formValues['radio_ts'] == 'ts_sel') {
      $includeContactIDs = TRUE;
    }

    $sql = $search->all(0, 0, $order, $includeContactIDs);

    $columns = $search->columns();

    $header = array_keys($columns);
    $fields = array_values($columns);

    $rows = [];
    $dao = CRM_Core_DAO::executeQuery($sql);
    $alterRow = FALSE;
    if (method_exists($search, 'alterRow')) {
      $alterRow = TRUE;
    }
    while ($dao->fetch()) {
      $row = [];

      foreach ($fields as $field) {
        $unqualified_field = CRM_Utils_Array::First(array_slice(explode('.', $field), -1));
        $row[$field] = $dao->$unqualified_field;
      }
      if ($alterRow) {
        $search->alterRow($row);
      }
      $rows[] = $row;
    }

    CRM_Core_Report_Excel::writeCSVFile(ts('CiviCRM Contact Search'), $header, $rows);
    CRM_Utils_System::civiExit();
  }

  /**
   * @param \CRM_Export_BAO_ExportProcessor $processor
   * @param $details
   */
  public static function writeDetailsToTable($processor, $details) {
    $tableName = $processor->getTemporaryTable();
    if (empty($details)) {
      return;
    }

    $sql = "
SELECT max(id)
FROM   $tableName
";

    $id = CRM_Core_DAO::singleValueQuery($sql);
    if (!$id) {
      $id = 0;
    }

    $sqlClause = [];

    foreach ($details as $row) {
      $id++;
      $valueString = [$id];
      foreach ($row as $value) {
        if (empty($value)) {
          $valueString[] = "''";
        }
        else {
          $valueString[] = "'" . CRM_Core_DAO::escapeString($value) . "'";
        }
      }
      $sqlClause[] = '(' . implode(',', $valueString) . ')';
    }
    $sqlColumns = array_merge(['id' => 1], $processor->getSQLColumns());
    $sqlColumnString = '(' . implode(',', array_keys($sqlColumns)) . ')';

    $sqlValueString = implode(",\n", $sqlClause);

    $sql = "
INSERT INTO $tableName $sqlColumnString
VALUES $sqlValueString
";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Get the ids that we want to get related contact details for.
   *
   * @param array $ids
   * @param int $exportMode
   *
   * @return array
   */
  protected static function getIDsForRelatedContact($ids, $exportMode) {
    if ($exportMode == CRM_Export_Form_Select::CONTACT_EXPORT) {
      return $ids;
    }
    if ($exportMode == CRM_Export_Form_Select::ACTIVITY_EXPORT) {
      $relIDs = [];
      $sourceID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Source');
      $dao = CRM_Core_DAO::executeQuery("
            SELECT contact_id FROM civicrm_activity_contact
            WHERE activity_id IN ( " . implode(',', $ids) . ") AND
            record_type_id = {$sourceID}
          ");

      while ($dao->fetch()) {
        $relIDs[] = $dao->contact_id;
      }
      return $relIDs;
    }
    $componentMapping = [
      CRM_Export_Form_Select::CONTRIBUTE_EXPORT => 'civicrm_contribution',
      CRM_Export_Form_Select::EVENT_EXPORT => 'civicrm_participant',
      CRM_Export_Form_Select::MEMBER_EXPORT => 'civicrm_membership',
      CRM_Export_Form_Select::PLEDGE_EXPORT => 'civicrm_pledge',
      CRM_Export_Form_Select::GRANT_EXPORT => 'civicrm_grant',
    ];

    if ($exportMode == CRM_Export_Form_Select::CASE_EXPORT) {
      return CRM_Case_BAO_Case::retrieveContactIdsByCaseId($ids);
    }
    else {
      return CRM_Core_DAO::getContactIDsFromComponent($ids, $componentMapping[$exportMode]);
    }
  }

  /**
   * @param $selectAll
   * @param $ids
   * @param \CRM_Export_BAO_ExportProcessor $processor
   * @param $componentTable
   */
  protected static function buildRelatedContactArray($selectAll, $ids, $processor, $componentTable) {
    $allRelContactArray = $relationQuery = [];
    $queryMode = $processor->getQueryMode();
    $exportMode = $processor->getExportMode();

    foreach ($processor->getRelationshipReturnProperties() as $relationshipKey => $relationReturnProperties) {
      $allRelContactArray[$relationshipKey] = [];
      // build Query for each relationship
      $relationQuery = new CRM_Contact_BAO_Query(NULL, $relationReturnProperties,
        NULL, FALSE, FALSE, $queryMode
      );
      list($relationSelect, $relationFrom, $relationWhere, $relationHaving) = $relationQuery->query();

      list($id, $direction) = explode('_', $relationshipKey, 2);
      // identify the relationship direction
      $contactA = 'contact_id_a';
      $contactB = 'contact_id_b';
      if ($direction == 'b_a') {
        $contactA = 'contact_id_b';
        $contactB = 'contact_id_a';
      }
      $relIDs = self::getIDsForRelatedContact($ids, $exportMode);

      $relationshipJoin = $relationshipClause = '';
      if (!$selectAll && $componentTable) {
        $relationshipJoin = " INNER JOIN {$componentTable} ctTable ON ctTable.contact_id = {$contactA}";
      }
      elseif (!empty($relIDs)) {
        $relID = implode(',', $relIDs);
        $relationshipClause = " AND crel.{$contactA} IN ( {$relID} )";
      }

      $relationFrom = " {$relationFrom}
              INNER JOIN civicrm_relationship crel ON crel.{$contactB} = contact_a.id AND crel.relationship_type_id = {$id}
              {$relationshipJoin} ";

      //check for active relationship status only
      $today = date('Ymd');
      $relationActive = " AND (crel.is_active = 1 AND ( crel.end_date is NULL OR crel.end_date >= {$today} ) )";
      $relationWhere = " WHERE contact_a.is_deleted = 0 {$relationshipClause} {$relationActive}";
      CRM_Core_DAO::disableFullGroupByMode();
      $relationSelect = "{$relationSelect}, {$contactA} as refContact ";
      $relationQueryString = "$relationSelect $relationFrom $relationWhere $relationHaving GROUP BY crel.{$contactA}";

      $allRelContactDAO = CRM_Core_DAO::executeQuery($relationQueryString);
      CRM_Core_DAO::reenableFullGroupByMode();

      while ($allRelContactDAO->fetch()) {
        $relationQuery->convertToPseudoNames($allRelContactDAO);
        $row = [];
        // @todo pass processor to fetchRelationshipDetails and set fields directly within it.
        $processor->fetchRelationshipDetails($allRelContactDAO, $relationReturnProperties, $relationshipKey, $row);
        foreach (array_keys($relationReturnProperties) as $property) {
          if ($property === 'location') {
            // @todo - simplify location in fetchRelationshipDetails - remove handling here. Or just call
            // $processor->setRelationshipValue from fetchRelationshipDetails
            foreach ($relationReturnProperties['location'] as $locationName => $locationValues) {
              foreach (array_keys($locationValues) as $locationValue) {
                $key = str_replace(' ', '_', $locationName) . '-' . $locationValue;
                $processor->setRelationshipValue($relationshipKey, $allRelContactDAO->refContact, $key, $row[$relationshipKey . '__' . $key]);
              }
            }
          }
          else {
            $processor->setRelationshipValue($relationshipKey, $allRelContactDAO->refContact, $property, $row[$relationshipKey . '_' . $property]);
          }
        }
      }
    }
  }

}
