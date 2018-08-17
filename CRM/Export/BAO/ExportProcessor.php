<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Class CRM_Export_BAO_ExportProcessor
 *
 * Class to handle logic of export.
 */
class CRM_Export_BAO_ExportProcessor {

  /**
   * @var int
   */
  protected $queryMode;

  /**
   * @var int
   */
  protected $exportMode;

  /**
   * Array of fields in the main query.
   *
   * @var array
   */
  protected $queryFields = [];

  /**
   * Either AND or OR.
   *
   * @var string
   */
  protected $queryOperator;

  /**
   * Requested output fields.
   *
   * If set to NULL then it is 'primary fields only'
   * which actually means pretty close to all fields!
   *
   * @var array|null
   */
  protected $requestedFields;

  /**
   * Key representing the head of household in the relationship array.
   *
   * e.g. ['8_b_a' => 'Household Member Is', '8_a_b = 'Household Member Of'.....]
   *
   * @var
   */
  protected $relationshipTypes = [];

  /**
   * Array of properties to retrieve for relationships.
   *
   * @var array
   */
  protected $relationshipReturnProperties = [];

  /**
   * @var array
   */
  protected $returnProperties = [];

  /**
   * CRM_Export_BAO_ExportProcessor constructor.
   *
   * @param int $exportMode
   * @param array|NULL $requestedFields
   * @param string $queryOperator
   */
  public function __construct($exportMode, $requestedFields, $queryOperator) {
    $this->setExportMode($exportMode);
    $this->setQueryMode();
    $this->setQueryOperator($queryOperator);
    $this->setRequestedFields($requestedFields);
    $this->setRelationshipTypes();
  }

  /**
   * @return array|null
   */
  public function getRequestedFields() {
    return $this->requestedFields;
  }

  /**
   * @param array|null $requestedFields
   */
  public function setRequestedFields($requestedFields) {
    $this->requestedFields = $requestedFields;
  }


  /**
   * @return array
   */
  public function getReturnProperties() {
    return $this->returnProperties;
  }

  /**
   * @param array $returnProperties
   */
  public function setReturnProperties($returnProperties) {
    $this->returnProperties = $returnProperties;
  }

  /**
   * @return array
   */
  public function getRelationshipTypes() {
    return $this->relationshipTypes;
  }

  /**
   */
  public function setRelationshipTypes() {
    $this->relationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(
      NULL,
      NULL,
      NULL,
      NULL,
      TRUE,
      'name',
      FALSE
    );
  }


  /**
   * @param $fieldName
   * @return bool
   */
  public function isRelationshipTypeKey($fieldName) {
    return array_key_exists($fieldName, $this->relationshipTypes);
  }

  /**
   * @return string
   */
  public function getQueryOperator() {
    return $this->queryOperator;
  }

  /**
   * @param string $queryOperator
   */
  public function setQueryOperator($queryOperator) {
    $this->queryOperator = $queryOperator;
  }

  /**
   * @return array
   */
  public function getQueryFields() {
    return $this->queryFields;
  }

  /**
   * @param array $queryFields
   */
  public function setQueryFields($queryFields) {
    $this->queryFields = $queryFields;
  }

  /**
   * @return int
   */
  public function getQueryMode() {
    return $this->queryMode;
  }

  /**
   * Set the query mode based on the export mode.
   */
  public function setQueryMode() {

    switch ($this->getExportMode()) {
      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
        break;

      case CRM_Export_Form_Select::EVENT_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_EVENT;
        break;

      case CRM_Export_Form_Select::MEMBER_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_MEMBER;
        break;

      case CRM_Export_Form_Select::PLEDGE_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_PLEDGE;
        break;

      case CRM_Export_Form_Select::CASE_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_CASE;
        break;

      case CRM_Export_Form_Select::GRANT_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_GRANT;
        break;

      case CRM_Export_Form_Select::ACTIVITY_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_ACTIVITY;
        break;

      default:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_CONTACTS;
    }
  }

  /**
   * @return int
   */
  public function getExportMode() {
    return $this->exportMode;
  }

  /**
   * @param int $exportMode
   */
  public function setExportMode($exportMode) {
    $this->exportMode = $exportMode;
  }

  /**
   * @param $params
   * @param $order
   * @param $returnProperties
   * @return array
   */
  public function runQuery($params, $order, $returnProperties) {
    $query = new CRM_Contact_BAO_Query($params, $returnProperties, NULL,
      FALSE, FALSE, $this->getQueryMode(),
      FALSE, TRUE, TRUE, NULL, $this->getQueryOperator()
    );

    //sort by state
    //CRM-15301
    $query->_sort = $order;
    list($select, $from, $where, $having) = $query->query();
    $this->setQueryFields($query->_fields);
    return array($query, $select, $from, $where, $having);
  }

