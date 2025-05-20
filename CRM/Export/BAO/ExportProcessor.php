<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * Should contacts with the same address be merged.
   *
   * @var bool
   */
  protected $isMergeSameAddress = FALSE;

  /**
   * Fields that need to be retrieved for address merge purposes but should not be in output.
   *
   * @var array
   */
  protected $additionalFieldsForSameAddressMerge = [];

  /**
   * Fields used for merging same contacts.
   *
   * @var array
   */
  protected $contactGreetingFields = [];

  /**
   * An array of primary IDs of the entity being exported.
   *
   * @var array
   */
  protected $ids = [];

  /**
   * Greeting options mapping to various greeting ids.
   *
   * This stores the option values for the addressee, postal_greeting & email_greeting
   * option groups.
   *
   * @var array
   */
  protected $greetingOptions = [];

  /**
   * Get additional non-visible fields for address merge purposes.
   *
   * @return array
   */
  public function getAdditionalFieldsForSameAddressMerge(): array {
    return $this->additionalFieldsForSameAddressMerge;
  }

  /**
   * Set additional non-visible fields for address merge purposes.
   */
  public function setAdditionalFieldsForSameAddressMerge() {
    if ($this->isMergeSameAddress) {
      $fields = ['id', 'master_id', 'state_province_id', 'postal_greeting_id', 'addressee_id'];
      foreach ($fields as $index => $field) {
        if (!empty($this->getReturnProperties()[$field])) {
          unset($fields[$index]);
        }
      }
      $this->additionalFieldsForSameAddressMerge = array_fill_keys($fields, 1);
    }
  }

  /**
   * Should contacts with the same address be merged.
   *
   * @return bool
   */
  public function isMergeSameAddress(): bool {
    return $this->isMergeSameAddress;
  }

  /**
   * Set same address is to be merged.
   *
   * @param bool $isMergeSameAddress
   */
  public function setIsMergeSameAddress(bool $isMergeSameAddress) {
    $this->isMergeSameAddress = $isMergeSameAddress;
  }

  /**
   * Additional fields required to export postal fields.
   *
   * @var array
   */
  protected $additionalFieldsForPostalExport = [];

  /**
   * Get additional fields required to do a postal export.
   *
   * @return array
   */
  public function getAdditionalFieldsForPostalExport() {
    return $this->additionalFieldsForPostalExport;
  }

  /**
   * Set additional fields required for a postal export.
   */
  public function setAdditionalFieldsForPostalExport() {
    if ($this->getRequestedFields() && $this->isPostalableOnly()) {
      $fields = ['is_deceased', 'do_not_mail', 'street_address', 'supplemental_address_1'];
      foreach ($fields as $index => $field) {
        if (!empty($this->getReturnProperties()[$field])) {
          unset($fields[$index]);
        }
      }
      $this->additionalFieldsForPostalExport = array_fill_keys($fields, 1);
    }
  }

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
   * @var array
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
   * Contacts to be merged by virtue of their shared address.
   *
   * @var array
   */
  protected $contactsToMerge = [];

  /**
   * Households to skip during export as they will be exported via their relationships anyway.
   *
   * @var array
   */
  protected $householdsToSkip = [];

  /**
   * Additional fields to return.
   *
   * This doesn't make much sense when we have a fields set but search build add it's own onto
   * the 'Primary fields' (all) option.
   *
   * @var array
   */
  protected $additionalRequestedReturnProperties = [];

  /**
   * Get additional return properties.
   *
   * @return array
   */
  public function getAdditionalRequestedReturnProperties() {
    return $this->additionalRequestedReturnProperties;
  }

  /**
   * Set additional return properties.
   *
   * @param array $value
   */
  public function setAdditionalRequestedReturnProperties($value) {
    // fix for CRM-7066
    if (!empty($value['group'])) {
      unset($value['group']);
      $value['groups'] = 1;
    }
    $this->additionalRequestedReturnProperties = $value;
  }

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
   * @var string
   */
  protected $componentTable = '';

  /**
   * @return string
   */
  public function getComponentTable() {
    return $this->componentTable;
  }

  /**
   * Set the component table (if any).
   *
   * @param string $componentTable
   */
  public function setComponentTable($componentTable) {
    $this->componentTable = $componentTable;
  }

  /**
   * Clause from component search.
   *
   * @var string
   */
  protected $componentClause = '';

  /**
   * @return string
   */
  public function getComponentClause() {
    return $this->componentClause;
  }

  /**
   * @param string $componentClause
   */
  public function setComponentClause($componentClause) {
    $this->componentClause = $componentClause;
  }

  /**
   * Name of a temporary table created to hold the results.
   *
   * Current decision making on when to create a temp table is kinda bad so this might change
   * a bit as it is reviewed but basically we need a temp table or similar to calculate merging
   * addresses. Merging households is handled in php. We create a temp table even when we don't need them.
   *
   * @var string
   */
  protected $temporaryTable;

  /**
   * @return string
   */
  public function getTemporaryTable(): string {
    return $this->temporaryTable;
  }

  /**
   * @param string $temporaryTable
   */
  public function setTemporaryTable(string $temporaryTable) {
    $this->temporaryTable = $temporaryTable;
  }

  protected $postalGreetingTemplate;

  /**
   * @return mixed
   */
  public function getPostalGreetingTemplate() {
    return $this->postalGreetingTemplate;
  }

  /**
   * @param mixed $postalGreetingTemplate
   */
  public function setPostalGreetingTemplate($postalGreetingTemplate) {
    $this->postalGreetingTemplate = $postalGreetingTemplate;
  }

  /**
   * @return mixed
   */
  public function getAddresseeGreetingTemplate() {
    return $this->addresseeGreetingTemplate;
  }

  /**
   * @param mixed $addresseeGreetingTemplate
   */
  public function setAddresseeGreetingTemplate($addresseeGreetingTemplate) {
    $this->addresseeGreetingTemplate = $addresseeGreetingTemplate;
  }

  protected $addresseeGreetingTemplate;

  /**
   * CRM_Export_BAO_ExportProcessor constructor.
   *
   * @param int $exportMode
   * @param array|null $requestedFields
   * @param string $queryOperator
   * @param bool $isMergeSameHousehold
   * @param bool $isPostalableOnly
   * @param bool $isMergeSameAddress
   * @param array $formValues
   *   Values from the export options form on contact export. We currently support these keys
   *   - postal_greeting
   *   - postal_other
   *   - addresee_greeting
   *   - addressee_other
   */
  public function __construct($exportMode, $requestedFields, $queryOperator, $isMergeSameHousehold = FALSE, $isPostalableOnly = FALSE, $isMergeSameAddress = FALSE, $formValues = []) {
    $this->setExportMode((int) $exportMode);
    $this->setQueryMode();
    $this->setQueryOperator($queryOperator);
    $this->setRequestedFields($requestedFields);
    $this->setRelationshipTypes();
    $this->setIsMergeSameHousehold($isMergeSameHousehold || $isMergeSameAddress);
    $this->setIsPostalableOnly($isPostalableOnly);
    $this->setIsMergeSameAddress($isMergeSameAddress);
    $this->setReturnProperties($this->determineReturnProperties());
    $this->setAdditionalFieldsForSameAddressMerge();
    $this->setAdditionalFieldsForPostalExport();
    $this->setHouseholdMergeReturnProperties();
    $this->setGreetingStringsForSameAddressMerge($formValues);
    $this->setGreetingOptions();
  }

  /**
   * Set the greeting options, if relevant.
   */
  public function setGreetingOptions() {
    if ($this->isMergeSameAddress()) {
      $this->greetingOptions['addressee'] = CRM_Core_OptionGroup::values('addressee');
      $this->greetingOptions['postal_greeting'] = CRM_Core_OptionGroup::values('postal_greeting');
    }
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
    return empty($this->requestedFields) ? NULL : $this->requestedFields;
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
    return array_merge($this->returnProperties, $this->getAdditionalRequestedReturnProperties(), $this->getAdditionalFieldsForSameAddressMerge(), $this->getAdditionalFieldsForPostalExport());
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
    if ($field === 'id' && $this->isHouseholdMergeRelationshipTypeKey($relationshipType)) {
      $this->householdsToSkip[] = $value;
    }
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
    return $this->relatedContactValues[$relationshipType][$contactID][$field] ?? '';
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
  public function getIds() {
    return $this->ids;
  }

  /**
   * @param array $ids
   */
  public function setIds($ids) {
    $this->ids = $ids;
  }

  /**
   * @return array
   */
  public function getQueryFields() {
    return array_merge(
      $this->queryFields,
      $this->getComponentPaymentFields()
    );
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
    $queryFields['contact_id'] = ['title' => ts('Contact ID'), 'type' => CRM_Utils_Type::T_INT];
    $queryFields['tags']['type'] = CRM_Utils_Type::T_LONGTEXT;
    $queryFields['groups']['type'] = CRM_Utils_Type::T_LONGTEXT;
    $queryFields['notes']['type'] = CRM_Utils_Type::T_LONGTEXT;
    // Set the label to gender for gender_id as we it's ... magic (not in a good way).
    // In other places the query object offers e.g contribution_status & contribution_status_id
    $queryFields['gender_id']['title'] = ts('Gender');
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
  public function getExportMode(): int {
    return $this->exportMode;
  }

  /**
   * @param int $exportMode
   */
  public function setExportMode(int $exportMode) {
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
    if (substr($field, -11) === 'campaign_id') {
      // @todo - set this correctly in the xml rather than here.
      // This will require a generalised handling cleanup
      return ts('Campaign ID');
    }
    if ($this->isMergeSameHousehold() && !$this->isMergeSameAddress() && $field === 'id') {
      // This is weird - even if we are merging households not every contact in the export is a household so this would not be accurate.
      return ts('Household ID');
    }
    elseif (isset($this->getQueryFields()[$field]['title'])) {
      return $this->getQueryFields()[$field]['title'];
    }
    elseif ($this->isExportPaymentFields() && array_key_exists($field, $this->getcomponentPaymentFields())) {
      return $this->getcomponentPaymentFields()[$field]['title'];
    }
    else {
      return $field;
    }
  }

  /**
   * @param $params
   * @param $order
   *
   * @return array
   */
  public function runQuery($params, $order) {
    $returnProperties = $this->getReturnProperties();
    $params = array_merge($params, $this->getWhereParams());

    $query = new CRM_Contact_BAO_Query($params, $returnProperties, NULL,
      FALSE, FALSE, $this->getQueryMode(),
      FALSE, TRUE, TRUE, NULL, $this->getQueryOperator(),
      NULL, TRUE
    );

    //sort by state
    //CRM-15301
    $query->_sort = $order;
    [$select, $from, $where, $having] = $query->query();
    $this->setQueryFields($query->_fields);
    $whereClauses = ['trash_clause' => "contact_a.is_deleted != 1"];
    if ($this->getComponentClause()) {
      $whereClauses[] = $this->getComponentClause();
    }
    elseif ($this->getRequestedFields() && $this->getComponentTable() &&  $this->getComponentTable() !== 'civicrm_contact') {
      $from .= " INNER JOIN " . $this->getComponentTable() . " ctTable ON ctTable.contact_id = contact_a.id ";
    }

    // CRM-13982 - check if is deleted
    foreach ($params as $value) {
      if ($value[0] === 'contact_is_deleted') {
        unset($whereClauses['trash_clause']);
      }
    }

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
        $whereClauses['address'] = '(' . $addressWhere . ')';
      }
    }

    if (empty($where)) {
      $where = 'WHERE ' . implode(' AND ', $whereClauses);
    }
    else {
      $where .= ' AND ' . implode(' AND ', $whereClauses);
    }

    $groupBy = $this->getGroupBy($query);
    $queryString = "$select $from $where $having $groupBy";
    if ($order) {
      // always add contact_a.id to the ORDER clause
      // so the order is deterministic
      //CRM-15301
      if (!str_contains('contact_a.id', $order)) {
        $order .= ", contact_a.id";
      }

      [$field, $dir] = explode(' ', $order, 2);
      $field = trim($field);
      if (!empty($this->getReturnProperties()[$field])) {
        //CRM-15301
        $queryString .= " ORDER BY $order";
      }
    }
    return [$query, $queryString];
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
    $index = $this->getOutputSpecificationIndex($key, $relationshipType, $locationType, $entityTypeID);
    $fieldKey = $this->getOutputSpecificationFieldKey($key, $relationshipType, $locationType, $entityTypeID);

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
   * @param $paymentDetails
   * @param $addPaymentHeader
   *
   * @return array|bool
   */
  public function buildRow($query, $iterationDAO, $outputColumns, $paymentDetails, $addPaymentHeader) {
    $paymentTableId = $this->getPaymentTableID();
    if ($this->isHouseholdToSkip($iterationDAO->contact_id)) {
      return FALSE;
    }
    $imProviders = CRM_Core_DAO_IM::buildOptions('provider_id');

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
        if ($field == 'provider_id' || $field == 'im_provider') {
          $fieldValue = $imProviders[$fieldValue] ?? NULL;
        }
        elseif (str_contains($field, 'master_id')) {
          // @todo - why not just $field === 'master_id'  - what else would it be?
          $masterAddressId = $iterationDAO->$field ?? NULL;
          // get display name of contact that address is shared.
          $fieldValue = CRM_Contact_BAO_Contact::getMasterDisplayName($masterAddressId);
        }
      }

      if ($this->isRelationshipTypeKey($field)) {
        $this->buildRelationshipFieldsForRow($row, $iterationDAO->contact_id, $value, $field);
      }
      else {
        $row[$field] = $this->getTransformedFieldValue($field, $iterationDAO, $fieldValue, $paymentDetails);
      }
    }

    // If specific payment fields have been selected for export, payment
    // data will already be in $row. Otherwise, add payment related
    // information, if appropriate.
    if ($addPaymentHeader) {
      if (!$this->isExportSpecifiedPaymentFields()) {
        $nullContributionDetails = array_fill_keys(array_keys($this->getPaymentHeaders()), NULL);
        if ($this->isExportPaymentFields()) {
          $paymentData = $paymentDetails[$row[$paymentTableId]] ?? NULL;
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
   * @param $paymentDetails
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getTransformedFieldValue($field, $iterationDAO, $fieldValue, $paymentDetails) {

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
        $html_type = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $cfID, 'html_type');

        //need to calculate the link to the file for file custom data
        if ($html_type === 'File' && $fieldValue) {
          $result = civicrm_api3('attachment', 'get', ['return' => ['url'], 'id' => $fieldValue]);
          return $result['values'][$result['id']]['url'];
        }

        // Do not export HTML markup for links
        if ($html_type === 'Link' && $fieldValue) {
          return $fieldValue;
        }

        return CRM_Core_BAO_CustomField::displayValue($fieldValue, $cfID);
      }
      elseif (in_array($field, ['email_greeting', 'postal_greeting', 'addressee'])) {
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
          case 'communication_style':
            return $i18n->crm_translate($fieldValue);

          default:
            $fieldSpec = $this->outputSpecification[$this->getMungedFieldName($field)]['metadata'];
            // No I don't know why we do it this way & whether we could
            // make better use of pseudoConstants.
            if (!empty($fieldSpec['context'])) {
              return $i18n->crm_translate($fieldValue, $fieldSpec);
            }
            if (!empty($fieldSpec['pseudoconstant']) && !empty($fieldSpec['hasLocationType']) && $fieldSpec['name'] !== 'phone_type_id') {
              if (!empty($fieldSpec['bao'])) {
                $transformedValue = CRM_Core_PseudoConstant::getLabel($fieldSpec['bao'], $fieldSpec['name'], $fieldValue);
                if ($transformedValue) {
                  return $transformedValue;
                }
                return $fieldValue;
              }
              // Yes - definitely feeling hatred for this bit of code - I know you will beat me up over it's awfulness
              // but I have to reach a stable point....
              $varName = $fieldSpec['pseudoconstant']['var'];
              if ($varName === 'imProviders') {
                return CRM_Core_PseudoConstant::getLabel('CRM_Core_DAO_IM', 'provider_id', $fieldValue);
              }
            }
            return $fieldValue;
        }
      }
    }
    elseif ($this->isExportSpecifiedPaymentFields() && array_key_exists($field, $this->getcomponentPaymentFields())) {
      $paymentTableId = $this->getPaymentTableID();
      $paymentData = $paymentDetails[$iterationDAO->$paymentTableId] ?? NULL;
      $payFieldMapper = [
        'componentPaymentField_total_amount' => 'total_amount',
        'componentPaymentField_contribution_status' => 'contribution_status',
        'componentPaymentField_payment_instrument' => 'pay_instru',
        'componentPaymentField_transaction_id' => 'trxn_id',
        'componentPaymentField_received_date' => 'receive_date',
      ];
      return $paymentData[$payFieldMapper[$field]] ?? '';
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
      'componentPaymentField_total_amount' => ['title' => ts('Total Amount'), 'type' => CRM_Utils_Type::T_MONEY],
      'componentPaymentField_contribution_status' => ['title' => ts('Contribution Status'), 'type' => CRM_Utils_Type::T_STRING],
      'componentPaymentField_received_date' => ['title' => ts('Contribution Date'), 'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME],
      'componentPaymentField_payment_instrument' => ['title' => ts('Payment Method'), 'type' => CRM_Utils_Type::T_STRING],
      'componentPaymentField_transaction_id' => ['title' => ts('Transaction ID'), 'type' => CRM_Utils_Type::T_STRING],
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
      return CRM_Utils_Array::collect('title', $this->getcomponentPaymentFields());
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
      'notes',
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
    $relationField = $value['name'];
    $relLocTypeId = $value['location_type_id'] ?? NULL;
    $locationName = CRM_Core_PseudoConstant::getName('CRM_Core_BAO_Address', 'location_type_id', $relLocTypeId);
    $relPhoneTypeId = $value['phone_type_id'] ?? ($locationName ? 'Primary' : NULL);
    $relIMProviderId = $value['im_provider_id'] ?? ($locationName ? 'Primary' : NULL);
    if (in_array($relationField, $this->getValidLocationFields()) && $locationName) {
      if ($relationField === 'phone') {
        $this->relationshipReturnProperties[$relationshipKey]['location'][$locationName]['phone-' . $relPhoneTypeId] = 1;
      }
      elseif ($relationField === 'im') {
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
   */
  public function setHouseholdMergeReturnProperties() {
    if ($this->isMergeSameHousehold()) {
      $returnProperties = $this->getReturnProperties();
      $returnProperties = array_diff_key($returnProperties, array_fill_keys(['location_type', 'im_provider'], 1));
      foreach ($this->getHouseholdRelationshipTypes() as $householdRelationshipType) {
        $this->relationshipReturnProperties[$householdRelationshipType] = $returnProperties;
      }
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
    // in the DB it is an ID, but in the export, we retrieve the display_name of the master record
    if ($columnName === 'master_id') {
      return "`$fieldName` varchar(128)";
    }

    $queryFields = $this->getQueryFields();
    // @todo remove the e-notice avoidance here, ensure all columns are declared.
    // tests will fail on the enotices until they all are & then all the 'else'
    // below can go.
    $fieldSpec = $queryFields[$columnName] ?? [];
    $type = $fieldSpec['type'] ?? ($fieldSpec['data_type'] ?? '');
    // set the sql columns
    if ($type) {
      switch ($type) {
        case CRM_Utils_Type::T_INT:
        case CRM_Utils_Type::T_BOOLEAN:
          if (in_array($fieldSpec['data_type'] ?? NULL, ['Country', 'StateProvince', 'ContactReference', 'EntityReference'])) {
            return "`$fieldName` text";
          }
          // some of those will be exported as a (localisable) string
          // @see https://lab.civicrm.org/dev/core/-/issues/2164
          return "`$fieldName` varchar(64)";

        case CRM_Utils_Type::T_STRING:
          if (isset($fieldSpec['maxlength'])) {
            return "`$fieldName` varchar({$fieldSpec['maxlength']})";
          }
          $dataType = $fieldSpec['data_type'] ?? '';
          // set the sql columns for custom data
          switch ($dataType) {
            case 'String':
              // May be option labels, which could be up to 512 characters
              $length = max(512, $fieldSpec['text_length'] ?? 0);
              return "`$fieldName` varchar($length)";

            case 'Memo':
              return "`$fieldName` text";

            default:
              return "`$fieldName` varchar(255)";
          }

        case CRM_Utils_Type::T_TEXT:
        case CRM_Utils_Type::T_LONGTEXT:
        case CRM_Utils_Type::T_BLOB:
        case CRM_Utils_Type::T_MEDIUMBLOB:
          return "`$fieldName` longtext";

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
          return "`$fieldName` varchar(32)";
      }
    }
    else {
      if (substr($fieldName, -3, 3) === '_id') {
        return "`$fieldName` varchar(255)";
      }
      return "`$fieldName` text";
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

  /**
   * Is this contact a household that is already set to be exported by virtue of it's household members.
   *
   * @param int $contactID
   *
   * @return bool
   */
  protected function isHouseholdToSkip($contactID) {
    return in_array($contactID, $this->householdsToSkip);
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
  public function getExportStructureArrays() {
    $outputColumns = [];
    $queryFields = $this->getQueryFields();
    foreach ($this->getReturnProperties() as $key => $value) {
      if (($key != 'location' || !is_array($value)) && !$this->isRelationshipTypeKey($key)) {
        $outputColumns[$key] = $value;
        $this->addOutputSpecification($key);
      }
      elseif ($this->isRelationshipTypeKey($key)) {
        $outputColumns[$key] = $value;
        foreach ($value as $relationField => $relationValue) {
          // below block is same as primary block (duplicate)
          if (isset($queryFields[$relationField]['title'])) {
            $this->addOutputSpecification($relationField, $key);
          }
          elseif (is_array($relationValue) && $relationField == 'location') {
            // fix header for location type case
            foreach ($relationValue as $ltype => $val) {
              foreach (array_keys($val) as $fld) {
                $type = explode('-', $fld);
                $this->addOutputSpecification($type[0], $key, $ltype, $type[1] ?? NULL);
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
            $this->addOutputSpecification($actualDBFieldName, NULL, $locationType, $type[1] ?? NULL);
            $outputColumns[$daoFieldName] = TRUE;
          }
        }
      }
    }
    return [$outputColumns];
  }

  /**
   * Get default return property for export based on mode
   *
   * @return string
   *   Default Return property
   */
  public function defaultReturnProperty() {
    // hack to add default return property based on export mode
    $property = NULL;
    $exportMode = $this->getExportMode();
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
   * Determine the required return properties from the input parameters.
   *
   * @return array
   */
  public function determineReturnProperties() {
    if ($this->getRequestedFields()) {
      $returnProperties = [];
      foreach ($this->getRequestedFields() as $key => $value) {
        $fieldName = $value['name'];
        $locationName = !empty($value['location_type_id']) ? CRM_Core_PseudoConstant::getName('CRM_Core_BAO_Address', 'location_type_id', $value['location_type_id']) : NULL;
        $relationshipTypeKey = !empty($value['relationship_type_id']) ? $value['relationship_type_id'] . '_' . $value['relationship_direction'] : NULL;
        if (!$fieldName || $this->isHouseholdMergeRelationshipTypeKey($relationshipTypeKey)) {
          continue;
        }

        if ($this->isRelationshipTypeKey($relationshipTypeKey)) {
          $returnProperties[$relationshipTypeKey] = $this->setRelationshipReturnProperties($value, $relationshipTypeKey);
        }
        elseif ($locationName) {
          if ($fieldName === 'phone') {
            $returnProperties['location'][$locationName]['phone-' . $value['phone_type_id'] ?? NULL] = 1;
          }
          elseif ($fieldName === 'im') {
            $returnProperties['location'][$locationName]['im-' . $value['im_provider_id'] ?? NULL] = 1;
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
      $defaultExportMode = $this->defaultReturnProperty();
      if ($defaultExportMode) {
        $returnProperties[$defaultExportMode] = 1;
      }
    }
    else {
      $returnProperties = $this->getDefaultReturnProperties();
    }
    if ($this->isMergeSameHousehold()) {
      $returnProperties['id'] = 1;
    }
    if ($this->isMergeSameAddress()) {
      $returnProperties['addressee'] = 1;
      $returnProperties['postal_greeting'] = 1;
      $returnProperties['email_greeting'] = 1;
      $returnProperties['street_name'] = 1;
      $returnProperties['household_name'] = 1;
      $returnProperties['street_address'] = 1;
      $returnProperties['city'] = 1;
      $returnProperties['state_province'] = 1;

    }
    return $returnProperties;
  }

  /**
   * @param object $query
   *   CRM_Contact_BAO_Query
   *
   * @return string
   *   Group By Clause
   */
  public function getGroupBy($query) {
    $groupBy = NULL;
    $returnProperties = $this->getReturnProperties();
    $exportMode = $this->getExportMode();
    $queryMode = $this->getQueryMode();
    if (!empty($returnProperties['tags']) || !empty($returnProperties['groups']) ||
      !empty($returnProperties['notes']) ||
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
   * Replace contact greetings in merged contacts.
   *
   * @param int $contactID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function replaceMergeTokens(int $contactID): array {
    $messageTemplate = [
      'postal_greeting' => $this->getPostalGreetingTemplate() ?? '',
      'addressee' => $this->getAddresseeGreetingTemplate() ?? '',
    ];
    if (array_filter($messageTemplate)) {
      return CRM_Core_TokenSmarty::render($messageTemplate, ['contactId' => $contactID]);
    }
    return $messageTemplate;
  }

  /**
   * Build array for merging same addresses.
   *
   * @param string $sql
   */
  public function buildMasterCopyArray($sql) {

    $parents = [];
    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $masterID = $dao->master_id;
      $copyID = $dao->copy_id;

      $this->cacheContactGreetings((int) $dao->master_contact_id);
      $this->cacheContactGreetings((int) $dao->copy_contact_id);

      if (!isset($this->contactsToMerge[$masterID])) {
        // check if this is an intermediate child
        // this happens if there are 3 or more matches a,b, c
        // the above query will return a, b / a, c / b, c
        // we might be doing a bit more work, but for now its ok, unless someone
        // knows how to fix the query above
        if (isset($parents[$masterID])) {
          $masterID = $parents[$masterID];
        }
        else {
          $this->contactsToMerge[$masterID] = [
            'addressee' => $this->getContactGreeting((int) $dao->master_contact_id, 'addressee', $dao->master_addressee),
            'copy' => [],
            'postalGreeting' => $this->getContactGreeting((int) $dao->master_contact_id, 'postal_greeting', $dao->master_postal_greeting),
          ];
          $this->contactsToMerge[$masterID]['emailGreeting'] = &$this->contactsToMerge[$masterID]['postalGreeting'];
        }
      }
      $parents[$copyID] = $masterID;

      if (!array_key_exists($copyID, $this->contactsToMerge[$masterID]['copy'])) {
        $copyPostalGreeting = $this->getContactPortionOfGreeting((int) $dao->copy_contact_id, (int) $dao->copy_postal_greeting_id, 'postal_greeting', $dao->copy_postal_greeting);
        if ($copyPostalGreeting) {
          $this->contactsToMerge[$masterID]['postalGreeting'] = "{$this->contactsToMerge[$masterID]['postalGreeting']}, {$copyPostalGreeting}";
          // if there happens to be a duplicate, remove it
          $this->contactsToMerge[$masterID]['postalGreeting'] = str_replace(" {$copyPostalGreeting},", "", $this->contactsToMerge[$masterID]['postalGreeting']);
        }

        $copyAddressee = $this->getContactPortionOfGreeting((int) $dao->copy_contact_id, (int) $dao->copy_addressee_id, 'addressee', $dao->copy_addressee);
        if ($copyAddressee) {
          $this->contactsToMerge[$masterID]['addressee'] = "{$this->contactsToMerge[$masterID]['addressee']}, " . trim($copyAddressee);
        }
      }
      if (!isset($this->contactsToMerge[$masterID]['copy'][$copyID])) {
        // If it was set in the first run through - share routine, don't subsequently clobber.
        $this->contactsToMerge[$masterID]['copy'][$copyID] = $copyAddressee ?? $dao->copy_addressee;
      }
    }
  }

  /**
   * Merge contacts with the same address.
   */
  public function mergeSameAddress() {

    $tableName = $this->getTemporaryTable();

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
WHERE ( r1.street_address != '' )
AND       r2.id > r1.id
ORDER BY  r1.id
";
    $this->buildMasterCopyArray($sql);

    foreach ($this->contactsToMerge as $masterID => $values) {
      $sql = "
UPDATE $tableName
SET    addressee = %1, postal_greeting = %2, email_greeting = %3
WHERE  id = %4
";
      $params = [
        1 => [CRM_Utils_String::ellipsify($values['addressee'], 255), 'String'],
        2 => [$values['postalGreeting'], 'String'],
        3 => [$values['emailGreeting'], 'String'],
        4 => [$masterID, 'Integer'],
      ];
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
  }

  /**
   * The function unsets static part of the string, if token is the dynamic part.
   *
   * Example: 'Hello {contact.first_name}' => converted to => '{contact.first_name}'
   * i.e 'Hello Alan' => converted to => 'Alan'
   *
   * @param string $parsedString
   * @param string $defaultGreeting
   * @param string $greetingLabel
   *
   * @return mixed
   */
  public function trimNonTokensFromAddressString(
    &$parsedString, $defaultGreeting,
    $greetingLabel
  ) {
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
   * Preview export output.
   *
   * @param int $limit
   * @return array
   */
  public function getPreview($limit) {
    $rows = [];
    [$outputColumns] = $this->getExportStructureArrays();
    $query = $this->runQuery([], '');
    CRM_Core_DAO::disableFullGroupByMode();
    $result = CRM_Core_DAO::executeQuery($query[1] . ' LIMIT ' . (int) $limit);
    CRM_Core_DAO::reenableFullGroupByMode();
    while ($result->fetch()) {
      $rows[] = $this->buildRow($query[0], $result, $outputColumns, [], []);
    }
    return $rows;
  }

  /**
   * Set the template strings to be used when merging two contacts with the same address.
   *
   * @param array $formValues
   *   Values from first form. In this case we care about the keys
   *   - postal_greeting
   *   - postal_other
   *   - address_greeting
   *   - addressee_other
   *
   * @return mixed
   */
  protected function setGreetingStringsForSameAddressMerge($formValues) {
    $greetingOptions = CRM_Export_Form_Select::getGreetingOptions();

    if (!empty($greetingOptions)) {
      // Greeting options is keyed by 'postal_greeting' or 'addressee'.
      foreach ($greetingOptions as $key => $value) {
        $option = $formValues[$key] ?? NULL;
        if ($option) {
          if ($greetingOptions[$key][$option] == ts('Other')) {
            $formValues[$key] = $formValues["{$key}_other"];
          }
          elseif ($greetingOptions[$key][$option] == ts('List of names')) {
            $formValues[$key] = '';
          }
          else {
            $formValues[$key] = $greetingOptions[$key][$option];
          }
        }
      }
    }
    if (!empty($formValues['postal_greeting'])) {
      $this->setPostalGreetingTemplate($formValues['postal_greeting']);
    }
    if (!empty($formValues['addressee'])) {
      $this->setAddresseeGreetingTemplate($formValues['addressee']);
    }
  }

  /**
   * Create the temporary table for output.
   */
  public function createTempTable() {
    //creating a temporary table for the search result that need be exported
    $exportTempTable = CRM_Utils_SQL_TempTable::build()->setDurable()->setCategory('export');
    $sqlColumns = $this->getSQLColumns();
    // also create the sql table
    $exportTempTable->drop();

    $sql = " id int unsigned NOT NULL AUTO_INCREMENT, ";
    if (!empty($sqlColumns)) {
      $sql .= implode(",\n", array_values($sqlColumns)) . ',';
    }

    $sql .= "\n PRIMARY KEY ( id )";

    // add indexes for street_address and household_name if present
    $addIndices = [
      'street_address',
      'household_name',
      'civicrm_primary_id',
    ];

    foreach ($addIndices as $index) {
      if (isset($sqlColumns[$index])) {
        $sql .= ",
  INDEX index_{$index}( $index )
";
      }
    }

    $exportTempTable->createWithColumns($sql);
    $this->setTemporaryTable($exportTempTable->getName());
  }

  /**
   * Get the values of linked household contact.
   *
   * @param CRM_Core_DAO $relDAO
   * @param array $value
   * @param string $field
   * @param array $row
   *
   * @throws \Exception
   */
  public function fetchRelationshipDetails($relDAO, $value, $field, &$row) {
    $phoneTypes = CRM_Core_DAO_Phone::buildOptions('phone_type_id');
    $imProviders = CRM_Core_DAO_IM::buildOptions('provider_id');
    $i18n = CRM_Core_I18n::singleton();
    $field = $field . '_';

    foreach ($value as $relationField => $relationValue) {
      if (is_object($relDAO) && property_exists($relDAO, $relationField)) {
        $fieldValue = $relDAO->$relationField;
        if ($relationField == 'provider_id') {
          $fieldValue = $imProviders[$relationValue] ?? NULL;
        }
        // CRM-13995
        elseif (is_object($relDAO) &&
          in_array($relationField, ['email_greeting', 'postal_greeting', 'addressee'])
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
                  ['context' => 'country']
                );
                break;

              case in_array('state_province', $type):
                $row[$field . '_' . $fldValue] = $i18n->crm_translate($relDAO->$fldValue,
                  ['context' => 'province']
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
              $row[$relPrefix] = $i18n->crm_translate($fieldValue, ['context' => 'country']);
              break;

            case 'state_province':
              $row[$relPrefix] = $i18n->crm_translate($fieldValue, ['context' => 'province']);
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
   * Write to the csv from the temp table.
   */
  public function writeCSVFromTable() {
    // call export hook
    $headerRows = $this->getHeaderRows();
    $exportTempTable = $this->getTemporaryTable();
    $exportMode = $this->getExportMode();
    $sqlColumns = $this->getSQLColumns();
    $componentTable = $this->getComponentTable();
    $ids = $this->getIds();
    CRM_Utils_Hook::export($exportTempTable, $headerRows, $sqlColumns, $exportMode, $componentTable, $ids);
    if ($exportMode !== $this->getExportMode() || $componentTable !== $this->getComponentTable()) {
      CRM_Core_Error::deprecatedFunctionWarning('altering the export mode and/or component table in the hook is no longer supported.');
    }
    if ($ids !== $this->getIds()) {
      CRM_Core_Error::deprecatedFunctionWarning('altering the ids in the hook is no longer supported.');
    }
    if ($exportTempTable !== $this->getTemporaryTable()) {
      CRM_Core_Error::deprecatedFunctionWarning('altering the export table in the hook is deprecated (in some flows the table itself will be)');
      $this->setTemporaryTable($exportTempTable);
    }
    $exportTempTable = $this->getTemporaryTable();
    $writeHeader = TRUE;
    $offset = 0;
    // increase this number a lot to avoid making too many queries
    // LIMIT is not much faster than a no LIMIT query
    // CRM-7675
    $limit = 100000;

    $query = "SELECT * FROM $exportTempTable";

    $this->instantiateTempTable($headerRows);
    while (1) {
      $limitQuery = $query . "
LIMIT $offset, $limit
";
      $dao = CRM_Core_DAO::executeQuery($limitQuery);

      if ($dao->N <= 0) {
        break;
      }

      $componentDetails = [];
      while ($dao->fetch()) {
        $row = [];

        foreach (array_keys($sqlColumns) as $column) {
          $row[$column] = $dao->$column;
        }
        $componentDetails[] = $row;
      }
      $this->writeRows($headerRows, $componentDetails);

      $offset += $limit;
    }
  }

  /**
   * Set up the temp table.
   *
   * @param array $headerRows
   */
  protected function instantiateTempTable(array $headerRows) {
    CRM_Utils_System::download(CRM_Utils_String::munge($this->getExportFileName()),
      'text/x-csv',
      CRM_Core_DAO::$_nullObject,
      'csv',
      FALSE
    );
    // Output UTF BOM so that MS Excel copes with diacritics. This is recommended as
    // the Windows variant but is tested with MS Excel for Mac (Office 365 v 16.31)
    // and it continues to work on Libre Office, Numbers, Notes etc.
    echo "\xEF\xBB\xBF";
    CRM_Core_Report_Excel::makeCSVTable($headerRows, [], TRUE);
  }

  /**
   * Write rows to the csv.
   *
   * @param array $headerRows
   * @param array $rows
   */
  protected function writeRows(array $headerRows, array $rows) {
    if (!empty($rows)) {
      CRM_Core_Report_Excel::makeCSVTable($headerRows, $rows, FALSE);
    }
  }

  /**
   * Cache the greeting fields for the given contact.
   *
   * @param int $contactID
   */
  protected function cacheContactGreetings(int $contactID) {
    if (!isset($this->contactGreetingFields[$contactID])) {
      $this->contactGreetingFields[$contactID] = $this->replaceMergeTokens($contactID);
    }
  }

  /**
   * Get the greeting value for the given contact.
   *
   * The values have already been cached so we are grabbing the value at this point.
   *
   * @param int $contactID
   * @param string $type
   *   postal_greeting|addressee|email_greeting
   * @param string $default
   *
   * @return string
   */
  protected function getContactGreeting(int $contactID, string $type, string $default): string {
    return empty($this->contactGreetingFields[$contactID][$type]) ? $default : $this->contactGreetingFields[$contactID][$type];
  }

  /**
   * Get the portion of the greeting string that relates to the contact.
   *
   * For example if the greeting id 'Dear Sarah' we are going to combine it with 'Dear Mike'
   * so we want to strip the 'Dear ' and just get 'Sarah
   * @param int $contactID
   * @param int $greetingID
   * @param string $type
   *   postal_greeting, addressee (email_greeting not currently implemented for unknown reasons.
   * @param string $defaultGreeting
   *
   * @return mixed|string
   */
  protected function getContactPortionOfGreeting(int $contactID, int $greetingID, string $type, string $defaultGreeting) {
    $copyPostalGreeting = $this->getContactGreeting($contactID, $type, $defaultGreeting);
    $template = $type === 'postal_greeting' ? $this->getPostalGreetingTemplate() : $this->getAddresseeGreetingTemplate();
    if ($copyPostalGreeting) {
      $copyPostalGreeting = $this->trimNonTokensFromAddressString($copyPostalGreeting,
        $this->greetingOptions[$type][$greetingID],
        $template
      );
    }
    return $copyPostalGreeting;
  }

  /**
   * Get the contribution details for component export.
   *
   * @internal do not call from outside core.
   *
   * @return array
   *   associated array
   */
  public function getContributionDetails() {
    $paymentDetails = [];
    $componentClause = ' IN ( ' . implode(',', $this->ids) . ' ) ';

    if ($this->getExportMode() === CRM_Export_Form_Select::EVENT_EXPORT) {
      $componentSelect = " civicrm_participant_payment.participant_id id";
      $additionalClause = "
INNER JOIN civicrm_participant_payment ON (civicrm_contribution.id = civicrm_participant_payment.contribution_id
AND civicrm_participant_payment.participant_id {$componentClause} )
";
    }
    elseif ($this->getExportMode() === CRM_Export_Form_Select::MEMBER_EXPORT) {
      $componentSelect = " civicrm_membership_payment.membership_id id";
      $additionalClause = "
INNER JOIN civicrm_membership_payment ON (civicrm_contribution.id = civicrm_membership_payment.contribution_id
AND civicrm_membership_payment.membership_id {$componentClause} )
";
    }
    elseif ($this->getExportMode() === CRM_Export_Form_Select::PLEDGE_EXPORT) {
      $componentSelect = " civicrm_pledge_payment.id id";
      $additionalClause = "
INNER JOIN civicrm_pledge_payment ON (civicrm_contribution.id = civicrm_pledge_payment.contribution_id
AND civicrm_pledge_payment.pledge_id {$componentClause} )
";
    }

    $query = " SELECT total_amount, contribution_status.name as status_id, contribution_status.label as status, payment_instrument.name as payment_instrument, receive_date,
                          trxn_id, {$componentSelect}
FROM civicrm_contribution
LEFT JOIN civicrm_option_group option_group_payment_instrument ON ( option_group_payment_instrument.name = 'payment_instrument')
LEFT JOIN civicrm_option_value payment_instrument ON (civicrm_contribution.payment_instrument_id = payment_instrument.value
     AND option_group_payment_instrument.id = payment_instrument.option_group_id )
LEFT JOIN civicrm_option_group option_group_contribution_status ON (option_group_contribution_status.name = 'contribution_status')
LEFT JOIN civicrm_option_value contribution_status ON (civicrm_contribution.contribution_status_id = contribution_status.value
                               AND option_group_contribution_status.id = contribution_status.option_group_id )
{$additionalClause}
";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $paymentDetails[$dao->id] = [
        'total_amount' => $dao->total_amount,
        'contribution_status' => $dao->status,
        'receive_date' => $dao->receive_date,
        'pay_instru' => $dao->payment_instrument,
        'trxn_id' => $dao->trxn_id,
      ];
    }

    return $paymentDetails;
  }

}
