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
   * Is the contact being merged into a single household.
   *
   * @var bool
   */
  protected $isMergeSameHousehold;

  /**
   * Only export contacts that can receive postal mail.
   *
   * Includes being alive, having an address & not having do_not_mail.
   *
   * @var bool
   */
  protected $isPostalableOnly;

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
   * IDs of households that have already been exported.
   *
   * @var array
   */
  protected $exportedHouseholds = [];

  /**
   * Get return properties by relationship.
   * @return array
   */
  public function getRelationshipReturnProperties() {
    return $this->relationshipReturnProperties;
  }

  /**
   * Export values for related contacts.
   *
   * @var array
   */
  protected $relatedContactValues = [];

  /**
   * @var array
   */
  protected $returnProperties = [];

  /**
   * @var array
   */
  protected $outputSpecification = [];

  /**
   * CRM_Export_BAO_ExportProcessor constructor.
   *
   * @param int $exportMode
   * @param array|NULL $requestedFields
   * @param string $queryOperator
   * @param bool $isMergeSameHousehold
   * @param bool $isPostalableOnly
   */
  public function __construct($exportMode, $requestedFields, $queryOperator, $isMergeSameHousehold = FALSE, $isPostalableOnly = FALSE) {
    $this->setExportMode($exportMode);
    $this->setQueryMode();
    $this->setQueryOperator($queryOperator);
    $this->setRequestedFields($requestedFields);
    $this->setRelationshipTypes();
    $this->setIsMergeSameHousehold($isMergeSameHousehold);
    $this->setisPostalableOnly($isPostalableOnly);
  }

  /**
   * @return bool
   */
  public function isPostalableOnly() {
    return $this->isPostalableOnly;
  }

  /**
   * @param bool $isPostalableOnly
   */
  public function setIsPostalableOnly($isPostalableOnly) {
    $this->isPostalableOnly = $isPostalableOnly;
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
   * Set the value for a relationship type field.
   *
   * In this case we are building up an array of properties for a related contact.
   *
   * These may be used for direct exporting or for merge to household depending on the
   * options selected.
   *
   * @param string $relationshipType
   * @param int $contactID
   * @param string $field
   * @param string $value
   */
  public function setRelationshipValue($relationshipType, $contactID, $field, $value) {
    $this->relatedContactValues[$relationshipType][$contactID][$field] = $value;
  }

  /**
   * Get the value for a relationship type field.
   *
   * In this case we are building up an array of properties for a related contact.
   *
   * These may be used for direct exporting or for merge to household depending on the
   * options selected.
   *
   * @param string $relationshipType
   * @param int $contactID
   * @param string $field
   *
   * @return string
   */
  public function getRelationshipValue($relationshipType, $contactID, $field) {
    return isset($this->relatedContactValues[$relationshipType][$contactID][$field]) ? $this->relatedContactValues[$relationshipType][$contactID][$field] : '';
  }

  /**
   * Get the id of the related household.
   *
   * @param int $contactID
   * @param string $relationshipType
   *
   * @return int
   */
  public function getRelatedHouseholdID($contactID, $relationshipType) {
    return $this->relatedContactValues[$relationshipType][$contactID]['id'];
  }

  /**
   * Has the household already been exported.
   *
   * @param int $housholdContactID
   *
   * @return bool
   */
  public function isHouseholdExported($housholdContactID) {
    return isset($this->exportedHouseholds[$housholdContactID]);

  }

  /**
   * @return bool
   */
  public function isMergeSameHousehold() {
    return $this->isMergeSameHousehold;
  }

  /**
   * @param bool $isMergeSameHousehold
   */
  public function setIsMergeSameHousehold($isMergeSameHousehold) {
    $this->isMergeSameHousehold = $isMergeSameHousehold;
  }

  /**
   * Return relationship types for household merge.
   *
   * @return mixed
   */
  public function getHouseholdRelationshipTypes() {
    if (!$this->isMergeSameHousehold()) {
      return [];
    }
    return [
      CRM_Utils_Array::key('Household Member of', $this->getRelationshipTypes()),
      CRM_Utils_Array::key('Head of Household for', $this->getRelationshipTypes()),
    ];
  }

  /**
   * @param $fieldName
   * @return bool
   */
  public function isRelationshipTypeKey($fieldName) {
    return array_key_exists($fieldName, $this->relationshipTypes);
  }


  /**
   * @param $fieldName
   * @return bool
   */
  public function isHouseholdMergeRelationshipTypeKey($fieldName) {
    return in_array($fieldName, $this->getHouseholdRelationshipTypes());
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
    // legacy hacks - we add these to queryFields because this
    // pseudometadata is currently required.
    $queryFields['im_provider']['pseudoconstant']['var'] = 'imProviders';
    $queryFields['country']['context'] = 'country';
    $queryFields['world_region']['context'] = 'country';
    $queryFields['state_province']['context'] = 'province';
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
   * Get the name for the export file.
   *
   * @return string
   */
  public function getExportFileName() {
    switch ($this->getExportMode()) {
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

      default:
        // Legacy code suggests the value could be 'financial' - ie. something
        // other than what should be accepted. However, I suspect that this line is
        // never hit.
        return ts('CiviCRM Search');
    }
  }

  /**
   * Get the label for the header row based on the field to output.
   *
   * @param string $field
   *
   * @return string
   */
  public function getHeaderForRow($field) {
    if (substr($field, -11) == 'campaign_id') {
      // @todo - set this correctly in the xml rather than here.
      // This will require a generalised handling cleanup
      return ts('Campaign ID');
    }
    if ($this->isMergeSameHousehold() && $field === 'id') {
      return ts('Household ID');
    }
    elseif (isset($this->getQueryFields()[$field]['title'])) {
      return $this->getQueryFields()[$field]['title'];
    }
    elseif ($this->isExportPaymentFields() && array_key_exists($field, $this->getcomponentPaymentFields())) {
      return CRM_Utils_Array::value($field, $this->getcomponentPaymentFields());
    }
    else {
      return $field;
    }
  }

  /**
   * @param $params
   * @param $order
   * @param $returnProperties
   * @return array
   */
  public function runQuery($params, $order, $returnProperties) {
    $addressWhere = '';
    $params = array_merge($params, $this->getWhereParams());
    if ($this->isPostalableOnly) {
      if (array_key_exists('street_address', $returnProperties)) {
        $addressWhere = " civicrm_address.street_address <> ''";
        if (array_key_exists('supplemental_address_1', $returnProperties)) {
          // We need this to be an OR rather than AND on the street_address so, hack it in.
          $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
            'address_options', TRUE, NULL, TRUE
          );
          if (!empty($addressOptions['supplemental_address_1'])) {
            $addressWhere .= " OR civicrm_address.supplemental_address_1 <> ''";
          }
        }
        $addressWhere = ' AND (' . $addressWhere . ')';
      }
    }
    $query = new CRM_Contact_BAO_Query($params, $returnProperties, NULL,
      FALSE, FALSE, $this->getQueryMode(),
      FALSE, TRUE, TRUE, NULL, $this->getQueryOperator()
    );

    //sort by state
    //CRM-15301
    $query->_sort = $order;
    list($select, $from, $where, $having) = $query->query();
    $this->setQueryFields($query->_fields);
    return [$query, $select, $from, $where . $addressWhere, $having];
  }

  /**
   * Add a row to the specification for how to output data.
   *
   * @param string $key
   * @param string $relationshipType
   * @param string $locationType
   * @param int $entityTypeID phone_type_id or provider_id for phone or im fields.
   */
  public function addOutputSpecification($key, $relationshipType = NULL, $locationType = NULL, $entityTypeID = NULL) {
    $entityLabel = '';
    if ($entityTypeID) {
      if ($key === 'phone') {
        $entityLabel = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Phone', 'phone_type_id', $entityTypeID);
      }
      if ($key === 'im') {
        $entityLabel = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_IM', 'provider_id', $entityTypeID);
      }
    }

    // These oddly constructed keys are for legacy reasons. Altering them will affect test success
    // but in time it may be good to rationalise them.
    $label = $this->getOutputSpecificationLabel($key, $relationshipType, $locationType, $entityLabel);
    $index = $this->getOutputSpecificationIndex($key, $relationshipType, $locationType, $entityLabel);
    $fieldKey = $this->getOutputSpecificationFieldKey($key, $relationshipType, $locationType, $entityLabel);

    $this->outputSpecification[$index]['header'] = $label;
    $this->outputSpecification[$index]['sql_columns'] = $this->getSqlColumnDefinition($fieldKey, $key);

    if ($relationshipType && $this->isHouseholdMergeRelationshipTypeKey($relationshipType)) {
      $this->setColumnAsCalculationOnly($index);
    }
    $this->outputSpecification[$index]['metadata'] = $this->getMetaDataForField($key);
  }

  /**
   * Get the metadata for the given field.
   *
   * @param $key
   *
   * @return array
   */
  public function getMetaDataForField($key) {
    $mappings = ['contact_id' => 'id'];
    if (isset($this->getQueryFields()[$key])) {
      return $this->getQueryFields()[$key];
    }
    if (isset($mappings[$key])) {
      return $this->getQueryFields()[$mappings[$key]];
    }
    return [];
  }

  /**
   * @param $key
   */
  public function setSqlColumnDefn($key) {
    $this->outputSpecification[$this->getMungedFieldName($key)]['sql_columns'] = $this->getSqlColumnDefinition($key, $this->getMungedFieldName($key));
  }

  /**
   * Mark a column as only required for calculations.
   *
   * Do not include the row with headers.
   *
   * @param string $column
   */
  public function setColumnAsCalculationOnly($column) {
    $this->outputSpecification[$column]['do_not_output_to_csv'] = TRUE;
  }

  /**
   * @return array
   */
  public function getHeaderRows() {
    $headerRows = [];
    foreach ($this->outputSpecification as $key => $spec) {
      if (empty($spec['do_not_output_to_csv'])) {
        $headerRows[] = $spec['header'];
      }
    }
    return $headerRows;
  }

  /**
   * @return array
   */
  public function getSQLColumns() {
    $sqlColumns = [];
    foreach ($this->outputSpecification as $key => $spec) {
      if (empty($spec['do_not_output_to_sql'])) {
        $sqlColumns[$key] = $spec['sql_columns'];
      }
    }
    return $sqlColumns;
  }

  /**
   * @return array
   */
  public function getMetadata() {
    $metadata = [];
    foreach ($this->outputSpecification as $key => $spec) {
      $metadata[$key] = $spec['metadata'];
    }
    return $metadata;
  }

  /**
   * Build the row for output.
   *
   * @param \CRM_Contact_BAO_Query $query
   * @param CRM_Core_DAO $iterationDAO
   * @param array $outputColumns
   * @param $metadata
   * @param $paymentDetails
   * @param $addPaymentHeader
   * @param $paymentTableId
   *
   * @return array|bool
   */
  public function buildRow($query, $iterationDAO, $outputColumns, $metadata, $paymentDetails, $addPaymentHeader, $paymentTableId) {
    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');

    $row = [];
    $householdMergeRelationshipType = $this->getHouseholdMergeTypeForRow($iterationDAO->contact_id);
    if ($householdMergeRelationshipType) {
      $householdID = $this->getRelatedHouseholdID($iterationDAO->contact_id, $householdMergeRelationshipType);
      if ($this->isHouseholdExported($householdID)) {
        return FALSE;
      }
      foreach (array_keys($outputColumns) as $column) {
        $row[$column] = $this->getRelationshipValue($householdMergeRelationshipType, $iterationDAO->contact_id, $column);
      }
      $this->markHouseholdExported($householdID);
      return $row;
    }

    $query->convertToPseudoNames($iterationDAO);

    //first loop through output columns so that we return what is required, and in same order.
    foreach ($outputColumns as $field => $value) {
      // add im_provider to $dao object
      if ($field == 'im_provider' && property_exists($iterationDAO, 'provider_id')) {
        $iterationDAO->im_provider = $iterationDAO->provider_id;
      }

      //build row values (data)
      $fieldValue = NULL;
      if (property_exists($iterationDAO, $field)) {
        $fieldValue = $iterationDAO->$field;
        // to get phone type from phone type id
        if ($field == 'phone_type_id' && isset($phoneTypes[$fieldValue])) {
          $fieldValue = $phoneTypes[$fieldValue];
        }
        elseif ($field == 'provider_id' || $field == 'im_provider') {
          $fieldValue = CRM_Utils_Array::value($fieldValue, $imProviders);
        }
        elseif (strstr($field, 'master_id')) {
          $masterAddressId = NULL;
          if (isset($iterationDAO->$field)) {
            $masterAddressId = $iterationDAO->$field;
          }
          // get display name of contact that address is shared.
          $fieldValue = CRM_Contact_BAO_Contact::getMasterDisplayName($masterAddressId);
        }
      }

      if ($this->isRelationshipTypeKey($field)) {
        $this->buildRelationshipFieldsForRow($row, $iterationDAO->contact_id, $value, $field);
      }
      else {
        $row[$field] = $this->getTransformedFieldValue($field, $iterationDAO, $fieldValue, $metadata, $paymentDetails);
      }
    }

    // If specific payment fields have been selected for export, payment
    // data will already be in $row. Otherwise, add payment related
    // information, if appropriate.
    if ($addPaymentHeader) {
      if (!$this->isExportSpecifiedPaymentFields()) {
        $nullContributionDetails = array_fill_keys(array_keys($this->getPaymentHeaders()), NULL);
        if ($this->isExportPaymentFields()) {
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
    if (!empty($row['contact_type']) &&
      $row['contact_type'] == 'Individual' && array_key_exists('organization_name', $row)
    ) {
      $row['organization_name'] = '';
    }
    return $row;
  }

  /**
   * If this row has a household whose details we should use get the relationship type key.
   *
   * @param $contactID
   *
   * @return bool
   */
  public function getHouseholdMergeTypeForRow($contactID) {
    if (!$this->isMergeSameHousehold()) {
      return FALSE;
    }
    foreach ($this->getHouseholdRelationshipTypes() as $relationshipType) {
      if (isset($this->relatedContactValues[$relationshipType][$contactID])) {
        return $relationshipType;
      }
    }
  }

  /**
   * Mark the given household as already exported.
   *
   * @param $householdID
   */
  public function markHouseholdExported($householdID) {
    $this->exportedHouseholds[$householdID] = $householdID;
  }

  /**
   * @param $field
   * @param $iterationDAO
   * @param $fieldValue
   * @param $metadata
   * @param $paymentDetails
   *
   * @return string
   */
  public function getTransformedFieldValue($field, $iterationDAO, $fieldValue, $metadata, $paymentDetails) {

    $i18n = CRM_Core_I18n::singleton();
    if ($field == 'id') {
      return $iterationDAO->contact_id;
      // special case for calculated field
    }
    elseif ($field == 'source_contact_id') {
      return $iterationDAO->contact_id;
    }
    elseif ($field == 'pledge_balance_amount') {
      return $iterationDAO->pledge_amount - $iterationDAO->pledge_total_paid;
      // special case for calculated field
    }
    elseif ($field == 'pledge_next_pay_amount') {
      return $iterationDAO->pledge_next_pay_amount + $iterationDAO->pledge_outstanding_amount;
    }
    elseif (isset($fieldValue) &&
      $fieldValue != ''
    ) {
      //check for custom data
      if ($cfID = CRM_Core_BAO_CustomField::getKeyID($field)) {
        return CRM_Core_BAO_CustomField::displayValue($fieldValue, $cfID);
      }

      elseif (in_array($field, [
        'email_greeting',
        'postal_greeting',
        'addressee',
      ])) {
        //special case for greeting replacement
        $fldValue = "{$field}_display";
        return $iterationDAO->$fldValue;
      }
      else {
        //normal fields with a touch of CRM-3157
        switch ($field) {
          case 'country':
          case 'world_region':
            return $i18n->crm_translate($fieldValue, ['context' => 'country']);

          case 'state_province':
            return $i18n->crm_translate($fieldValue, ['context' => 'province']);

          case 'gender':
          case 'preferred_communication_method':
          case 'preferred_mail_format':
          case 'communication_style':
            return $i18n->crm_translate($fieldValue);

          default:
            if (isset($metadata[$field])) {
              // No I don't know why we do it this way & whether we could
              // make better use of pseudoConstants.
              if (!empty($metadata[$field]['context'])) {
                return $i18n->crm_translate($fieldValue, $metadata[$field]);
              }
              if (!empty($metadata[$field]['pseudoconstant'])) {
                if (!empty($metadata[$field]['bao'])) {
                  return CRM_Core_PseudoConstant::getLabel($metadata[$field]['bao'], $metadata[$field]['name'], $fieldValue);
                }
                // This is not our normal syntax for pseudoconstants but I am a bit loath to
                // call an external function until sure it is not increasing php processing given this
                // may be iterated 100,000 times & we already have the $imProvider var loaded.
                // That can be next refactor...
                // Yes - definitely feeling hatred for this bit of code - I know you will beat me up over it's awfulness
                // but I have to reach a stable point....
                $varName = $metadata[$field]['pseudoconstant']['var'];
                if ($varName === 'imProviders') {
                  return CRM_Core_PseudoConstant::getLabel('CRM_Core_DAO_IM', 'provider_id', $fieldValue);
                }
                if ($varName === 'phoneTypes') {
                  return CRM_Core_PseudoConstant::getLabel('CRM_Core_DAO_Phone', 'phone_type_id', $fieldValue);
                }
              }

            }
            return $fieldValue;
        }
      }
    }
    elseif ($this->isExportSpecifiedPaymentFields() && array_key_exists($field, $this->getcomponentPaymentFields())) {
      $paymentTableId = $this->getPaymentTableID();
      $paymentData = CRM_Utils_Array::value($iterationDAO->$paymentTableId, $paymentDetails);
      $payFieldMapper = [
        'componentPaymentField_total_amount' => 'total_amount',
        'componentPaymentField_contribution_status' => 'contribution_status',
        'componentPaymentField_payment_instrument' => 'pay_instru',
        'componentPaymentField_transaction_id' => 'trxn_id',
        'componentPaymentField_received_date' => 'receive_date',
      ];
      return CRM_Utils_Array::value($payFieldMapper[$field], $paymentData, '');
    }
    else {
      // if field is empty or null
      return '';
    }
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
    return $componentSpecificFields;
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
   * Ideally this should be protected but making it temporarily public helps refactoring..
   *
   * @return array
   */
  public function getComponentPaymentFields() {
    return [
      'componentPaymentField_total_amount' => ts('Total Amount'),
      'componentPaymentField_contribution_status' => ts('Contribution Status'),
      'componentPaymentField_received_date' => ts('Date Received'),
      'componentPaymentField_payment_instrument' => ts('Payment Method'),
      'componentPaymentField_transaction_id' => ts('Transaction ID'),
    ];
  }

  /**
   * Get headers for payment fields.
   *
   * Returns an array of contribution fields when the entity supports payment fields and specific fields
   * are not specified. This is a transitional function for refactoring legacy code.
   */
  public function getPaymentHeaders() {
    if ($this->isExportPaymentFields() && !$this->isExportSpecifiedPaymentFields()) {
      return $this->getcomponentPaymentFields();
    }
    return [];
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
   * Add the main return properties to the household merge properties if needed for merging.
   *
   * If we are using household merge we need to add these to the relationship properties to
   * be retrieved.
   *
   * @param $returnProperties
   */
  public function setHouseholdMergeReturnProperties($returnProperties) {
    foreach ($this->getHouseholdRelationshipTypes() as $householdRelationshipType) {
      $this->relationshipReturnProperties[$householdRelationshipType] = $returnProperties;
    }
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
   * @param string $fieldName
   * @param string $columnName
   *
   * @return mixed
   */
  public function getSqlColumnDefinition($fieldName, $columnName) {

    // early exit for master_id, CRM-12100
    // in the DB it is an ID, but in the export, we retrive the display_name of the master record
    // also for current_employer, CRM-16939
    if ($columnName == 'master_id' || $columnName == 'current_employer') {
      return "$fieldName varchar(128)";
    }

    if (substr($fieldName, -11) == 'campaign_id') {
      // CRM-14398
      return "$fieldName varchar(128)";
    }

    $queryFields = $this->getQueryFields();
    $lookUp = ['prefix_id', 'suffix_id'];
    // set the sql columns
    if (isset($queryFields[$columnName]['type'])) {
      switch ($queryFields[$columnName]['type']) {
        case CRM_Utils_Type::T_INT:
        case CRM_Utils_Type::T_BOOLEAN:
          if (in_array($columnName, $lookUp)) {
            return "$fieldName varchar(255)";
          }
          else {
            return "$fieldName varchar(16)";
          }

        case CRM_Utils_Type::T_STRING:
          if (isset($queryFields[$columnName]['maxlength'])) {
            return "$fieldName varchar({$queryFields[$columnName]['maxlength']})";
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
          if (isset($queryFields[$columnName]['data_type'])) {

            switch ($queryFields[$columnName]['data_type']) {
              case 'String':
                // May be option labels, which could be up to 512 characters
                $length = max(512, CRM_Utils_Array::value('text_length', $queryFields[$columnName]));
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

  /**
   * In order to respect the history of this class we need to index kinda illogically.
   *
   * On the bright side - this stuff is tested within a nano-byte of it's life.
   *
   * e.g '2-a-b_Home-City'
   *
   * @param string $key
   * @param string $relationshipType
   * @param string $locationType
   * @param $entityLabel
   *
   * @return string
   */
  protected function getOutputSpecificationIndex($key, $relationshipType, $locationType, $entityLabel) {
    if ($entityLabel || $key === 'im') {
      // Just cos that's the history...
      if ($key !== 'master_id') {
        $key = $this->getHeaderForRow($key);
      }
    }
    if (!$relationshipType || $key !== 'id') {
      $key = $this->getMungedFieldName($key);
    }
    return $this->getMungedFieldName(
      ($relationshipType ? ($relationshipType . '_') : '')
      . ($locationType ? ($locationType . '_') : '')
      . $key
      . ($entityLabel ? ('_' . $entityLabel) : '')
    );
  }

  /**
   * Get the compiled label for the column.
   *
   * e.g 'Gender', 'Employee Of-Home-city'
   *
   * @param string $key
   * @param string $relationshipType
   * @param string $locationType
   * @param string $entityLabel
   *
   * @return string
   */
  protected function getOutputSpecificationLabel($key, $relationshipType, $locationType, $entityLabel) {
    return ($relationshipType ? $this->getRelationshipTypes()[$relationshipType] . '-' : '')
      . ($locationType ? $locationType . '-' : '')
      . $this->getHeaderForRow($key)
      . ($entityLabel ? '-' . $entityLabel : '');
  }

  /**
   * Get the mysql field name key.
   *
   * This key is locked in by tests but the reasons for the specific conventions -
   * ie. headings are used for keying fields in some cases, are likely
   * accidental rather than deliberate.
   *
   * This key is used for the output sql array.
   *
   * @param string $key
   * @param $relationshipType
   * @param $locationType
   * @param $entityLabel
   *
   * @return string
   */
  protected function getOutputSpecificationFieldKey($key, $relationshipType, $locationType, $entityLabel) {
    if ($entityLabel || $key === 'im') {
      if ($key !== 'state_province' && $key !== 'id') {
        // @todo - test removing this - indexing by $key should be fine...
        $key = $this->getHeaderForRow($key);
      }
    }
    if (!$relationshipType || $key !== 'id') {
      $key = $this->getMungedFieldName($key);
    }
    $fieldKey = $this->getMungedFieldName(
      ($relationshipType ? ($relationshipType . '_') : '')
      . ($locationType ? ($locationType . '_') : '')
      . $key
      . ($entityLabel ? ('_' . $entityLabel) : '')
    );
    return $fieldKey;
  }

  /**
   * Get params for the where criteria.
   *
   * @return mixed
   */
  public function getWhereParams() {
    if (!$this->isPostalableOnly()) {
      return [];
    }
    $params['is_deceased'] = ['is_deceased', '=', 0, CRM_Contact_BAO_Query::MODE_CONTACTS];
    $params['do_not_mail'] = ['do_not_mail', '=', 0, CRM_Contact_BAO_Query::MODE_CONTACTS];
    return $params;
  }

  /**
   * @param $row
   * @param $contactID
   * @param $value
   * @param $field
   */
  protected function buildRelationshipFieldsForRow(&$row, $contactID, $value, $field) {
    foreach (array_keys($value) as $property) {
      if ($property === 'location') {
        // @todo just undo all this nasty location wrangling!
        foreach ($value['location'] as $locationKey => $locationFields) {
          foreach (array_keys($locationFields) as $locationField) {
            $fieldKey = str_replace(' ', '_', $locationKey . '-' . $locationField);
            $row[$field . '_' . $fieldKey] = $this->getRelationshipValue($field, $contactID, $fieldKey);
          }
        }
      }
      else {
        $row[$field . '_' . $property] = $this->getRelationshipValue($field, $contactID, $property);
      }
    }
  }

}