  /**
   * Get array of fields to return, over & above those defined in the main contact exportable fields.
   *
   * These include export mode specific fields & some fields apparently required as 'exportableFields'
   * but not returned by the function of the same name.
   *
   * @return array
   *   Array of fields to return in the format ['field_name' => 1,...]
   */
  public function getAdditionalReturnProperties() {

    $missing = [
      'location_type',
      'im_provider',
      'phone_type_id',
      'provider_id',
      'current_employer',
    ];
    if ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_CONTACTS) {
      $componentSpecificFields = [];
    }
    else {
      $componentSpecificFields = CRM_Contact_BAO_Query::defaultReturnProperties($this->getQueryMode());
    }
    if ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_PLEDGE) {
      $componentSpecificFields = array_merge($componentSpecificFields, CRM_Pledge_BAO_Query::extraReturnProperties($this->getQueryMode()));
      unset($componentSpecificFields['contribution_status_id']);
      unset($componentSpecificFields['pledge_status_id']);
      unset($componentSpecificFields['pledge_payment_status_id']);
    }
    if ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_CASE) {
      $componentSpecificFields = array_merge($componentSpecificFields, CRM_Case_BAO_Query::extraReturnProperties($this->getQueryMode()));
    }
    if ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
      $componentSpecificFields = array_merge($componentSpecificFields, CRM_Contribute_BAO_Query::softCreditReturnProperties(TRUE));
      unset($componentSpecificFields['contribution_status_id']);
    }
    return array_merge(array_fill_keys($missing, 1), $componentSpecificFields);
  }

  /**
   * Should payment fields be appended to the export.
   *
   * (This is pretty hacky so hopefully this function won't last long - notice
   * how obviously it should be part of the above function!).
   */
  public function isExportPaymentFields() {
    if ($this->getRequestedFields() === NULL
      &&  in_array($this->getQueryMode(), [
        CRM_Contact_BAO_Query::MODE_EVENT,
        CRM_Contact_BAO_Query::MODE_MEMBER,
        CRM_Contact_BAO_Query::MODE_PLEDGE,
      ])) {
      return TRUE;
    }
    elseif ($this->isExportSpecifiedPaymentFields()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Has specific payment fields been requested (as opposed to via all fields).
   *
   * If specific fields have been requested then they get added at various points.
   *
   * @return bool
   */
  public function isExportSpecifiedPaymentFields() {
    if ($this->getRequestedFields() !== NULL && $this->hasRequestedComponentPaymentFields()) {
      return TRUE;
    }
  }

  /**
   * Get the name of the id field in the table that connects contributions to the export entity.
   */
  public function getPaymentTableID() {
    if ($this->getRequestedFields() === NULL) {
      $mapping = [
        CRM_Contact_BAO_Query::MODE_EVENT => 'participant_id',
        CRM_Contact_BAO_Query::MODE_MEMBER => 'membership_id',
        CRM_Contact_BAO_Query::MODE_PLEDGE => 'pledge_payment_id',
      ];
      return isset($mapping[$this->getQueryMode()]) ? $mapping[$this->getQueryMode()] : '';
    }
    elseif ($this->hasRequestedComponentPaymentFields()) {
      return 'participant_id';
    }
    return FALSE;
  }

  /**
   * Have component payment fields been requested.
   *
   * @return bool
   */
  protected function hasRequestedComponentPaymentFields() {
    if ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_EVENT) {
      $participantPaymentFields = array_intersect_key($this->getComponentPaymentFields(), $this->getReturnProperties());
      if (!empty($participantPaymentFields)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get fields that indicate payment fields have been requested for a component.
   *
   * @return array
   */
  protected function getComponentPaymentFields() {
    return [
      'componentPaymentField_total_amount' => ts('Total Amount'),
      'componentPaymentField_contribution_status' => ts('Contribution Status'),
      'componentPaymentField_received_date' => ts('Date Received'),
      'componentPaymentField_payment_instrument' => ts('Payment Method'),
      'componentPaymentField_transaction_id' => ts('Transaction ID'),
    ];
  }

  /**
   * Get the default properties when not specified.
   *
   * In the UI this appears as 'Primary fields only' but in practice it's
   * most of the kitchen sink and the hallway closet thrown in.
   *
   * Since CRM-952 custom fields are excluded, but no other form of mercy is shown.
   *
   * @return array
   */
  public function getDefaultReturnProperties() {
    $returnProperties = [];
    $fields = CRM_Contact_BAO_Contact::exportableFields('All', TRUE, TRUE);
    $skippedFields = ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_CONTACTS) ? [] : [
      'groups',
      'tags',
      'notes'
    ];

    foreach ($fields as $key => $var) {
      if ($key && (substr($key, 0, 6) != 'custom') && !in_array($key, $skippedFields)) {
        $returnProperties[$key] = 1;
      }
    }
    $returnProperties = array_merge($returnProperties, $this->getAdditionalReturnProperties());
    return $returnProperties;
  }

  /**
   * Add the field to relationship return properties & return it.
   *
   * This function is doing both setting & getting which is yuck but it is an interim
   * refactor.
   *
   * @param array $value
   * @param string $relationshipKey
   *
   * @return array
   */
  public function setRelationshipReturnProperties($value, $relationshipKey) {
    $relPhoneTypeId = $relIMProviderId = NULL;
    if (!empty($value[2])) {
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
    elseif (!empty($value[4])) {
      $relationField = CRM_Utils_Array::value(4, $value);
      $relLocTypeId = CRM_Utils_Array::value(5, $value);
      if ($relationField == 'phone') {
        $relPhoneTypeId = CRM_Utils_Array::value(6, $value);
      }
      elseif ($relationField == 'im') {
        $relIMProviderId = CRM_Utils_Array::value(6, $value);
      }
    }
    if (in_array($relationField, $this->getValidLocationFields()) && is_numeric($relLocTypeId)) {
      $locationName = CRM_Core_PseudoConstant::getName('CRM_Core_BAO_Address', 'location_type_id', $relLocTypeId);
      if ($relPhoneTypeId) {
        $this->relationshipReturnProperties[$relationshipKey]['location'][$locationName]['phone-' . $relPhoneTypeId] = 1;
      }
      elseif ($relIMProviderId) {
        $this->relationshipReturnProperties[$relationshipKey]['location'][$locationName]['im-' . $relIMProviderId] = 1;
      }
      else {
        $this->relationshipReturnProperties[$relationshipKey]['location'][$locationName][$relationField] = 1;
      }
    }
    else {
      $this->relationshipReturnProperties[$relationshipKey][$relationField] = 1;
    }
    return $this->relationshipReturnProperties[$relationshipKey];
  }

  /**
   * Get the default location fields to request.
   *
   * @return array
   */
  public function getValidLocationFields() {
    return [
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
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
    ];
  }

  /**
   * Get the sql column definition for the given field.
   *
   * @param $field
   *
   * @return mixed
   */
  public function getSqlColumnDefinition($field) {
    $fieldName = $this->getMungedFieldName($field);

    // early exit for master_id, CRM-12100
    // in the DB it is an ID, but in the export, we retrive the display_name of the master record
    // also for current_employer, CRM-16939
    if ($fieldName == 'master_id' || $fieldName == 'current_employer') {
      return "$fieldName varchar(128)";
    }

    if (substr($fieldName, -11) == 'campaign_id') {
      // CRM-14398
      return "$fieldName varchar(128)";
    }

    $queryFields = $this->getQueryFields();
    $lookUp = ['prefix_id', 'suffix_id'];
    // set the sql columns
    if (isset($queryFields[$field]['type'])) {
      switch ($queryFields[$field]['type']) {
        case CRM_Utils_Type::T_INT:
        case CRM_Utils_Type::T_BOOLEAN:
          if (in_array($field, $lookUp)) {
            return "$fieldName varchar(255)";
          }
          else {
            return "$fieldName varchar(16)";
          }

        case CRM_Utils_Type::T_STRING:
          if (isset($queryFields[$field]['maxlength'])) {
            return "$fieldName varchar({$queryFields[$field]['maxlength']})";
          }
          else {
            return "$fieldName varchar(255)";
          }

        case CRM_Utils_Type::T_TEXT:
        case CRM_Utils_Type::T_LONGTEXT:
        case CRM_Utils_Type::T_BLOB:
        case CRM_Utils_Type::T_MEDIUMBLOB:
          return "$fieldName longtext";

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
          return "$fieldName varchar(32)";
      }
    }
    else {
      if (substr($fieldName, -3, 3) == '_id') {
        return "$fieldName varchar(255)";
      }
      elseif (substr($fieldName, -5, 5) == '_note') {
        return "$fieldName text";
      }
      else {
        $changeFields = [
          'groups',
          'tags',
          'notes',
        ];

        if (in_array($fieldName, $changeFields)) {
          return "$fieldName text";
        }
        else {
          // set the sql columns for custom data
          if (isset($queryFields[$field]['data_type'])) {

            switch ($queryFields[$field]['data_type']) {
              case 'String':
                // May be option labels, which could be up to 512 characters
                $length = max(512, CRM_Utils_Array::value('text_length', $queryFields[$field]));
                return "$fieldName varchar($length)";

              case 'Country':
              case 'StateProvince':
              case 'Link':
                return "$fieldName varchar(255)";

              case 'Memo':
                return "$fieldName text";

              default:
                return "$fieldName varchar(255)";
            }
          }
          else {
            return "$fieldName text";
          }
        }
      }
    }
  }

  /**
   * Get the munged field name.
   *
   * @param string $field
   * @return string
   */
  public function getMungedFieldName($field) {
    $fieldName = CRM_Utils_String::munge(strtolower($field), '_', 64);
    if ($fieldName == 'id') {
      $fieldName = 'civicrm_primary_id';
    }
    return $fieldName;
  }

}
