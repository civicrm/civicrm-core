<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class contains the funtions for Component export
 *
 */
class CRM_Export_BAO_Export {
  // increase this number a lot to avoid making too many queries
  // LIMIT is not much faster than a no LIMIT query
  // CRM-7675
  CONST EXPORT_ROW_COUNT = 10000;

  /**
   * Function to get the list the export fields
   *
   * @param int    $selectAll user preference while export
   * @param array  $ids  contact ids
   * @param array  $params associated array of fields
   * @param string $order order by clause
   * @param array  $fields associated array of fields
   * @param array  $moreReturnProperties additional return fields
   * @param int    $exportMode export mode
   * @param string $componentClause component clause
   * @param string $componentTable component table
   * @param bool   $mergeSameAddress merge records if they have same address
   * @param bool   $mergeSameHousehold merge records if they belong to the same household
   *
   * @static
   * @access public
   */
  static function exportComponents($selectAll,
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
    $headerRows = $returnProperties = array();
    $primary    = $paymentFields    = $selectedPaymentFields = FALSE;
    $origFields = $fields;
    $queryMode  = $relationField = NULL;

    $allCampaigns = array();
    $exportCampaign = FALSE;

    $phoneTypes = CRM_Core_PseudoConstant::phoneType();
    $imProviders = CRM_Core_PseudoConstant::IMProvider();
    $contactRelationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(
      NULL,
      NULL,
      NULL,
      NULL,
      TRUE,
      'name',
      FALSE
    );
    $queryMode = CRM_Contact_BAO_Query::MODE_CONTACTS;

    switch ($exportMode) {
      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
        break;

      case CRM_Export_Form_Select::EVENT_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_EVENT;
        break;

      case CRM_Export_Form_Select::MEMBER_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_MEMBER;
        break;

      case CRM_Export_Form_Select::PLEDGE_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_PLEDGE;
        break;

      case CRM_Export_Form_Select::CASE_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_CASE;
        break;

      case CRM_Export_Form_Select::GRANT_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_GRANT;
        break;

      case CRM_Export_Form_Select::ACTIVITY_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_ACTIVITY;
        break;
    }
    if ($fields) {
      //construct return properties
      $locationTypes = CRM_Core_PseudoConstant::locationType();
      $locationTypeFields = array(
        'street_address',
        'supplemental_address_1',
        'supplemental_address_2',
        'city',
        'postal_code',
        'postal_code_suffix',
        'geo_code_1',
        'geo_code_2',
        'state_province',
        'country',
        'phone',
        'email',
        'im',
      );

      foreach ($fields as $key => $value) {
        $phoneTypeId = $imProviderId = NULL;
        $relationshipTypes = $fieldName = CRM_Utils_Array::value(1, $value);
        if (!$fieldName) {
          continue;
        }
        // get phoneType id and IM service provider id seperately
        if ($fieldName == 'phone') {
          $phoneTypeId = CRM_Utils_Array::value(3, $value);
        }
        elseif ($fieldName == 'im') {
          $imProviderId = CRM_Utils_Array::value(3, $value);
        }
        elseif (substr($fieldName, -8) == 'campaign') {
          $exportCampaign = TRUE;
        }

        if (array_key_exists($relationshipTypes, $contactRelationshipTypes)) {
          if (CRM_Utils_Array::value(2, $value)) {
            $relationField = CRM_Utils_Array::value(2, $value);
            if (trim(CRM_Utils_Array::value(3, $value))) {
              $relLocTypeId = CRM_Utils_Array::value(3, $value);
            }
            else {
              $relLocTypeId = 'Primary';
            }

            if ($relationField == 'phone') {
              $relPhoneTypeId = CRM_Utils_Array::value(4, $value);
            }
            elseif ($relationField == 'im') {
              $relIMProviderId = CRM_Utils_Array::value(4, $value);
            }
          }
          elseif (CRM_Utils_Array::value(4, $value)) {
            $relationField = CRM_Utils_Array::value(4, $value);
            $relLocTypeId = CRM_Utils_Array::value(5, $value);
            if ($relationField == 'phone') {
              $relPhoneTypeId = CRM_Utils_Array::value(6, $value);
            }
            elseif ($relationField == 'im') {
              $relIMProviderId = CRM_Utils_Array::value(6, $value);
            }
          }
        }

        $contactType = CRM_Utils_Array::value(0, $value);
        $locTypeId = CRM_Utils_Array::value(2, $value);

        if ($relationField) {
          if (in_array($relationField, $locationTypeFields) && is_numeric($relLocTypeId)) {
            if ($relPhoneTypeId) {
              $returnProperties[$relationshipTypes]['location'][$locationTypes[$relLocTypeId]]['phone-' . $relPhoneTypeId] = 1;
            }
            elseif ($relIMProviderId) {
              $returnProperties[$relationshipTypes]['location'][$locationTypes[$relLocTypeId]]['im-' . $relIMProviderId] = 1;
            }
            else {
              $returnProperties[$relationshipTypes]['location'][$locationTypes[$relLocTypeId]][$relationField] = 1;
            }
            $relPhoneTypeId = $relIMProviderId = NULL;
          }
          else {
            $returnProperties[$relationshipTypes][$relationField] = 1;
          }
        }
        elseif (is_numeric($locTypeId)) {
          if ($phoneTypeId) {
            $returnProperties['location'][$locationTypes[$locTypeId]]['phone-' . $phoneTypeId] = 1;
          }
          elseif ($imProviderId) {
            $returnProperties['location'][$locationTypes[$locTypeId]]['im-' . $imProviderId] = 1;
          }
          else {
            $returnProperties['location'][$locationTypes[$locTypeId]][$fieldName] = 1;
          }
        }
        else {
          //hack to fix component fields
          if ($fieldName == 'event_id') {
            $returnProperties['event_title'] = 1;
          }
          else if (
            $exportMode == CRM_Export_Form_Select::EVENT_EXPORT &&
            array_key_exists( $fieldName, self::componentPaymentFields())
          ) {
              $selectedPaymentFields = true;
              $paymentTableId = 'participant_id';
              $returnProperties[$fieldName] = 1;
          }
          else {
            $returnProperties[$fieldName] = 1;

            //campaign field export.
            if (substr($fieldName, -8) == 'campaign') {
              $fldNames = explode('_', $fieldName);
              $returnProperties["{$fldNames[0]}_campaign_id"] = 1;
            }
          }
        }
      }

      // hack to add default returnproperty based on export mode
      if ($exportMode == CRM_Export_Form_Select::CONTRIBUTE_EXPORT) {
        $returnProperties['contribution_id'] = 1;
      }
      elseif ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT) {
        $returnProperties['participant_id'] = 1;
        if (CRM_Utils_Array::value('participant_role', $returnProperties)) {
          unset($returnProperties['participant_role']);
          $returnProperties['participant_role_id'] = 1;
        }
      }
      elseif ($exportMode == CRM_Export_Form_Select::MEMBER_EXPORT) {
        $returnProperties['membership_id'] = 1;
      }
      elseif ($exportMode == CRM_Export_Form_Select::PLEDGE_EXPORT) {
        $returnProperties['pledge_id'] = 1;
      }
      elseif ($exportMode == CRM_Export_Form_Select::CASE_EXPORT) {
        $returnProperties['case_id'] = 1;
      }
      elseif ($exportMode == CRM_Export_Form_Select::GRANT_EXPORT) {
        $returnProperties['grant_id'] = 1;
      }
      elseif ($exportMode == CRM_Export_Form_Select::ACTIVITY_EXPORT) {
        $returnProperties['activity_id'] = 1;
      }
    }
    else {
      $primary = TRUE;
      $fields = CRM_Contact_BAO_Contact::exportableFields('All', TRUE, TRUE);
      foreach ($fields as $key => $var) {
        if ($key && (substr($key, 0, 6) != 'custom')) {
          //for CRM=952
          $returnProperties[$key] = 1;
        }
      }

      if ($primary) {
        $returnProperties['location_type'] = 1;
        $returnProperties['im_provider'] = 1;
        $returnProperties['phone_type_id'] = 1;
        $returnProperties['provider_id'] = 1;
        $returnProperties['current_employer'] = 1;
      }

      $extraReturnProperties = array();
      $paymentFields = FALSE;

      switch ($queryMode) {
        case CRM_Contact_BAO_Query::MODE_EVENT:
          $paymentFields = TRUE;
          $paymentTableId = 'participant_id';
          break;

        case CRM_Contact_BAO_Query::MODE_MEMBER:
          $paymentFields = TRUE;
          $paymentTableId = 'membership_id';
          break;

        case CRM_Contact_BAO_Query::MODE_PLEDGE:
          $extraReturnProperties = CRM_Pledge_BAO_Query::extraReturnProperties($queryMode);
          $paymentFields = TRUE;
          $paymentTableId = 'pledge_payment_id';
          break;

        case CRM_Contact_BAO_Query::MODE_CASE:
          $extraReturnProperties = CRM_Case_BAO_Query::extraReturnProperties($queryMode);
          break;
      }

      if ($queryMode != CRM_Contact_BAO_Query::MODE_CONTACTS) {
        $componentReturnProperties = CRM_Contact_BAO_Query::defaultReturnProperties($queryMode);

        $campaignReturnProperties = array();;
        foreach ($componentReturnProperties as $fld => $true) {
          $campaignReturnProperties[$fld] = $true;
          if (substr($fld, -11) == 'campaign_id') {
            $exportCampaign = TRUE;
            $campaignReturnProperties[substr($fld, 0, -3)] = 1;
          }
        }
        $componentReturnProperties = $campaignReturnProperties;

        $returnProperties = array_merge($returnProperties, $componentReturnProperties);

        if (!empty($extraReturnProperties)) {
          $returnProperties = array_merge($returnProperties, $extraReturnProperties);
        }

        // unset non exportable fields for components
        $nonExpoFields = array(
          'groups', 'tags', 'notes', 'contribution_status_id',
          'pledge_status_id', 'pledge_payment_status_id',
        );
        foreach ($nonExpoFields as $value) {
          unset($returnProperties[$value]);
        }
      }
    }

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

