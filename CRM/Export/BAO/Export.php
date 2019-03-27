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
   * Get default return property for export based on mode
   *
   * @param int $exportMode
   *   Export mode.
   *
   * @return string $property
   *   Default Return property
   */
  public static function defaultReturnProperty($exportMode) {
    // hack to add default return property based on export mode
    $property = NULL;
    if ($exportMode == CRM_Export_Form_Select::CONTRIBUTE_EXPORT) {
      $property = 'contribution_id';
    }
    elseif ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT) {
      $property = 'participant_id';
    }
    elseif ($exportMode == CRM_Export_Form_Select::MEMBER_EXPORT) {
      $property = 'membership_id';
    }
    elseif ($exportMode == CRM_Export_Form_Select::PLEDGE_EXPORT) {
      $property = 'pledge_id';
    }
    elseif ($exportMode == CRM_Export_Form_Select::CASE_EXPORT) {
      $property = 'case_id';
    }
    elseif ($exportMode == CRM_Export_Form_Select::GRANT_EXPORT) {
      $property = 'grant_id';
    }
    elseif ($exportMode == CRM_Export_Form_Select::ACTIVITY_EXPORT) {
      $property = 'activity_id';
    }
    return $property;
  }

  /**
   * Get Export component
   *
   * @param int $exportMode
   *   Export mode.
   *
   * @return string $component
   *   CiviCRM Export Component
   */
  public static function exportComponent($exportMode) {
    switch ($exportMode) {
      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        $component = 'civicrm_contribution';
        break;

      case CRM_Export_Form_Select::EVENT_EXPORT:
        $component = 'civicrm_participant';
        break;

      case CRM_Export_Form_Select::MEMBER_EXPORT:
        $component = 'civicrm_membership';
        break;

      case CRM_Export_Form_Select::PLEDGE_EXPORT:
        $component = 'civicrm_pledge';
        break;

      case CRM_Export_Form_Select::GRANT_EXPORT:
        $component = 'civicrm_grant';
        break;
    }
    return $component;
  }

  /**
   * Get Query Group By Clause
   * @param \CRM_Export_BAO_ExportProcessor $processor
   *   Export Mode
   * @param array $returnProperties
   *   Return Properties
   * @param object $query
   *   CRM_Contact_BAO_Query
   *
   * @return string $groupBy
   *   Group By Clause
   */
  public static function getGroupBy($processor, $returnProperties, $query) {
    $groupBy = NULL;
    $exportMode = $processor->getExportMode();
    $queryMode = $processor->getQueryMode();
    if (!empty($returnProperties['tags']) || !empty($returnProperties['groups']) ||
      CRM_Utils_Array::value('notes', $returnProperties) ||
      // CRM-9552
      ($queryMode & CRM_Contact_BAO_Query::MODE_CONTACTS && $query->_useGroupBy)
    ) {
      $groupBy = "contact_a.id";
    }

    switch ($exportMode) {
      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        $groupBy = 'civicrm_contribution.id';
        if (CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled()) {
          // especial group by  when soft credit columns are included
          $groupBy = ['contribution_search_scredit_combined.id', 'contribution_search_scredit_combined.scredit_id'];
        }
        break;

      case CRM_Export_Form_Select::EVENT_EXPORT:
        $groupBy = 'civicrm_participant.id';
        break;

      case CRM_Export_Form_Select::MEMBER_EXPORT:
        $groupBy = "civicrm_membership.id";
        break;
    }

    if ($queryMode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
      $groupBy = "civicrm_activity.id ";
    }

    return $groupBy ? ' GROUP BY ' . implode(', ', (array) $groupBy) : '';
  }

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
    $exportParams = array(),
    $queryOperator = 'AND'
  ) {

    $isPostalOnly = (
      isset($exportParams['postal_mailing_export']['postal_mailing_export']) &&
      $exportParams['postal_mailing_export']['postal_mailing_export'] == 1
    );

    $processor = new CRM_Export_BAO_ExportProcessor($exportMode, $fields, $queryOperator, $mergeSameHousehold, $isPostalOnly);
    $returnProperties = array();

    if ($fields) {
      foreach ($fields as $key => $value) {
        $fieldName = CRM_Utils_Array::value(1, $value);
        if (!$fieldName || $processor->isHouseholdMergeRelationshipTypeKey($fieldName)) {
          continue;
        }

        if ($processor->isRelationshipTypeKey($fieldName) && (!empty($value[2]) || !empty($value[4]))) {
          $returnProperties[$fieldName] = $processor->setRelationshipReturnProperties($value, $fieldName);
        }
        elseif (is_numeric(CRM_Utils_Array::value(2, $value))) {
          $locationName = CRM_Core_PseudoConstant::getName('CRM_Core_BAO_Address', 'location_type_id', $value[2]);
          if ($fieldName == 'phone') {
            $returnProperties['location'][$locationName]['phone-' . CRM_Utils_Array::value(3, $value)] = 1;
          }
          elseif ($fieldName == 'im') {
            $returnProperties['location'][$locationName]['im-' . CRM_Utils_Array::value(3, $value)] = 1;
          }
          else {
            $returnProperties['location'][$locationName][$fieldName] = 1;
          }
        }
        else {
          //hack to fix component fields
          //revert mix of event_id and title
          if ($fieldName == 'event_id') {
            $returnProperties['event_id'] = 1;
          }
          else {
            $returnProperties[$fieldName] = 1;
          }
        }
      }
      $defaultExportMode = self::defaultReturnProperty($exportMode);
      if ($defaultExportMode) {
        $returnProperties[$defaultExportMode] = 1;
      }
    }
    else {
      $returnProperties = $processor->getDefaultReturnProperties();
    }
    // @todo - we are working towards this being entirely a property of the processor
    $processor->setReturnProperties($returnProperties);
    $paymentTableId = $processor->getPaymentTableID();

    if ($mergeSameAddress) {
      //make sure the addressee fields are selected
      //while using merge same address feature
      $returnProperties['addressee'] = 1;
      $returnProperties['postal_greeting'] = 1;
      $returnProperties['email_greeting'] = 1;
      $returnProperties['street_name'] = 1;
      $returnProperties['household_name'] = 1;
      $returnProperties['street_address'] = 1;
      $returnProperties['city'] = 1;
      $returnProperties['state_province'] = 1;

      // some columns are required for assistance incase they are not already present
      $exportParams['merge_same_address']['temp_columns'] = array();
      $tempColumns = array('id', 'master_id', 'state_province_id', 'postal_greeting_id', 'addressee_id');
      foreach ($tempColumns as $column) {
        if (!array_key_exists($column, $returnProperties)) {
          $returnProperties[$column] = 1;
          $column = $column == 'id' ? 'civicrm_primary_id' : $column;
          $exportParams['merge_same_address']['temp_columns'][$column] = 1;
        }
      }
    }

    if (!$selectAll && $componentTable && !empty($exportParams['additional_group'])) {
      // If an Additional Group is selected, then all contacts in that group are
      // added to the export set (filtering out duplicates).
      $query = "
INSERT INTO {$componentTable} SELECT distinct gc.contact_id FROM civicrm_group_contact gc WHERE gc.group_id = {$exportParams['additional_group']} ON DUPLICATE KEY UPDATE {$componentTable}.contact_id = gc.contact_id";
      CRM_Core_DAO::executeQuery($query);
    }

    if ($moreReturnProperties) {
      // fix for CRM-7066
      if (!empty($moreReturnProperties['group'])) {
        unset($moreReturnProperties['group']);
        $moreReturnProperties['groups'] = 1;
      }
      $returnProperties = array_merge($returnProperties, $moreReturnProperties);
    }

    $exportParams['postal_mailing_export']['temp_columns'] = array();
    if ($exportParams['exportOption'] == 2 &&
      isset($exportParams['postal_mailing_export']) &&
      CRM_Utils_Array::value('postal_mailing_export', $exportParams['postal_mailing_export']) == 1
    ) {
      $postalColumns = array('is_deceased', 'do_not_mail', 'street_address', 'supplemental_address_1');
      foreach ($postalColumns as $column) {
        if (!array_key_exists($column, $returnProperties)) {
          $returnProperties[$column] = 1;
          $exportParams['postal_mailing_export']['temp_columns'][$column] = 1;
        }
      }
    }

    // rectify params to what proximity search expects if there is a value for prox_distance
    // CRM-7021
    if (!empty($params)) {
      CRM_Contact_BAO_ProximityQuery::fixInputParams($params);
    }

    list($query, $select, $from, $where, $having) = $processor->runQuery($params, $order, $returnProperties);

    if ($mergeSameHousehold == 1) {
      if (empty($returnProperties['id'])) {
        $returnProperties['id'] = 1;
      }

      $processor->setHouseholdMergeReturnProperties(array_diff_key($returnProperties, array_fill_keys(['location_type', 'im_provider'], 1)));
    }

    self::buildRelatedContactArray($selectAll, $ids, $processor, $componentTable);

    // make sure the groups stuff is included only if specifically specified
    // by the fields param (CRM-1969), else we limit the contacts outputted to only
    // ones that are part of a group
    if (!empty($returnProperties['groups'])) {
      $oldClause = "( contact_a.id = civicrm_group_contact.contact_id )";
      $newClause = " ( $oldClause AND ( civicrm_group_contact.status = 'Added' OR civicrm_group_contact.status IS NULL ) )";
      // total hack for export, CRM-3618
      $from = str_replace($oldClause,
        $newClause,
        $from
      );
    }

    if (!$selectAll && $componentTable) {
      $from .= " INNER JOIN $componentTable ctTable ON ctTable.contact_id = contact_a.id ";
    }
    elseif ($componentClause) {
      if (empty($where)) {
        $where = "WHERE $componentClause";
      }
      else {
        $where .= " AND $componentClause";
      }
    }

    // CRM-13982 - check if is deleted
    $excludeTrashed = TRUE;
    foreach ($params as $value) {
      if ($value[0] == 'contact_is_deleted') {
        $excludeTrashed = FALSE;
      }
    }
    $trashClause = $excludeTrashed ? "contact_a.is_deleted != 1" : "( 1 )";

    if (empty($where)) {
      $where = "WHERE $trashClause";
    }
    else {
      $where .= " AND $trashClause";
    }

    $queryString = "$select $from $where $having";

    $groupBy = self::getGroupBy($processor, $returnProperties, $query);

    $queryString .= $groupBy;

    if ($order) {
      // always add contact_a.id to the ORDER clause
      // so the order is deterministic
      //CRM-15301
      if (strpos('contact_a.id', $order) === FALSE) {
        $order .= ", contact_a.id";
      }

      list($field, $dir) = explode(' ', $order, 2);
      $field = trim($field);
      if (!empty($returnProperties[$field])) {
        //CRM-15301
        $queryString .= " ORDER BY $order";
      }
    }

    $addPaymentHeader = FALSE;

    list($outputColumns, $metadata) = self::getExportStructureArrays($returnProperties, $processor);

    if (!empty($exportParams['merge_same_address']['temp_columns'])) {
      // @todo - this is a temp fix  - ideally later we don't set stuff only to unset it.
      // test exists covering this...
      foreach (array_keys($exportParams['merge_same_address']['temp_columns']) as $field) {
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

    $componentDetails = array();

    $rowCount = self::EXPORT_ROW_COUNT;
    $offset = 0;
    // we write to temp table often to avoid using too much memory
    $tempRowCount = 100;

    $count = -1;

    $headerRows = $processor->getHeaderRows();
    $sqlColumns = $processor->getSQLColumns();
    $exportTempTable = self::createTempTable($sqlColumns);
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
        $row = $processor->buildRow($query, $iterationDAO, $outputColumns, $metadata, $paymentDetails, $addPaymentHeader, $paymentTableId);
        if ($row === FALSE) {
          continue;
        }

        // add component info
        // write the row to a file
        $componentDetails[] = $row;

        // output every $tempRowCount rows
        if ($count % $tempRowCount == 0) {
          self::writeDetailsToTable($exportTempTable, $componentDetails, $sqlColumns);
          $componentDetails = array();
        }
      }
      if ($rowsThisIteration < self::EXPORT_ROW_COUNT) {
        $limitReached = TRUE;
      }
      $offset += $rowCount;
    }

    if ($exportTempTable) {
      self::writeDetailsToTable($exportTempTable, $componentDetails, $sqlColumns);

      // do merge same address and merge same household processing
      if ($mergeSameAddress) {
        self::mergeSameAddress($exportTempTable, $sqlColumns, $exportParams);
      }

      // call export hook
      CRM_Utils_Hook::export($exportTempTable, $headerRows, $sqlColumns, $exportMode, $componentTable, $ids);

      // In order to be able to write a unit test against this function we need to suppress
      // the csv writing. In future hopefully the csv writing & the main processing will be in separate functions.
      if (empty($exportParams['suppress_csv_for_testing'])) {
        self::writeCSVFromTable($exportTempTable, $headerRows, $sqlColumns, $processor);
      }
      else {
        // return tableName sqlColumns headerRows in test context
        return array($exportTempTable, $sqlColumns, $headerRows, $processor);
      }

      // delete the export temp table and component table
      $sql = "DROP TABLE IF EXISTS {$exportTempTable}";
      CRM_Core_DAO::executeQuery($sql);
      CRM_Core_DAO::reenableFullGroupByMode();
      CRM_Utils_System::civiExit();
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

    $rows = array();
    $dao = CRM_Core_DAO::executeQuery($sql);
    $alterRow = FALSE;
    if (method_exists($search, 'alterRow')) {
      $alterRow = TRUE;
    }
    while ($dao->fetch()) {
      $row = array();

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
   * @param string $tableName
   * @param $details
   * @param $sqlColumns
   */
  public static function writeDetailsToTable($tableName, $details, $sqlColumns) {
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

    $sqlClause = array();

    foreach ($details as $row) {
      $id++;
      $valueString = array($id);
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
    $sqlColumns = array_merge(['id' => 1], $sqlColumns);
    $sqlColumnString = '(' . implode(',', array_keys($sqlColumns)) . ')';

    $sqlValueString = implode(",\n", $sqlClause);

    $sql = "
INSERT INTO $tableName $sqlColumnString
VALUES $sqlValueString
";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * @param $sqlColumns
   *
   * @return string
   */
  public static function createTempTable($sqlColumns) {
    //creating a temporary table for the search result that need be exported
    $exportTempTable = CRM_Utils_SQL_TempTable::build()->setDurable()->setCategory('export')->setUtf8();

    // also create the sql table
    $exportTempTable->drop();

    $sql = " id int unsigned NOT NULL AUTO_INCREMENT, ";
    if (!empty($sqlColumns)) {
      $sql .= implode(",\n", array_values($sqlColumns)) . ',';
    }

    $sql .= "\n PRIMARY KEY ( id )";

    // add indexes for street_address and household_name if present
    $addIndices = array(
      'street_address',
      'household_name',
      'civicrm_primary_id',
    );

    foreach ($addIndices as $index) {
      if (isset($sqlColumns[$index])) {
        $sql .= ",
  INDEX index_{$index}( $index )
";
      }
    }

    $exportTempTable->createWithColumns($sql);
    return $exportTempTable->getName();
  }

  /**
   * @param string $tableName
   * @param $sqlColumns
   * @param array $exportParams
   */
  public static function mergeSameAddress($tableName, &$sqlColumns, $exportParams) {
    // check if any records are present based on if they have used shared address feature,
    // and not based on if city / state .. matches.
    $sql = "
SELECT    r1.id                 as copy_id,
          r1.civicrm_primary_id as copy_contact_id,
          r1.addressee          as copy_addressee,
          r1.addressee_id       as copy_addressee_id,
          r1.postal_greeting    as copy_postal_greeting,
          r1.postal_greeting_id as copy_postal_greeting_id,
          r2.id                 as master_id,
          r2.civicrm_primary_id as master_contact_id,
          r2.postal_greeting    as master_postal_greeting,
          r2.postal_greeting_id as master_postal_greeting_id,
          r2.addressee          as master_addressee,
          r2.addressee_id       as master_addressee_id
FROM      $tableName r1
INNER JOIN civicrm_address adr ON r1.master_id   = adr.id
INNER JOIN $tableName      r2  ON adr.contact_id = r2.civicrm_primary_id
ORDER BY  r1.id";
    $linkedMerge = self::_buildMasterCopyArray($sql, $exportParams, TRUE);

    // find all the records that have the same street address BUT not in a household
    // require match on city and state as well
    $sql = "
SELECT    r1.id                 as master_id,
          r1.civicrm_primary_id as master_contact_id,
          r1.postal_greeting    as master_postal_greeting,
          r1.postal_greeting_id as master_postal_greeting_id,
          r1.addressee          as master_addressee,
          r1.addressee_id       as master_addressee_id,
          r2.id                 as copy_id,
          r2.civicrm_primary_id as copy_contact_id,
          r2.postal_greeting    as copy_postal_greeting,
          r2.postal_greeting_id as copy_postal_greeting_id,
          r2.addressee          as copy_addressee,
          r2.addressee_id       as copy_addressee_id
FROM      $tableName r1
LEFT JOIN $tableName r2 ON ( r1.street_address = r2.street_address AND
               r1.city = r2.city AND
               r1.state_province_id = r2.state_province_id )
WHERE     ( r1.household_name IS NULL OR r1.household_name = '' )
AND       ( r2.household_name IS NULL OR r2.household_name = '' )
AND       ( r1.street_address != '' )
AND       r2.id > r1.id
ORDER BY  r1.id
";
    $merge = self::_buildMasterCopyArray($sql, $exportParams);

    // unset ids from $merge already present in $linkedMerge
    foreach ($linkedMerge as $masterID => $values) {
      $keys = array($masterID);
      $keys = array_merge($keys, array_keys($values['copy']));
      foreach ($merge as $mid => $vals) {
        if (in_array($mid, $keys)) {
          unset($merge[$mid]);
        }
        else {
          foreach ($values['copy'] as $copyId) {
            if (in_array($copyId, $keys)) {
              unset($merge[$mid]['copy'][$copyId]);
            }
          }
        }
      }
    }
    $merge = $merge + $linkedMerge;

    foreach ($merge as $masterID => $values) {
      $sql = "
UPDATE $tableName
SET    addressee = %1, postal_greeting = %2, email_greeting = %3
WHERE  id = %4
";
      $params = array(
        1 => array($values['addressee'], 'String'),
        2 => array($values['postalGreeting'], 'String'),
        3 => array($values['emailGreeting'], 'String'),
        4 => array($masterID, 'Integer'),
      );
      CRM_Core_DAO::executeQuery($sql, $params);

      // delete all copies
      $deleteIDs = array_keys($values['copy']);
      $deleteIDString = implode(',', $deleteIDs);
      $sql = "
DELETE FROM $tableName
WHERE  id IN ( $deleteIDString )
";
      CRM_Core_DAO::executeQuery($sql);
    }

    // unset temporary columns that were added for postal mailing format
    // @todo - this part is pretty close to ready to be removed....
    if (!empty($exportParams['merge_same_address']['temp_columns'])) {
      $unsetKeys = array_keys($sqlColumns);
      foreach ($unsetKeys as $headerKey => $sqlColKey) {
        if (array_key_exists($sqlColKey, $exportParams['merge_same_address']['temp_columns'])) {
          unset($sqlColumns[$sqlColKey]);
        }
      }
    }
  }

  /**
   * @param int $contactId
   * @param array $exportParams
   *
   * @return array
   */
  public static function _replaceMergeTokens($contactId, $exportParams) {
    $greetings = array();
    $contact = NULL;

    $greetingFields = array(
      'postal_greeting',
      'addressee',
    );
    foreach ($greetingFields as $greeting) {
      if (!empty($exportParams[$greeting])) {
        $greetingLabel = $exportParams[$greeting];
        if (empty($contact)) {
          $values = array(
            'id' => $contactId,
            'version' => 3,
          );
          $contact = civicrm_api('contact', 'get', $values);

          if (!empty($contact['is_error'])) {
            return $greetings;
          }
          $contact = $contact['values'][$contact['id']];
        }

        $tokens = array('contact' => $greetingLabel);
        $greetings[$greeting] = CRM_Utils_Token::replaceContactTokens($greetingLabel, $contact, NULL, $tokens);
      }
    }
    return $greetings;
  }

  /**
   * The function unsets static part of the string, if token is the dynamic part.
   *
   * Example: 'Hello {contact.first_name}' => converted to => '{contact.first_name}'
   * i.e 'Hello Alan' => converted to => 'Alan'
   *
   * @param string $parsedString
   * @param string $defaultGreeting
   * @param bool $addressMergeGreetings
   * @param string $greetingType
   *
   * @return mixed
   */
  public static function _trimNonTokens(
    &$parsedString, $defaultGreeting,
    $addressMergeGreetings, $greetingType = 'postal_greeting'
  ) {
    if (!empty($addressMergeGreetings[$greetingType])) {
      $greetingLabel = $addressMergeGreetings[$greetingType];
    }
    $greetingLabel = empty($greetingLabel) ? $defaultGreeting : $greetingLabel;

    $stringsToBeReplaced = preg_replace('/(\{[a-zA-Z._ ]+\})/', ';;', $greetingLabel);
    $stringsToBeReplaced = explode(';;', $stringsToBeReplaced);
    foreach ($stringsToBeReplaced as $key => $string) {
      // to keep one space
      $stringsToBeReplaced[$key] = ltrim($string);
    }
    $parsedString = str_replace($stringsToBeReplaced, "", $parsedString);

    return $parsedString;
  }

  /**
   * @param $sql
   * @param array $exportParams
   * @param bool $sharedAddress
   *
   * @return array
   */
  public static function _buildMasterCopyArray($sql, $exportParams, $sharedAddress = FALSE) {
    static $contactGreetingTokens = array();

    $addresseeOptions = CRM_Core_OptionGroup::values('addressee');
    $postalOptions = CRM_Core_OptionGroup::values('postal_greeting');

    $merge = $parents = array();
    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $masterID = $dao->master_id;
      $copyID = $dao->copy_id;
      $masterPostalGreeting = $dao->master_postal_greeting;
      $masterAddressee = $dao->master_addressee;
      $copyAddressee = $dao->copy_addressee;

      if (!$sharedAddress) {
        if (!isset($contactGreetingTokens[$dao->master_contact_id])) {
          $contactGreetingTokens[$dao->master_contact_id] = self::_replaceMergeTokens($dao->master_contact_id, $exportParams);
        }
        $masterPostalGreeting = CRM_Utils_Array::value('postal_greeting',
          $contactGreetingTokens[$dao->master_contact_id], $dao->master_postal_greeting
        );
        $masterAddressee = CRM_Utils_Array::value('addressee',
          $contactGreetingTokens[$dao->master_contact_id], $dao->master_addressee
        );

        if (!isset($contactGreetingTokens[$dao->copy_contact_id])) {
          $contactGreetingTokens[$dao->copy_contact_id] = self::_replaceMergeTokens($dao->copy_contact_id, $exportParams);
        }
        $copyPostalGreeting = CRM_Utils_Array::value('postal_greeting',
          $contactGreetingTokens[$dao->copy_contact_id], $dao->copy_postal_greeting
        );
        $copyAddressee = CRM_Utils_Array::value('addressee',
          $contactGreetingTokens[$dao->copy_contact_id], $dao->copy_addressee
        );
      }

      if (!isset($merge[$masterID])) {
        // check if this is an intermediate child
        // this happens if there are 3 or more matches a,b, c
        // the above query will return a, b / a, c / b, c
        // we might be doing a bit more work, but for now its ok, unless someone
        // knows how to fix the query above
        if (isset($parents[$masterID])) {
          $masterID = $parents[$masterID];
        }
        else {
          $merge[$masterID] = array(
            'addressee' => $masterAddressee,
            'copy' => array(),
            'postalGreeting' => $masterPostalGreeting,
          );
          $merge[$masterID]['emailGreeting'] = &$merge[$masterID]['postalGreeting'];
        }
      }
      $parents[$copyID] = $masterID;

      if (!$sharedAddress && !array_key_exists($copyID, $merge[$masterID]['copy'])) {

        if (!empty($exportParams['postal_greeting_other']) &&
          count($merge[$masterID]['copy']) >= 1
        ) {
          // use static greetings specified if no of contacts > 2
          $merge[$masterID]['postalGreeting'] = $exportParams['postal_greeting_other'];
        }
        elseif ($copyPostalGreeting) {
          self::_trimNonTokens($copyPostalGreeting,
            $postalOptions[$dao->copy_postal_greeting_id],
            $exportParams
          );
          $merge[$masterID]['postalGreeting'] = "{$merge[$masterID]['postalGreeting']}, {$copyPostalGreeting}";
          // if there happens to be a duplicate, remove it
          $merge[$masterID]['postalGreeting'] = str_replace(" {$copyPostalGreeting},", "", $merge[$masterID]['postalGreeting']);
        }

        if (!empty($exportParams['addressee_other']) &&
          count($merge[$masterID]['copy']) >= 1
        ) {
          // use static greetings specified if no of contacts > 2
          $merge[$masterID]['addressee'] = $exportParams['addressee_other'];
        }
        elseif ($copyAddressee) {
          self::_trimNonTokens($copyAddressee,
            $addresseeOptions[$dao->copy_addressee_id],
            $exportParams, 'addressee'
          );
          $merge[$masterID]['addressee'] = "{$merge[$masterID]['addressee']}, " . trim($copyAddressee);
        }
      }
      $merge[$masterID]['copy'][$copyID] = $copyAddressee;
    }

    return $merge;
  }

  /**
   * @param $exportTempTable
   * @param $headerRows
   * @param $sqlColumns
   * @param \CRM_Export_BAO_ExportProcessor $processor
   */
  public static function writeCSVFromTable($exportTempTable, $headerRows, $sqlColumns, $processor) {
    $exportMode = $processor->getExportMode();
    $writeHeader = TRUE;
    $offset = 0;
    $limit = self::EXPORT_ROW_COUNT;

    $query = "SELECT * FROM $exportTempTable";

    while (1) {
      $limitQuery = $query . "
LIMIT $offset, $limit
";
      $dao = CRM_Core_DAO::executeQuery($limitQuery);

      if ($dao->N <= 0) {
        break;
      }

      $componentDetails = array();
      while ($dao->fetch()) {
        $row = array();

        foreach ($sqlColumns as $column => $dontCare) {
          $row[$column] = $dao->$column;
        }
        $componentDetails[] = $row;
      }
      CRM_Core_Report_Excel::writeCSVFile($processor->getExportFileName(),
        $headerRows,
        $componentDetails,
        NULL,
        $writeHeader
      );

      $writeHeader = FALSE;
      $offset += $limit;
    }
  }

  /**
   * Build componentPayment fields.
   *
   * This is no longer used by export but BAO_Mapping still calls it & we
   * should find a generic way to handle this or move this to that class.
   *
   * @deprecated
   */
  public static function componentPaymentFields() {
    static $componentPaymentFields;
    if (!isset($componentPaymentFields)) {
      $componentPaymentFields = array(
        'componentPaymentField_total_amount' => ts('Total Amount'),
        'componentPaymentField_contribution_status' => ts('Contribution Status'),
        'componentPaymentField_received_date' => ts('Date Received'),
        'componentPaymentField_payment_instrument' => ts('Payment Method'),
        'componentPaymentField_transaction_id' => ts('Transaction ID'),
      );
    }
    return $componentPaymentFields;
  }

  /**
   * Get the various arrays that we use to structure our output.
   *
   * The extraction of these has been moved to a separate function for clarity and so that
   * tests can be added - in particular on the $outputHeaders array.
   *
   * However it still feels a bit like something that I'm too polite to write down and this should be seen
   * as a step on the refactoring path rather than how it should be.
   *
   * @param array $returnProperties
   * @param \CRM_Export_BAO_ExportProcessor $processor
   *
   * @return array
   *   - outputColumns Array of columns to be exported. The values don't matter but the key must match the
   *   alias for the field generated by BAO_Query object.
   *   - headerRows Array of the column header strings to put in the csv header - non-associative.
   *   - sqlColumns Array of column names for the temp table. Not too sure why outputColumns can't be used here.
   *   - metadata Array of fields with specific parameters to pass to the translate function or another hacky nasty solution
   *    I'm too embarassed to discuss here.
   *    The keys need
   *    - to match the outputColumns keys (yes, the fact we ignore the output columns values & then pass another array with values
   *    we could use does suggest further refactors. However, you future improver, do remember that every check you do
   *    in the main DAO loop is done once per row & that coule be 100,000 times.)
   *    Finally a pop quiz: We need the translate context because we use a function other than ts() - is this because
   *    - a) the function used is more efficient or
   *    - b) this code is old & outdated. Submit your answers to circular bin or better
   *       yet find a way to comment them for posterity.
   */
  public static function getExportStructureArrays($returnProperties, $processor) {
    $outputColumns = $metadata = array();
    $queryFields = $processor->getQueryFields();
    foreach ($returnProperties as $key => $value) {
      if (($key != 'location' || !is_array($value)) && !$processor->isRelationshipTypeKey($key)) {
        $outputColumns[$key] = $value;
        $processor->addOutputSpecification($key);
      }
      elseif ($processor->isRelationshipTypeKey($key)) {
        $outputColumns[$key] = $value;
        foreach ($value as $relationField => $relationValue) {
          // below block is same as primary block (duplicate)
          if (isset($queryFields[$relationField]['title'])) {
            $processor->addOutputSpecification($relationField, $key);
          }
          elseif (is_array($relationValue) && $relationField == 'location') {
            // fix header for location type case
            foreach ($relationValue as $ltype => $val) {
              foreach (array_keys($val) as $fld) {
                $type = explode('-', $fld);
                $processor->addOutputSpecification($type[0], $key, $ltype, CRM_Utils_Array::value(1, $type));
              }
            }
          }
        }
      }
      else {
        foreach ($value as $locationType => $locationFields) {
          foreach (array_keys($locationFields) as $locationFieldName) {
            $type = explode('-', $locationFieldName);

            $actualDBFieldName = $type[0];
            $daoFieldName = CRM_Utils_String::munge($locationType) . '-' . $actualDBFieldName;

            if (!empty($type[1])) {
              $daoFieldName .= "-" . $type[1];
            }
            $processor->addOutputSpecification($actualDBFieldName, NULL, $locationType, CRM_Utils_Array::value(1, $type));
            $metadata[$daoFieldName] = $processor->getMetaDataForField($actualDBFieldName);
            $outputColumns[$daoFieldName] = TRUE;
          }
        }
      }
    }
    return array($outputColumns, $metadata);
  }

  /**
   * Get the values of linked household contact.
   *
   * @param CRM_Core_DAO $relDAO
   * @param array $value
   * @param string $field
   * @param array $row
   */
  private static function fetchRelationshipDetails($relDAO, $value, $field, &$row) {
    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    $i18n = CRM_Core_I18n::singleton();
    $field = $field . '_';

    foreach ($value as $relationField => $relationValue) {
      if (is_object($relDAO) && property_exists($relDAO, $relationField)) {
        $fieldValue = $relDAO->$relationField;
        if ($relationField == 'phone_type_id') {
          $fieldValue = $phoneTypes[$relationValue];
        }
        elseif ($relationField == 'provider_id') {
          $fieldValue = CRM_Utils_Array::value($relationValue, $imProviders);
        }
        // CRM-13995
        elseif (is_object($relDAO) && in_array($relationField, array(
            'email_greeting',
            'postal_greeting',
            'addressee',
          ))
        ) {
          //special case for greeting replacement
          $fldValue = "{$relationField}_display";
          $fieldValue = $relDAO->$fldValue;
        }
      }
      elseif (is_object($relDAO) && $relationField == 'state_province') {
        $fieldValue = CRM_Core_PseudoConstant::stateProvince($relDAO->state_province_id);
      }
      elseif (is_object($relDAO) && $relationField == 'country') {
        $fieldValue = CRM_Core_PseudoConstant::country($relDAO->country_id);
      }
      else {
        $fieldValue = '';
      }
      $relPrefix = $field . $relationField;

      if (is_object($relDAO) && $relationField == 'id') {
        $row[$relPrefix] = $relDAO->contact_id;
      }
      elseif (is_array($relationValue) && $relationField == 'location') {
        foreach ($relationValue as $ltype => $val) {
          // If the location name has a space in it the we need to handle that. This
          // is kinda hacky but specifically covered in the ExportTest so later efforts to
          // improve it should be secure in the knowled it will be caught.
          $ltype = str_replace(' ', '_', $ltype);
          foreach (array_keys($val) as $fld) {
            $type = explode('-', $fld);
            $fldValue = "{$ltype}-" . $type[0];
            if (!empty($type[1])) {
              $fldValue .= "-" . $type[1];
            }
            // CRM-3157: localise country, region (both have ‘country’ context)
            // and state_province (‘province’ context)
            switch (TRUE) {
              case (!is_object($relDAO)):
                $row[$field . '_' . $fldValue] = '';
                break;

              case in_array('country', $type):
              case in_array('world_region', $type):
                $row[$field . '_' . $fldValue] = $i18n->crm_translate($relDAO->$fldValue,
                  array('context' => 'country')
                );
                break;

              case in_array('state_province', $type):
                $row[$field . '_' . $fldValue] = $i18n->crm_translate($relDAO->$fldValue,
                  array('context' => 'province')
                );
                break;

              default:
                $row[$field . '_' . $fldValue] = $relDAO->$fldValue;
                break;
            }
          }
        }
      }
      elseif (isset($fieldValue) && $fieldValue != '') {
        //check for custom data
        if ($cfID = CRM_Core_BAO_CustomField::getKeyID($relationField)) {
          $row[$relPrefix] = CRM_Core_BAO_CustomField::displayValue($fieldValue, $cfID);
        }
        else {
          //normal relationship fields
          // CRM-3157: localise country, region (both have ‘country’ context) and state_province (‘province’ context)
          switch ($relationField) {
            case 'country':
            case 'world_region':
              $row[$relPrefix] = $i18n->crm_translate($fieldValue, array('context' => 'country'));
              break;

            case 'state_province':
              $row[$relPrefix] = $i18n->crm_translate($fieldValue, array('context' => 'province'));
              break;

            default:
              $row[$relPrefix] = $fieldValue;
              break;
          }
        }
      }
      else {
        // if relation field is empty or null
        $row[$relPrefix] = '';
      }
    }
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
    $component = self::exportComponent($exportMode);

    if ($exportMode == CRM_Export_Form_Select::CASE_EXPORT) {
      return CRM_Case_BAO_Case::retrieveContactIdsByCaseId($ids);
    }
    else {
      return CRM_Core_DAO::getContactIDsFromComponent($ids, $component);
    }
  }

  /**
   * @param $selectAll
   * @param $ids
   * @param \CRM_Export_BAO_ExportProcessor $processor
   * @param $componentTable
   */
  protected static function buildRelatedContactArray($selectAll, $ids, $processor, $componentTable) {
    $allRelContactArray = $relationQuery = array();
    $queryMode = $processor->getQueryMode();
    $exportMode = $processor->getExportMode();

    foreach ($processor->getRelationshipReturnProperties() as $relationshipKey => $relationReturnProperties) {
      $allRelContactArray[$relationshipKey] = array();
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
        self::fetchRelationshipDetails($allRelContactDAO, $relationReturnProperties, $relationshipKey, $row);
        foreach (array_keys($relationReturnProperties) as $property) {
          if ($property === 'location') {
            // @todo - simplify location in self::fetchRelationshipDetails - remove handling here. Or just call
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