    if ($componentTable &&
      CRM_Utils_Array::value('additional_group', $exportParams)
    ) {
      // If an Additional Group is selected, then all contacts in that group are
      // added to the export set (filtering out duplicates).
      $query = "
INSERT INTO {$componentTable} SELECT distinct gc.contact_id FROM civicrm_group_contact gc WHERE gc.group_id = {$exportParams['additional_group']} ON DUPLICATE KEY UPDATE {$componentTable}.contact_id = gc.contact_id";
      CRM_Core_DAO::executeQuery($query);
    }

    if ($moreReturnProperties) {
      // fix for CRM-7066
      if (CRM_Utils_Array::value('group', $moreReturnProperties)) {
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

    $query = new CRM_Contact_BAO_Query(NULL, $returnProperties, NULL,
      FALSE, FALSE, $queryMode,
      FALSE, TRUE, TRUE, NULL, $queryOperator
    );

    list($select, $from, $where, $having) = $query->query();

    if ($mergeSameHousehold == 1) {
      if (!$returnProperties['id']) {
        $returnProperties['id'] = 1;
      }

      //also merge Head of Household
      $relationKeyMOH = CRM_Utils_Array::key('Household Member of', $contactRelationshipTypes);
      $relationKeyHOH = CRM_Utils_Array::key('Head of Household for', $contactRelationshipTypes);

      foreach ($returnProperties as $key => $value) {
        if (!array_key_exists($key, $contactRelationshipTypes)) {
          $returnProperties[$relationKeyMOH][$key] = $value;
          $returnProperties[$relationKeyHOH][$key] = $value;
        }
      }

      unset($returnProperties[$relationKeyMOH]['location_type']);
      unset($returnProperties[$relationKeyMOH]['im_provider']);
      unset($returnProperties[$relationKeyHOH]['location_type']);
      unset($returnProperties[$relationKeyHOH]['im_provider']);
    }

    $allRelContactArray = $relationQuery = array();

    foreach ($contactRelationshipTypes as $rel => $dnt) {
      if ($relationReturnProperties = CRM_Utils_Array::value($rel, $returnProperties)) {
        $allRelContactArray[$rel] = array();
        // build Query for each relationship
        $relationQuery[$rel] = new CRM_Contact_BAO_Query(NULL, $relationReturnProperties,
          NULL, FALSE, FALSE, $queryMode
        );
        list($relationSelect, $relationFrom, $relationWhere, $relationHaving) = $relationQuery[$rel]->query();

        list($id, $direction) = explode('_', $rel, 2);
        // identify the relationship direction
        $contactA = 'contact_id_a';
        $contactB = 'contact_id_b';
        if ($direction == 'b_a') {
          $contactA = 'contact_id_b';
          $contactB = 'contact_id_a';
        }
        if ($exportMode == CRM_Export_Form_Select::CONTACT_EXPORT) {
          $relIDs = $ids;
        }
        elseif ($exportMode == CRM_Export_Form_Select::ACTIVITY_EXPORT) {
          $query = "SELECT source_contact_id FROM civicrm_activity
                              WHERE id IN ( " . implode(',', $ids) . ")";
          $dao = CRM_Core_DAO::executeQuery($query);
          while ($dao->fetch()) {
            $relIDs[] = $dao->source_contact_id;
          }
        }
        else {
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

            case CRM_Export_Form_Select::CASE_EXPORT:
              $component = 'civicrm_case';
              break;

            case CRM_Export_Form_Select::GRANT_EXPORT:
              $component = 'civicrm_grant';
              break;
          }
          $relIDs = CRM_Core_DAO::getContactIDsFromComponent($ids, $component);
        }

        $relationshipJoin = $relationshipClause = '';
        if ($componentTable) {
          $relationshipJoin = " INNER JOIN $componentTable ctTable ON ctTable.contact_id = {$contactA}";
        }
        else {
          $relID = implode(',', $relIDs);
          $relationshipClause = " AND crel.{$contactA} IN ( {$relID} )";
        }

        $relationFrom = " {$relationFrom}
                INNER JOIN civicrm_relationship crel ON crel.{$contactB} = contact_a.id AND crel.relationship_type_id = {$id}
                {$relationshipJoin} ";

        //check for active relationship status only
        $today               = date('Ymd');
        $relationActive      = " AND (crel.is_active = 1 AND ( crel.end_date is NULL OR crel.end_date >= {$today} ) )";
        $relationWhere       = " WHERE contact_a.is_deleted = 0 {$relationshipClause} {$relationActive}";
        $relationGroupBy     = " GROUP BY crel.{$contactA}";
        $relationSelect      = "{$relationSelect}, {$contactA} as refContact ";
        $relationQueryString = "$relationSelect $relationFrom $relationWhere $relationHaving $relationGroupBy";

        $allRelContactDAO = CRM_Core_DAO::executeQuery($relationQueryString);
        while ($allRelContactDAO->fetch()) {
          //FIX Me: Migrate this to table rather than array
          // build the array of all related contacts
          $allRelContactArray[$rel][$allRelContactDAO->refContact] = clone($allRelContactDAO);
        }
        $allRelContactDAO->free();
      }
    }

    // make sure the groups stuff is included only if specifically specified
    // by the fields param (CRM-1969), else we limit the contacts outputted to only
    // ones that are part of a group
    if (CRM_Utils_Array::value('groups', $returnProperties)) {
      $oldClause = "( contact_a.id = civicrm_group_contact.contact_id )";
      $newClause = " ( $oldClause AND ( civicrm_group_contact.status = 'Added' OR civicrm_group_contact.status IS NULL ) )";
      // total hack for export, CRM-3618
      $from = str_replace($oldClause,
        $newClause,
        $from
      );
    }

    if ($componentTable) {
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

    $queryString = "$select $from $where $having";

    $groupBy = "";
    if (CRM_Utils_Array::value('tags', $returnProperties) ||
      CRM_Utils_Array::value('groups', $returnProperties) ||
      CRM_Utils_Array::value('notes', $returnProperties) ||
      // CRM-9552
      ($queryMode & CRM_Contact_BAO_Query::MODE_CONTACTS && $query->_useGroupBy)
    ) {
      $groupBy = " GROUP BY contact_a.id";
    }

    if ($queryMode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
      $groupBy = " GROUP BY civicrm_activity.id ";
    }
    $queryString .= $groupBy;
    if ($order) {
      list($field, $dir) = explode(' ', $order, 2);
      $field = trim($field);
      if (CRM_Utils_Array::value($field, $returnProperties)) {
        // $queryString .= " ORDER BY $order";
      }
    }

    $multipleSelectFields = array('preferred_communication_method' => 1);

    $addPaymentHeader = FALSE;

    $paymentDetails = array();
    if ($paymentFields || $selectedPaymentFields) {

      // get payment related in for event and members
      $paymentDetails = CRM_Contribute_BAO_Contribution::getContributionDetails($exportMode, $ids);
      //get all payment headers.
      // If we haven't selected specific payment fields, load in all the
      // payment headers.
      if (!$selectedPaymentFields) {
        $paymentHeaders = self::componentPaymentFields();
        if (!empty($paymentDetails)) {
          $addPaymentHeader = TRUE;
        }
      }
      // If we have seleted specific payment fields, leave the payment headers
      // as an empty array; the headers for each selected field will be added
      // elsewhere.
      else {
        $paymentHeaders = array();
      }
      $nullContributionDetails = array_fill_keys(array_keys($paymentHeaders), NULL);
    }

    //get all campaigns.
    if ($exportCampaign) {
      $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);
    }

    $componentDetails = $headerRows = $sqlColumns = array();
    $setHeader = TRUE;

    $rowCount = self::EXPORT_ROW_COUNT;
    $offset = 0;
    // we write to temp table often to avoid using too much memory
    $tempRowCount = 100;

    $count = -1;

    // for CRM-3157 purposes
    $i18n = CRM_Core_I18n::singleton();

    while (1) {
      $limitQuery = "{$queryString} LIMIT {$offset}, {$rowCount}";
      $dao = CRM_Core_DAO::executeQuery($limitQuery);
      if ($dao->N <= 0) {
        break;
      }

      while ($dao->fetch()) {
        $count++;
        $row = array();

        //first loop through returnproperties so that we return what is required, and in same order.
        $relationshipField = 0;
        foreach ($returnProperties as $field => $value) {
          //we should set header only once
          if ($setHeader) {
            $sqlDone = FALSE;
            if (isset($query->_fields[$field]['title'])) {
              $headerRows[] = $query->_fields[$field]['title'];
            }
            elseif ($field == 'phone_type_id') {
              $headerRows[] = ts('Phone Type');
            }
            elseif ($field == 'provider_id') {
              $headerRows[] = ts('IM Service Provider');
            }
            elseif (is_array($value) && $field == 'location') {
              // fix header for location type case
              foreach ($value as $ltype => $val) {
                foreach (array_keys($val) as $fld) {
                  $type = explode('-', $fld);
                  $hdr = "{$ltype}-" . $query->_fields[$type[0]]['title'];

                  if (CRM_Utils_Array::value(1, $type)) {
                    if (CRM_Utils_Array::value(0, $type) == 'phone') {
                      $hdr .= "-" . CRM_Utils_Array::value($type[1], $phoneTypes);
                    }
                    elseif (CRM_Utils_Array::value(0, $type) == 'im') {
                      $hdr .= "-" . CRM_Utils_Array::value($type[1], $imProviders);
                    }
                  }
                  $headerRows[] = $hdr;
                  self::sqlColumnDefn($query, $sqlColumns, $hdr);
                }
                $sqlDone = TRUE;
              }
            }
            elseif (substr($field, 0, 5) == 'case_') {
              if ($query->_fields['case'][$field]['title']) {
                $headerRows[] = $query->_fields['case'][$field]['title'];
              }
              elseif ($query->_fields['activity'][$field]['title']) {
                $headerRows[] = $query->_fields['activity'][$field]['title'];
              }
            }
            elseif (array_key_exists($field, $contactRelationshipTypes)) {
              $relName = $field;
              foreach ($value as $relationField => $relationValue) {
                // below block is same as primary block (duplicate)
                if (isset($relationQuery[$field]->_fields[$relationField]['title'])) {
                  if ($relationQuery[$field]->_fields[$relationField]['name'] == 'name') {
                    $headerName = $field . '-' . $relationField;
                  }
                  else {
                    if ($relationField == 'current_employer') {
                      $headerName = $field . '-' . 'current_employer';
                    }
                    else {
                      $headerName = $field . '-' . $relationQuery[$field]->_fields[$relationField]['name'];
                    }
                  }

                  $headerRows[] = $headerName;

                  self::sqlColumnDefn($query, $sqlColumns, $headerName);
                }
                elseif ($relationField == 'phone_type_id') {
                  $headerName = $field . '-' . 'Phone Type';
                  $headerRows[] = $headerName;
                  self::sqlColumnDefn($query, $sqlColumns, $headerName);
                }
                elseif ($relationField == 'provider_id') {
                  $headerName = $field . '-' . 'Im Service Provider';
                  $headerRows[] = $headerName;
                  self::sqlColumnDefn($query, $sqlColumns, $headerName);
                }
                elseif ($relationField == 'state_province_id') {
                  $headerName = $field . '-' . 'state_province_id';
                  $headerRows[] = $headerName;
                  self::sqlColumnDefn($query, $sqlColumns, $headerName);
                }
                elseif (is_array($relationValue) && $relationField == 'location') {
                  // fix header for location type case
                  foreach ($relationValue as $ltype => $val) {
                    foreach (array_keys($val) as $fld) {
                      $type = explode('-', $fld);

                      $hdr = "{$ltype}-" . $relationQuery[$field]->_fields[$type[0]]['title'];

                      if (CRM_Utils_Array::value(1, $type)) {
                        if (CRM_Utils_Array::value(0, $type) == 'phone') {
                          $hdr .= "-" . CRM_Utils_Array::value($type[1], $phoneTypes);
                        }
                        elseif (CRM_Utils_Array::value(0, $type) == 'im') {
                          $hdr .= "-" . CRM_Utils_Array::value($type[1], $imProviders);
                        }
                      }
                      $headerName = $field . '-' . $hdr;
                      $headerRows[] = $headerName;
                      self::sqlColumnDefn($query, $sqlColumns, $headerName);
                    }
                  }
                }
              }
            }
            elseif ($selectedPaymentFields && array_key_exists($field, self::componentPaymentFields())) {
              $headerRows[] = CRM_Utils_Array::value($field, self::componentPaymentFields());
            }
            else {
              $headerRows[] = $field;
            }

            if (!$sqlDone) {
              self::sqlColumnDefn($query, $sqlColumns, $field);
            }
          }

          // add im_provider to $dao object
          if ($field == 'im_provider' && property_exists($dao, 'provider_id')) {
            $dao->im_provider = $dao->provider_id;
          }

          //build row values (data)
          $fieldValue = NULL;
          if (property_exists($dao, $field)) {
            $fieldValue = $dao->$field;
            // to get phone type from phone type id
            if ($field == 'phone_type_id' && isset($phoneTypes[$fieldValue])) {
              $fieldValue = $phoneTypes[$fieldValue];
            }
            elseif ($field == 'provider_id' || $field == 'im_provider') {
              $fieldValue = CRM_Utils_Array::value($fieldValue, $imProviders);
            }
            elseif ($field == 'participant_role_id') {
              $participantRoles = CRM_Event_PseudoConstant::participantRole();
              $sep              = CRM_Core_DAO::VALUE_SEPARATOR;
              $viewRoles        = array();
              foreach (explode($sep, $dao->$field) as $k => $v) {
                $viewRoles[] = $participantRoles[$v];
              }
              $fieldValue = implode(',', $viewRoles);
            }
            elseif ($field == 'master_id') {
              $masterAddressId = NULL;
              if (isset($dao->master_id)) {
                $masterAddressId = $dao->master_id;
              }
              // get display name of contact that address is shared.
              $fieldValue = CRM_Contact_BAO_Contact::getMasterDisplayName($masterAddressId, $dao->contact_id);
            }
          }

          if ($field == 'id') {
            $row[$field] = $dao->contact_id;
            // special case for calculated field
          }
          elseif ($field == 'pledge_balance_amount') {
            $row[$field] = $dao->pledge_amount - $dao->pledge_total_paid;
            // special case for calculated field
          }
          elseif ($field == 'pledge_next_pay_amount') {
            $row[$field] = $dao->pledge_next_pay_amount + $dao->pledge_outstanding_amount;
          }
          elseif (is_array($value) && $field == 'location') {
            // fix header for location type case
            foreach ($value as $ltype => $val) {
              foreach (array_keys($val) as $fld) {
                $type = explode('-', $fld);
                $fldValue = "{$ltype}-" . $type[0];

                if (CRM_Utils_Array::value(1, $type)) {
                  $fldValue .= "-" . $type[1];
                }

                // CRM-3157: localise country, region (both have ‘country’ context) and state_province (‘province’ context)
                switch ($fld) {
                  case 'country':
                  case 'world_region':
                    $row[$fldValue] = $i18n->crm_translate($dao->$fldValue, array('context' => 'country'));
                    break;

                  case 'state_province':
                    $row[$fldValue] = $i18n->crm_translate($dao->$fldValue, array('context' => 'province'));
                    break;

                  case 'im_provider':
                    $imFieldvalue = $fldValue . "-provider_id";
                    $row[$fldValue] = CRM_Utils_Array::value($dao->$imFieldvalue, $imProviders);
                    break;

                  default:
                    $row[$fldValue] = $dao->$fldValue;
                    break;
                }
              }
            }
          }
          elseif (array_key_exists($field, $contactRelationshipTypes)) {
            $relDAO = CRM_Utils_Array::value($dao->contact_id, $allRelContactArray[$field]);
            foreach ($value as $relationField => $relationValue) {
              if (is_object($relDAO) && property_exists($relDAO, $relationField)) {
                $fieldValue = $relDAO->$relationField;
                if ($relationField == 'phone_type_id') {
                  $fieldValue = $phoneTypes[$relationValue];
                }
                elseif ($relationField == 'provider_id') {
                  $fieldValue = CRM_Utils_Array::value($relationValue, $imProviders);
                }
              }
              else {
                $fieldValue = '';
              }
              $field = $field . '_';
              if (is_object($relDAO) && $relationField == 'id') {
                $row[$field . $relationField] = $relDAO->contact_id;
                            } else  if ( is_object( $relDAO ) && is_array( $relationValue ) && $relationField == 'location' ) {
                foreach ($relationValue as $ltype => $val) {
                  foreach (array_keys($val) as $fld) {
                    $type = explode('-', $fld);
                    $fldValue = "{$ltype}-" . $type[0];
                    if (CRM_Utils_Array::value(1, $type)) {
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
                  $row[$field . $relationField] = CRM_Core_BAO_CustomField::getDisplayValue($fieldValue, $cfID,
                    $relationQuery[$field]->_options
                  );
                }
                elseif (in_array($relationField, array(
                  'email_greeting', 'postal_greeting', 'addressee'))) {
                  //special case for greeting replacement
                  $fldValue = "{$relationField}_display";
                  $row[$field . $relationField] = $relDAO->$fldValue;
                }
                else {
                  //normal relationship fields
                  // CRM-3157: localise country, region (both have ‘country’ context) and state_province (‘province’ context)
                  switch ($relationField) {
                    case 'country':
                    case 'world_region':
                      $row[$field . $relationField] = $i18n->crm_translate($fieldValue, array('context' => 'country'));
                      break;

                    case 'state_province':
                      $row[$field . $relationField] = $i18n->crm_translate($fieldValue, array('context' => 'province'));
                      break;

                    default:
                      $row[$field . $relationField] = $fieldValue;
                      break;
                  }
                }
              }
              else {
                // if relation field is empty or null
                $row[$field . $relationField] = '';
              }
            }
          }
          elseif (isset($fieldValue) &&
            $fieldValue != ''
          ) {
            //check for custom data
            if ($cfID = CRM_Core_BAO_CustomField::getKeyID($field)) {
              $row[$field] = CRM_Core_BAO_CustomField::getDisplayValue($fieldValue, $cfID, $query->_options);
            }
            elseif (array_key_exists($field, $multipleSelectFields)) {
              //option group fixes
              $paramsNew = array($field => $fieldValue);
              if ($field == 'test_tutoring') {
                $name = array($field => array('newName' => $field, 'groupName' => 'test'));
                // for  readers group
              }
              elseif (substr($field, 0, 4) == 'cmr_') {
                $name = array($field => array('newName' => $field, 'groupName' => substr($field, 0, -3)));
              }
              else {
                $name = array($field => array('newName' => $field, 'groupName' => $field));
              }
              CRM_Core_OptionGroup::lookupValues($paramsNew, $name, FALSE);
              $row[$field] = $paramsNew[$field];
            }
            elseif (in_array($field, array(
              'email_greeting', 'postal_greeting', 'addressee'))) {
              //special case for greeting replacement
              $fldValue = "{$field}_display";
              $row[$field] = $dao->$fldValue;
            }
            else {
              //normal fields with a touch of CRM-3157
              switch ($field) {
                case 'country':
                case 'world_region':
                  $row[$field] = $i18n->crm_translate($fieldValue, array('context' => 'country'));
                  break;

                case 'state_province':
                  $row[$field] = $i18n->crm_translate($fieldValue, array('context' => 'province'));
                  break;

                case 'gender':
                case 'preferred_communication_method':
                case 'preferred_mail_format':
                  $row[$field] = $i18n->crm_translate($fieldValue);
                  break;

                default:
                  $row[$field] = $fieldValue;
                  break;
              }
            }
          }
          elseif (substr($field, -8) == 'campaign') {
            $campIdFld = "{$field}_id";
            $row[$field] = CRM_Utils_Array::value($dao->$campIdFld, $allCampaigns, '');
          }
          elseif ($selectedPaymentFields && array_key_exists($field, self::componentPaymentFields())) {
            $paymentData = CRM_Utils_Array::value($dao->$paymentTableId, $paymentDetails);
            $payFieldMapper = array(
              'componentPaymentField_total_amount'        => 'total_amount',
              'componentPaymentField_contribution_status' => 'contribution_status',
              'componentPaymentField_payment_instrument'  => 'pay_instru',
              'componentPaymentField_transaction_id'      => 'trxn_id',
              'componentPaymentField_received_date'       => 'receive_date',
            );
            $row[$field] = CRM_Utils_Array::value($payFieldMapper[$field], $paymentData, '');
          }
          else {
            // if field is empty or null
            $row[$field] = '';
          }
        }

        // add payment headers if required
        if ($addPaymentHeader && $paymentFields) {
          $headerRows = array_merge($headerRows, $paymentHeaders);
          foreach (array_keys($paymentHeaders) as $paymentHdr) {
            self::sqlColumnDefn($query, $sqlColumns, $paymentHdr);
          }
        }

        if ($setHeader) {
          $exportTempTable = self::createTempTable($sqlColumns);
        }

        //build header only once
        $setHeader = FALSE;

        // If specific payment fields have been selected for export, payment
        // data will already be in $row. Otherwise, add payment related
        // information, if appropriate.
        if ($addPaymentHeader) {
          if (!$selectedPaymentFields) {
            if ($paymentFields) {
              $paymentData = CRM_Utils_Array::value($row[$paymentTableId], $paymentDetails);
              if (!is_array($paymentData) || empty($paymentData)) {
                $paymentData = $nullContributionDetails;
              }
              $row = array_merge($row, $paymentData);
            }
            elseif (!empty($paymentDetails)) {
              $row = array_merge($row, $nullContributionDetails);
            }
          }
        }
        //remove organization name for individuals if it is set for current employer
        if (CRM_Utils_Array::value('contact_type', $row) &&
          $row['contact_type'] == 'Individual' && array_key_exists('organization_name', $row)
        ) {
          $row['organization_name'] = '';
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
      $dao->free();
      $offset += $rowCount;
    }

    if ($exportTempTable) {
      self::writeDetailsToTable($exportTempTable, $componentDetails, $sqlColumns);

      // do merge same address and merge same household processing
      if ($mergeSameAddress) {
        self::mergeSameAddress($exportTempTable, $headerRows, $sqlColumns, $exportParams);
      }

      // merge the records if they have corresponding households
      if ($mergeSameHousehold) {
        self::mergeSameHousehold($exportTempTable, $headerRows, $sqlColumns, $relationKeyMOH);
        self::mergeSameHousehold($exportTempTable, $headerRows, $sqlColumns, $relationKeyHOH);
      }

      // fix the headers for rows with relationship type
      if (!empty($relName)) {
        self::manipulateHeaderRows($headerRows, $contactRelationshipTypes);
      }

      // if postalMailing option is checked, exclude contacts who are deceased, have
      // "Do not mail" privacy setting, or have no street address
      if (isset($exportParams['postal_mailing_export']['postal_mailing_export']) &&
        $exportParams['postal_mailing_export']['postal_mailing_export'] == 1
      ) {
        self::postalMailingFormat($exportTempTable, $headerRows, $sqlColumns, $exportMode);
      }

      // call export hook
      CRM_Utils_Hook::export($exportTempTable, $headerRows, $sqlColumns, $exportMode);

      // now write the CSV file
      self::writeCSVFromTable($exportTempTable, $headerRows, $sqlColumns, $exportMode);

      // delete the export temp table and component table
      $sql = "DROP TABLE IF EXISTS {$exportTempTable}";
      CRM_Core_DAO::executeQuery($sql);

      CRM_Utils_System::civiExit();
    }
    else {
      CRM_Core_Error::fatal(ts('No records to export'));
    }
  }

  /**
   * name of the export file based on mode
   *
   * @param string  $output type of output
   * @param int     $mode export mode
   *
   * @return string name of the file
   */
  static function getExportFileName($output = 'csv', $mode = CRM_Export_Form_Select::CONTACT_EXPORT) {
    switch ($mode) {
      case CRM_Export_Form_Select::CONTACT_EXPORT:
        return ts('CiviCRM Contact Search');

      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        return ts('CiviCRM Contribution Search');

      case CRM_Export_Form_Select::MEMBER_EXPORT:
        return ts('CiviCRM Member Search');

      case CRM_Export_Form_Select::EVENT_EXPORT:
        return ts('CiviCRM Participant Search');

      case CRM_Export_Form_Select::PLEDGE_EXPORT:
        return ts('CiviCRM Pledge Search');

      case CRM_Export_Form_Select::CASE_EXPORT:
        return ts('CiviCRM Case Search');

      case CRM_Export_Form_Select::GRANT_EXPORT:
        return ts('CiviCRM Grant Search');

      case CRM_Export_Form_Select::ACTIVITY_EXPORT:
        return ts('CiviCRM Activity Search');
    }
  }

  /**
   * Function to handle import error file creation.
   *
   */
  function invoke() {
    $type = CRM_Utils_Request::retrieve('type', 'Positive', CRM_Core_DAO::$_nullObject);
    $parserName = CRM_Utils_Request::retrieve('parser', 'String', CRM_Core_DAO::$_nullObject);
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
      require_once (str_replace('_', DIRECTORY_SEPARATOR, $parserName) . ".php");
      // ensure the functions exists
      if (method_exists($parserName, 'errorFileName') &&
        method_exists($parserName, 'saveFileName')
      ) {
        $errorFileName = $parserName::errorFileName($type);
        $saveFileName = $parserName::saveFileName($type);
        if (!empty($errorFileName) && !empty($saveFileName)) {
          header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
          header('Content-Description: File Transfer');
          header('Content-Type: text/csv');
          header('Content-Length: ' . filesize($errorFileName));
          header('Content-Disposition: attachment; filename=' . $saveFileName);

          readfile($errorFileName);
        }
      }
    }
    CRM_Utils_System::civiExit();
  }

  function exportCustom($customSearchClass, $formValues, $order) {
    $ext = CRM_Extension_System::singleton()->getMapper();
    if (!$ext->isExtensionClass($customSearchClass)) {
      require_once (str_replace('_', DIRECTORY_SEPARATOR, $customSearchClass) . '.php');
    }
    else {
      require_once ($ext->classToPath($customSearchClass));
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

    $rows     = array();
    $dao      = CRM_Core_DAO::executeQuery($sql);
    $alterRow = FALSE;
    if (method_exists($search, 'alterRow')) {
      $alterRow = TRUE;
    }
    while ($dao->fetch()) {
      $row = array();

      foreach ($fields as $field) {
        $row[$field] = $dao->$field;
      }
      if ($alterRow) {
        $search->alterRow($row);
      }
      $rows[] = $row;
    }

    CRM_Core_Report_Excel::writeCSVFile(self::getExportFileName(), $header, $rows);
    CRM_Utils_System::civiExit();
  }

  static function sqlColumnDefn(&$query, &$sqlColumns, $field) {
    if (substr($field, -4) == '_a_b' || substr($field, -4) == '_b_a') {
      return;
    }

    $fieldName = CRM_Utils_String::munge(strtolower($field), '_', 64);
    if ($fieldName == 'id') {
      $fieldName = 'civicrm_primary_id';
    }

    // early exit for master_id, CRM-12100
    // in the DB it is an ID, but in the export, we retrive the display_name of the master record
    if ($fieldName == 'master_id') {
      $sqlColumns[$fieldName] = "$fieldName varchar(128)";
      return;
    }

    // set the sql columns
    if (isset($query->_fields[$field]['type'])) {
      switch ($query->_fields[$field]['type']) {
        case CRM_Utils_Type::T_INT:
        case CRM_Utils_Type::T_BOOL:
        case CRM_Utils_Type::T_BOOLEAN:
          $sqlColumns[$fieldName] = "$fieldName varchar(16)";
          break;

        case CRM_Utils_Type::T_STRING:
          if (isset($query->_fields[$field]['maxlength'])) {
            $sqlColumns[$fieldName] = "$fieldName varchar({$query->_fields[$field]['maxlength']})";
          }
          else {
            $sqlColumns[$fieldName] = "$fieldName varchar(64)";
          }
          break;

        case CRM_Utils_Type::T_TEXT:
        case CRM_Utils_Type::T_LONGTEXT:
        case CRM_Utils_Type::T_BLOB:
        case CRM_Utils_Type::T_MEDIUMBLOB:
          $sqlColumns[$fieldName] = "$fieldName longtext";
          break;

        case CRM_Utils_Type::T_FLOAT:
        case CRM_Utils_Type::T_ENUM:
        case CRM_Utils_Type::T_DATE:
        case CRM_Utils_Type::T_TIME:
        case CRM_Utils_Type::T_TIMESTAMP:
        case CRM_Utils_Type::T_MONEY:
        case CRM_Utils_Type::T_EMAIL:
        case CRM_Utils_Type::T_URL:
        case CRM_Utils_Type::T_CCNUM:
        default:
          $sqlColumns[$fieldName] = "$fieldName varchar(32)";
          break;
      }
    }
    else {
      if (substr($fieldName, -3, 3) == '_id') {
        $sqlColumns[$fieldName] = "$fieldName varchar(16)";
      }
      else {
        $changeFields = array(
          'groups',
          'tags',
          'notes'
        );

        if (in_array($fieldName, $changeFields)) {
          $sqlColumns[$fieldName] = "$fieldName text";
        }
        else {
          // set the sql columns for custom data
          if (isset($query->_fields[$field]['data_type'])) {

            switch ($query->_fields[$field]['data_type']) {
              case 'Country':
              case 'StateProvince':
              case 'Link':
              case 'String':
                $sqlColumns[$fieldName] = "$fieldName varchar(255)";
                break;

              case 'Memo':
                $sqlColumns[$fieldName] = "$fieldName text";
                break;

              default:
                $sqlColumns[$fieldName] = "$fieldName varchar(64)";
                break;
            }
          }
          else {
            $sqlColumns[$fieldName] = "$fieldName varchar(64)";
          }
        }
      }
    }
  }

  static function writeDetailsToTable($tableName, &$details, &$sqlColumns) {
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

    foreach ($details as $dontCare => $row) {
      $id++;
      $valueString = array($id);
      foreach ($row as $dontCare => $value) {
        if (empty($value)) {
          $valueString[] = "''";
        }
        else {
          $valueString[] = "'" . CRM_Core_DAO::escapeString($value) . "'";
        }
      }
      $sqlClause[] = '(' . implode(',', $valueString) . ')';
    }

    $sqlColumnString = '(id, ' . implode(',', array_keys($sqlColumns)) . ')';

    $sqlValueString = implode(",\n", $sqlClause);

    $sql = "
INSERT INTO $tableName $sqlColumnString
VALUES $sqlValueString
";

    CRM_Core_DAO::executeQuery($sql);
  }

  static function createTempTable(&$sqlColumns) {
    //creating a temporary table for the search result that need be exported
    $exportTempTable = CRM_Core_DAO::createTempTableName('civicrm_export', TRUE);

    // also create the sql table
    $sql = "DROP TABLE IF EXISTS {$exportTempTable}";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "
CREATE TABLE {$exportTempTable} (
     id int unsigned NOT NULL AUTO_INCREMENT,
";
    $sql .= implode(",\n", array_values($sqlColumns));

    $sql .= ",
  PRIMARY KEY ( id )
";
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

    $sql .= "
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
";

    CRM_Core_DAO::executeQuery($sql);
    return $exportTempTable;
  }

  static function mergeSameAddress($tableName, &$headerRows, &$sqlColumns, $exportParams) {
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
      $params = array(1 => array($values['addressee'], 'String'),
        2 => array($values['postalGreeting'], 'String'),
        3 => array($values['emailGreeting'], 'String'),
        4 => array($masterID, 'Integer'),
      );
      CRM_Core_DAO::executeQuery($sql, $params);

      // delete all copies
      $deleteIDs      = array_keys($values['copy']);
      $deleteIDString = implode(',', $deleteIDs);
      $sql            = "
DELETE FROM $tableName
WHERE  id IN ( $deleteIDString )
";
      CRM_Core_DAO::executeQuery($sql);
    }

    // unset temporary columns that were added for postal mailing format
    if (!empty($exportParams['merge_same_address']['temp_columns'])) {
      $unsetKeys = array_keys($sqlColumns);
      foreach ($unsetKeys as $headerKey => $sqlColKey) {
        if (array_key_exists($sqlColKey, $exportParams['merge_same_address']['temp_columns'])) {
          unset($sqlColumns[$sqlColKey], $headerRows[$headerKey]);
        }
      }
    }
  }

  static function _replaceMergeTokens($contactId, $exportParams) {
    $greetings = array();
    $contact = NULL;

    $greetingFields = array(
      'postal_greeting',
      'addressee',
    );
    foreach ($greetingFields as $greeting) {
      if (CRM_Utils_Array::value($greeting, $exportParams)) {
        $greetingLabel = $exportParams[$greeting];
        if (empty($contact)) {
          $values = array(
            'id' => $contactId,
            'version' => 3,
          );
          $contact = civicrm_api('contact', 'get', $values);

          if (CRM_Utils_Array::value('is_error', $contact)) {
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
   * Example: 'Hello {contact.first_name}' => converted to => '{contact.first_name}'
   * i.e 'Hello Alan' => converted to => 'Alan'
   *
   */
  static function _trimNonTokens(&$parsedString, $defaultGreeting,
    $addressMergeGreetings, $greetingType = 'postal_greeting'
  ) {
    if (CRM_Utils_Array::value($greetingType, $addressMergeGreetings)) {
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

  static function _buildMasterCopyArray($sql, $exportParams, $sharedAddress = FALSE) {
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

        if (CRM_Utils_Array::value('postal_greeting_other', $exportParams) &&
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

        if (CRM_Utils_Array::value('addressee_other', $exportParams) &&
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
   * Function to merge household record into the individual record
   * if exists
   *
   * @param string $exportTempTable temporary temp table that stores the records
   * @param array  $headerRows array of headers for the export file
   * @param array  $sqlColumns array of names of the table columns of the temp table
   * @param string $prefix name of the relationship type that is prefixed to the table columns
   */
  static function mergeSameHousehold($exportTempTable, &$headerRows, &$sqlColumns, $prefix) {
    $prefixColumn = $prefix . '_';
    $allKeys      = array_keys($sqlColumns);
    $replaced     = array();
    $headerRows   = array_values($headerRows);

    // name map of the non standard fields in header rows & sql columns
    $mappingFields = array(
      'civicrm_primary_id' => 'id',
      'contact_source' => 'source',
      'current_employer_id' => 'employer_id',
      'contact_is_deleted' => 'is_deleted',
      'name' => 'address_name',
      'provider_id' => 'im_service_provider',
      'phone_type_id' => 'phone_type'
    );

    //figure out which columns are to be replaced by which ones
    foreach ($sqlColumns as $columnNames => $dontCare) {
      if ($rep = CRM_Utils_Array::value($columnNames, $mappingFields)) {
        $replaced[$columnNames] = CRM_Utils_String::munge($prefixColumn . $rep, '_', 64);
      }
      else {
        $householdColName = CRM_Utils_String::munge($prefixColumn . $columnNames, '_', 64);

        if (CRM_Utils_Array::value($householdColName, $sqlColumns)) {
          $replaced[$columnNames] = $householdColName;
        }
      }
    }
    $query = "UPDATE $exportTempTable SET ";

    $clause = array();
    foreach ($replaced as $from => $to) {
      $clause[] = "$from = $to ";
      unset($sqlColumns[$to]);
      if ($key = CRM_Utils_Array::key($to, $allKeys)) {
        unset($headerRows[$key]);
      }
    }
    $query .= implode(",\n", $clause);
    $query .= " WHERE {$replaced['civicrm_primary_id']} != ''";

    CRM_Core_DAO::executeQuery($query);

    //drop the table columns that store redundant household info
    $dropQuery = "ALTER TABLE $exportTempTable ";
    foreach ($replaced as $householdColumns) {
      $dropClause[] = " DROP $householdColumns ";
    }
    $dropQuery .= implode(",\n", $dropClause);

    CRM_Core_DAO::executeQuery($dropQuery);

    // also drop the temp table if exists
    $sql = "DROP TABLE IF EXISTS {$exportTempTable}_temp";
    CRM_Core_DAO::executeQuery($sql);

    // clean up duplicate records
    $query = "
CREATE TABLE {$exportTempTable}_temp SELECT *
FROM {$exportTempTable}
GROUP BY civicrm_primary_id ";

    CRM_Core_DAO::executeQuery($query);

    $query = "DROP TABLE $exportTempTable";
    CRM_Core_DAO::executeQuery($query);

    $query = "ALTER TABLE {$exportTempTable}_temp RENAME TO {$exportTempTable}";
    CRM_Core_DAO::executeQuery($query);
  }

  static function writeCSVFromTable($exportTempTable, $headerRows, $sqlColumns, $exportMode, $saveFile = null, $batchItems = '') {
    $writeHeader = TRUE;
    $offset      = 0;
    $limit       = self::EXPORT_ROW_COUNT;

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
      if ( $exportMode == 'financial' ) {
        $getExportFileName = 'CiviCRM Contribution Search';
      }
      else {
        $getExportFileName =self::getExportFileName( 'csv', $exportMode );
      }
      $csvRows = CRM_Core_Report_Excel::writeCSVFile( $getExportFileName,
        $headerRows,
        $componentDetails,
        null,
        $writeHeader,
        $saveFile );

      if ($saveFile && !empty($csvRows)) {
        $batchItems .= $csvRows;
      }

      $writeHeader = false;
      $offset += $limit;
    }
  }

  /**
   * Function to manipulate header rows for relationship fields
   *
   */
  function manipulateHeaderRows(&$headerRows, $contactRelationshipTypes) {
    foreach ($headerRows as & $header) {
      $split = explode('-', $header);
      if ($relationTypeName = CRM_Utils_Array::value($split[0], $contactRelationshipTypes)) {
        $split[0] = $relationTypeName;
        $header = implode('-', $split);
      }
    }
  }

  /**
   * Function to exclude contacts who are deceased, have "Do not mail" privacy setting,
   * or have no street address
   *
   */
  function postalMailingFormat($exportTempTable, &$headerRows, &$sqlColumns, $exportParams) {
    $whereClause = array();

    if (array_key_exists('is_deceased', $sqlColumns)) {
      $whereClause[] = 'is_deceased = 1';
    }

    if (array_key_exists('do_not_mail', $sqlColumns)) {
      $whereClause[] = 'do_not_mail = 1';
    }

    if (array_key_exists('street_address', $sqlColumns)) {
      $addressWhereClause = " ( (street_address IS NULL) OR (street_address = '') ) ";

      // check for supplemental_address_1
      if (array_key_exists('supplemental_address_1', $sqlColumns)) {
        $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
          'address_options', TRUE, NULL, TRUE
        );
        if (CRM_Utils_Array::value('supplemental_address_1', $addressOptions)) {
          $addressWhereClause .= " AND ( (supplemental_address_1 IS NULL) OR (supplemental_address_1 = '') ) ";
          // enclose it again, since we are doing an AND in between a set of ORs
          $addressWhereClause = "( $addressWhereClause )";
        }
      }

      $whereClause[] = $addressWhereClause;
    }

    if (!empty($whereClause)) {
      $whereClause = implode(' OR ', $whereClause);
      $query = "
DELETE
FROM   $exportTempTable
WHERE  {$whereClause}";
      CRM_Core_DAO::singleValueQuery($query);
    }

    // unset temporary columns that were added for postal mailing format
    if (!empty($exportParams['postal_mailing_export']['temp_columns'])) {
      $unsetKeys = array_keys($sqlColumns);
      foreach ($unsetKeys as $headerKey => $sqlColKey) {
        if (array_key_exists($sqlColKey, $exportParams['postal_mailing_export']['temp_columns'])) {
          unset($sqlColumns[$sqlColKey], $headerRows[$headerKey]);
        }
      }
    }
  }

  /**
   * Build componentPayment fields.
   */
  static function componentPaymentFields() {
    static $componentPaymentFields;
    if (!isset( $componentPaymentFields)) {
      $componentPaymentFields = array(
        'componentPaymentField_total_amount'        => ts('Total Amount'),
        'componentPaymentField_contribution_status' => ts('Contribution Status'),
        'componentPaymentField_received_date'       => ts('Received Date'),
        'componentPaymentField_payment_instrument'  => ts('Payment Instrument'),
        'componentPaymentField_transaction_id'      => ts('Transaction ID'),
      );
    }
    return $componentPaymentFields;
  }
}

