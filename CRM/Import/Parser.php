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

use Civi\Api4\Campaign;
use Civi\Api4\Contact;
use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\Email;
use Civi\Api4\Event;
use Civi\Api4\UserJob;
use Civi\UserJob\UserJobInterface;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * @internal - this class is likely to change and extending it in extensions is not
 * supported.
 */
abstract class CRM_Import_Parser implements UserJobInterface {
  use \Civi\API\EntityLookupTrait;
  use \Civi\UserJob\UserJobTrait;

  /**
   * Return codes
   */
  const VALID = 1, WARNING = 2, ERROR = 4, CONFLICT = 8, STOP = 16, DUPLICATE = 32, MULTIPLE_DUPE = 64, NO_MATCH = 128, UNPARSED_ADDRESS_WARNING = 256, SOFT_CREDIT = 512, SOFT_CREDIT_ERROR = 1024, PLEDGE_PAYMENT = 2048, PLEDGE_PAYMENT_ERROR = 4096;

  /**
   * Codes for duplicate record handling
   */
  const DUPLICATE_SKIP = 1, DUPLICATE_UPDATE = 4, DUPLICATE_FILL = 8, DUPLICATE_NOCHECK = 16;

  /**
   * Potentially ambiguous options.
   *
   * For example 'UT' is a state in more than one country.
   *
   * @var array
   */
  protected $ambiguousOptions = [];

  /**
   * States to country mapping.
   *
   * @var array
   */
  protected $statesByCountry = [];

  /**
   * @var int|null
   */
  protected $siteDefaultCountry = NULL;

  /**
   * Ids of contacts created this iteration.
   *
   * @var array
   */
  protected $createdContacts = [];

  /**
   * Countries that the site is restricted to
   *
   * @var array|false
   */
  private $availableCountries;

  /**
   *
   * @return array
   */
  public function getTrackingFields(): array {
    return [];
  }

  public function getBaseEntity(): string {
    return $this->baseEntity;
  }

  /**
   * Get the relevant datasource object.
   *
   * @return \CRM_Import_DataSource|null
   */
  protected function getDataSourceObject(): ?CRM_Import_DataSource {
    $className = $this->getSubmittedValue('dataSource');
    if ($className) {
      return new $className($this->getUserJobID());
    }
    return NULL;
  }

  /**
   * Get the submitted value, as stored on the user job.
   *
   * @param string $fieldName
   *
   * @return mixed
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function getSubmittedValue(string $fieldName) {
    return $this->getUserJob()['metadata']['submitted_values'][$fieldName] ?? NULL;
  }

  /**
   * Has the import completed.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public function isComplete() :bool {
    return $this->getDataSourceObject()->isCompleted();
  }

  /**
   * @param int $contactID
   * @param string $value
   *
   * @return int|string|bool|null|array
   * @throws \CRM_Core_Exception
   */
  public function getExistingContactValue(int $contactID, string $value): mixed {
    $identifier = 'Contact' . $contactID;
    if (!$this->isDefined($identifier)) {
      $existingContact = Contact::get(FALSE)
        ->addWhere('id', '=', $contactID)
        // Don't auto-filter deleted - people use import to undelete.
        ->addWhere('is_deleted', 'IN', [0, 1])
        ->execute()->first();
      if (empty($existingContact['id'])) {
        throw new CRM_Core_Exception('No contact found for this contact ID:' . $contactID, CRM_Import_Parser::NO_MATCH);
      }
      $this->define('Contact', $identifier, $existingContact);
    }
    return $this->lookup($identifier, $value);
  }

  /**
   * Get configured contact type.
   *
   * @return string
   */
  protected function getContactType(): string {
    return $this->getSubmittedValue('contactType') ?: $this->getContactTypeForEntity('Contact') ?? '';
  }

  /**
   * Get configured contact type.
   *
   * @return string|null
   */
  public function getContactSubType(): ?string {
    if (!$this->_contactSubType) {
      $this->_contactSubType = $this->getSubmittedValue('contactSubType');
    }
    return $this->_contactSubType;
  }

  /**
   * Array of error lines, bounded by MAX_ERROR
   * @var array
   */
  protected $_errors;

  /**
   * Metadata for all available fields, keyed by unique name.
   *
   * This is intended to supercede $_fields which uses a special sauce format which
   * importableFieldsMetadata uses the standard getfields type format.
   *
   * @var array
   */
  protected $importableFieldsMetadata = [];

  /**
   * Get metadata for all importable fields in std getfields style format.
   *
   * @return array
   */
  public function getImportableFieldsMetadata(): array {
    return $this->importableFieldsMetadata;
  }

  /**
   * Set metadata for all importable fields in std getfields style format.
   *
   * @param array $importableFieldsMetadata
   */
  public function setImportableFieldsMetadata(array $importableFieldsMetadata): void {
    $this->importableFieldsMetadata = $importableFieldsMetadata;
  }

  /**
   * @param string $entity
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getImportFieldsForEntity(string $entity): array {
    return (array) civicrm_api4($entity, 'getFields', [
      'where' => [['usage', 'CONTAINS', 'import']],
      'orderBy' => ['title'],
      'action' => 'save',
    ])->indexBy('name');
  }

  /**
   * Gets the fields available for importing in a key-name, title format.
   *
   * @return array
   *   eg. ['first_name' => 'First Name'.....]
   *
   * @throws \CRM_Core_Exception
   *
   * @todo - we are constructing the metadata before we
   * have set the contact type so we re-do it here.
   *
   * Once we have cleaned up the way the mapper is handled
   * we can ditch all the existing _construct parameters in favour
   * of just the userJobID - there are current open PRs towards this end.
   *
   * @deprecated
   */
  public function getAvailableFields(): array {
    $this->setFieldMetadata();
    $return = [];
    foreach ($this->getImportableFieldsMetadata() as $name => $field) {
      if ($name === 'id' && $this->isSkipDuplicates()) {
        // Duplicates are being skipped so id matching is not available.
        continue;
      }
      $return[$name] = $field['html']['label'] ?? $field['title'];
    }
    return $return;
  }

  /**
   * Did the user specify duplicates should be skipped and not imported.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  protected function isSkipDuplicates(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_SKIP;
  }

  /**
   * Is this a case where the user has opted to update existing contacts.
   *
   * @return bool
   */
  protected function isUpdateExisting(): bool {
    if (isset($this->getUserJob()['metadata']['entity_configuration'][$this->getBaseEntity()]['action'])) {
      return $this->getUserJob()['metadata']['entity_configuration'][$this->getBaseEntity()]['action'] === 'update';
    }

    return in_array((int) $this->getSubmittedValue('onDuplicate'), [
      CRM_Import_Parser::DUPLICATE_UPDATE,
      CRM_Import_Parser::DUPLICATE_FILL,
    ], TRUE);
  }

  /**
   * Did the user specify duplicates checking should be skipped, resulting in possible duplicate contacts.
   *
   * Note we still need to check for external_identifier as it will hard-fail
   * if we duplicate.
   *
   * @return bool
   */
  protected function isIgnoreDuplicates(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_NOCHECK;
  }

  /**
   * Did the user specify duplicates should be filled with missing data.
   *
   * @return bool
   */
  protected function isFillDuplicates(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_FILL;
  }

  /**
   * Contact type
   *
   * @var string
   */
  public $_contactType;

  /**
   * @param string $contactType
   *
   * @return CRM_Import_Parser
   */
  public function setContactType(string $contactType): CRM_Import_Parser {
    $this->_contactType = $contactType;
    return $this;
  }

  /**
   * Contact sub-type
   *
   * @var int|null
   */
  public $_contactSubType;

  /**
   * @param int|null $contactSubType
   *
   * @return self
   */
  public function setContactSubType(?int $contactSubType): self {
    $this->_contactSubType = $contactSubType;
    return $this;
  }

  /**
   * Get an array of available fields that support location types (e.g phone, street_address etc).
   *
   * @return array
   */
  public function getFieldsWhichSupportLocationTypes(): array {
    $values = [];
    // This is only called from the MapField form in isolation now,
    foreach ($this->getFieldsMetadata() as $name => $field) {
      if (isset($field['hasLocationType'])) {
        $values[$name] = TRUE;
      }
    }
    return $values;
  }

  /**
   * Validate that we have the required fields to create the contact or find it to update.
   *
   * Note that the users duplicate selection affects this as follows
   * - if they did not select an update variant then the id field is not
   *   permitted in the mapping - so we can assume the presence of id means
   *   we should use it
   * - the external_identifier field is valid in place of the other fields
   *   when they have chosen update or fill - in this case we are only looking
   *   to update an existing contact.
   *
   * @param string $contactType
   * @param array $params
   * @param bool $isPermitExistingMatchFields
   *   True if the it is enough to have fields which will enable us to find
   *   an existing contact (eg. external_identifier).
   * @param string $prefixString
   *   String to include in the exception (e.g '(Child of)' if we are validating
   *   a related contact.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  protected function validateRequiredContactFields(string $contactType, array $params, bool $isPermitExistingMatchFields = TRUE, $prefixString = ''): void {
    if (!empty($params['id'])) {
      return;
    }
    $requiredFields = $this->getRequiredFieldsContactCreate()[$contactType];
    if ($isPermitExistingMatchFields) {
      // Historically just an email has been accepted as it is 'usually good enough'
      // for a dedupe rule look up - but really this is a stand in for
      // whatever is needed to find an existing matching contact using the
      // specified dedupe rule (or the default Unsupervised if not specified).
      $requiredFields = $contactType === 'Individual' ? [[$requiredFields, 'external_identifier']] : [[$requiredFields, 'email', 'external_identifier']];
    }
    $this->validateRequiredFields($requiredFields, $params, '', $prefixString);
  }

  /**
   * Get the fields required for contact create.
   *
   * @return array
   */
  protected function getRequiredFieldsContactMatch(): array {
    return [['id', 'external_identifier']];
  }

  /**
   * Get the fields required for contact create.
   *
   * @return array
   */
  protected function getRequiredFieldsContactCreate(): array {
    return [
      'Individual' => [
        [
          ['first_name', 'last_name'],
          'email',
        ],
      ],
      'Organization' => ['organization_name'],
      'Household' => ['household_name'],
    ];
  }

  /**
   * Core function - do not call from outside core.
   *
   * @internal
   */
  public function doPostImportActions() {
    $userJob = $this->getUserJob();
    $summaryInfo = $userJob['metadata']['summary_info'] ?? [];
    $actions = $userJob['metadata']['post_actions'] ?? [];
    if (!empty($actions['group'])) {
      $groupAdditions = $this->addImportedContactsToNewGroup($this->createdContacts, $actions['group']);
      foreach ($actions['group'] as $groupID) {
        $summaryInfo['groups'][$groupID]['added'] += $groupAdditions[$groupID]['added'];
        $summaryInfo['groups'][$groupID]['notAdded'] += $groupAdditions[$groupID]['notAdded'];
      }
    }
    if (!empty($actions['tag'])) {
      $tagAdditions = $this->tagImportedContactsWithNewTag($this->createdContacts, $actions['tag']);
      foreach ($actions['tag'] as $tagID) {
        $summaryInfo['tags'][$tagID]['added'] += $tagAdditions[$tagID]['added'];
        $summaryInfo['tags'][$tagID]['notAdded'] += $tagAdditions[$tagID]['notAdded'];
      }
    }

    $this->userJob['metadata']['summary_info'] = $summaryInfo;
    UserJob::update(FALSE)->addWhere('id', '=', $userJob['id'])->setValues(['metadata' => $this->userJob['metadata']])->execute();
  }

  /**
   * Queue the user job as one or more tasks.
   *
   * @throws \CRM_Core_Exception
   */
  public function queue(): void {
    $dataSource = $this->getDataSourceObject();
    $totalRowCount = $totalRows = $dataSource->getRowCount(['new']);
    // The retry limit for the queue is set to 5 - allowing for a few deadlocks but we might consider
    // making this configurable at some point.
    $queue = Civi::queue('user_job_' . $this->getUserJobID(), ['type' => 'Sql', 'error' => 'abort', 'runner' => 'task', 'user_job_id' => $this->getUserJobID(), 'retry_limit' => 5]);
    UserJob::update(FALSE)
      ->setValues([
        'queue_id.name' => 'user_job_' . $this->getUserJobID(),
        'status_id:name' => 'scheduled',
      ])->addWhere('id', '=', $this->getUserJobID())->execute();
    $offset = 0;
    $batchSize = Civi::settings()->get('import_batch_size');
    while ($totalRows > 0) {
      if ($totalRows < $batchSize) {
        $batchSize = $totalRows;
      }
      $task = new CRM_Queue_Task(
        [get_class($this), 'runJob'],
        // Offset is unused by our import classes, but required by the interface.
        ['userJobID' => $this->getUserJobID(), 'limit' => $batchSize, 'offset' => 0],
        ts('Processed %1 rows out of %2', [1 => $offset + $batchSize, 2 => $totalRowCount])
      );
      $task->runAs = ['contactId' => CRM_Core_Session::getLoggedInContactID(), 'domainId' => CRM_Core_Config::domainID()];
      $queue->createItem($task);
      $totalRows -= $batchSize;
      $offset += $batchSize;
    }

  }

  /**
   * Add imported contacts to groups.
   *
   * @param array $contactIDs
   * @param array $groups
   *
   * @return array
   */
  private function addImportedContactsToNewGroup(array $contactIDs, array $groups): array {
    $groupAdditions = [];
    foreach ($groups as $groupID) {
      // @todo - this function has been in use historically but it does not seem
      // to add much efficiency of get + create api calls
      // and it doesn't give enough control over cache flushing for smaller batches.
      // Note that the import updates a lot of enities & checking & updating the group
      // shouldn't add much performance wise. However, cache flushing will
      $addCount = CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs, $groupID);
      $groupAdditions[$groupID] = [
        'added' => (int) $addCount[1],
        'notAdded' => (int) $addCount[2],
      ];
    }
    return $groupAdditions;
  }

  /**
   * Tag imported contacts.
   *
   * @param array $contactIDs
   * @param array $tags
   *
   * @return array
   */
  private function tagImportedContactsWithNewTag(array $contactIDs, array $tags) {
    $tagAdditions = [];
    foreach ($tags as $tagID) {
      // @todo - this function has been in use historically but it does not seem
      // to add much efficiency of get + create api calls
      // and it doesn't give enough control over cache flushing for smaller batches.
      // Note that the import updates a lot of enities & checking & updating the group
      // shouldn't add much performance wise. However, cache flushing will
      $outcome = CRM_Core_BAO_EntityTag::addEntitiesToTag($contactIDs, $tagID, 'civicrm_contact', FALSE);
      $tagAdditions[$tagID] = ['added' => $outcome[1], 'notAdded' => $outcome[2]];
    }
    return $tagAdditions;
  }

  /**
   * Validate that a passed in contact ID is for an existing, not-deleted contact.
   *
   * @param int $contactID
   * @param string|null $contactType
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateContactID(int $contactID, ?string $contactType): void {
    if ($contactType && $this->getExistingContactValue($contactID, 'contact_type') !== $contactType) {
      throw new CRM_Core_Exception('Mismatched contact Types', CRM_Import_Parser::NO_MATCH);
    }
  }

  /**
   * Determines the file name based on error code.
   *
   * @deprecated
   *
   * @var int $type code constant
   * @return string
   */
  public static function saveFileName($type) {
    $fileName = NULL;
    if (empty($type)) {
      return $fileName;
    }
    switch ($type) {
      case self::ERROR:
        $fileName = 'Import_Errors.csv';
        break;

      case self::DUPLICATE:
        $fileName = 'Import_Duplicates.csv';
        break;

      case self::NO_MATCH:
        $fileName = 'Import_Mismatch.csv';
        break;

      case self::UNPARSED_ADDRESS_WARNING:
        $fileName = 'Import_Unparsed_Address.csv';
        break;
    }

    return $fileName;
  }

  /**
   * Get the default dedupe rule name for the contact type.
   *
   * @param string $contactType
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getDefaultRuleForContactType(string $contactType): string {
    return DedupeRuleGroup::get(FALSE)
      ->addWhere('contact_type', '=', $contactType)
      ->addWhere('used', '=', 'Unsupervised')
      ->addSelect('id', 'name')->execute()->first()['name'];
  }

  /**
   * Validate that the field requirements are met in the params.
   *
   * @param array $requiredFields
   * @param array $params
   *   An array of required fields (fieldName => label)
   *   - note this follows the and / or array nesting we see in permission checks
   *   eg.
   *   [
   *     'email',
   *     ['first_name', 'last_name']
   *   ]
   *   Means 'email' OR 'first_name AND 'last_name'.
   * @param string $entityName
   *   Entity name, if the entity is prefixed in the `getAvailableFields()` array
   *   - we are working towards this being required.
   * @param string $prefixString
   *
   * @throws \CRM_Core_Exception Exception thrown if field requirements are not met.
   */
  protected function validateRequiredFields(array $requiredFields, array $params, string $entityName = '', string $prefixString = ''): void {
    if ($entityName) {
      // @todo - make entityName required once all fields are prefixed.
      $params = CRM_Utils_Array::prefixKeys($params, "$entityName.");
    }
    $missingFields = $this->getMissingFields($requiredFields, $params);
    if (empty($missingFields)) {
      return;
    }
    throw new CRM_Core_Exception($prefixString . ts('Missing required fields:') . ' ' . implode(' ' . ts('OR') . ' ', $missingFields));
  }

  /**
   * Get the import action for the given entity.
   *
   * @param string $entity
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getActionForEntity(string $entity): string {
    return $this->getUserJob()['metadata']['entity_configuration'][$entity]['action'] ?? ($this->getImportEntities()[$entity]['default_action'] ?? 'select');
  }

  /**
   * Get the dedupe rule/s to use for the given entity.
   *
   * If none are returned then the code will use a default 'Unsupervised' rule in `getContactID`
   *
   * @param string $entity
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getDedupeRulesForEntity(string $entity): array {
    return (array) ($this->getUserJob()['metadata']['entity_configuration'][$entity]['dedupe_rule'] ?? []);
  }

  /**
   * Get the import action for the given entity.
   *
   * @param string $entity
   *
   * @return string|null
   * @throws \CRM_Core_Exception
   */
  protected function getContactTypeForEntity(string $entity): ?string {
    return $this->getUserJob()['metadata']['entity_configuration'][$entity]['contact_type'] ?? NULL;
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return array
   */
  public function getRequiredFieldsForEntity(string $entity, string $action): array {
    $entityMetadata = $this->getAvailableImportEntities()[$entity];
    if ($action === 'select') {
      // Select uses the same lookup as update.
      $action = 'update';
    }
    if (isset($entityMetadata['required_fields_' . $action])) {
      return $entityMetadata['required_fields_' . $action];
    }
    return [];
  }

  /**
   * Get the field requirements that are missing from the params array.
   *
   *  Eg Must have 'total_amount' and 'financial_type_id'
   *    [
   *      'total_amount',
   *      'financial_type_id'
   *    ]
   *
   * Eg Must have 'invoice_id' or 'trxn_id' or 'id'
   *
   *   [
   *     ['invoice_id'],
   *     ['trxn_id'],
   *     ['id']
   *   ],
   *
   * Eg Must have 'invoice_id' or 'trxn_id' or 'id' OR (total_amount AND financial_type_id)
   *   [
   *     [['invoice_id'], ['trxn_id'], ['id']]],
   *     ['total_amount', 'financial_type_id]
   *   ],
   *
   * Eg Must have 'invoice_id' or 'trxn_id' or 'id' AND (total_amount AND financial_type_id)
   *   [
   *     [['invoice_id'], ['trxn_id'], ['id']],
   *     ['total_amount', 'financial_type_id]
   *   ]
   *
   * @param array $requiredFields
   * @param array $params
   *
   * @return array
   */
  protected function getMissingFields(array $requiredFields, array $params): array {
    if (empty($requiredFields)) {
      return [];
    }
    return $this->checkRequirement($requiredFields, $params);
  }

  /**
   * Check an individual required fields criteria.
   *
   * @see getMissingFields
   *
   * @param string|array $requirement
   * @param array $params
   *
   * @return array
   */
  private function checkRequirement($requirement, array $params): array {
    $missing = [];
    if (!is_array($requirement)) {
      // In this case we need to match the field....
      // if we do, then return empty, otherwise return
      if (isset($this->baseEntity)) {
        $value = $params[$this->baseEntity][$requirement] ?? $params[$requirement] ?? NULL;
      }
      else {
        $value = $params[$requirement] ?? NULL;
      }

      if ($value) {
        if (!is_array($value)) {
          return [];
        }
        // Recurse the array looking for the key - eg. look for email
        // in a location values array
        foreach ($value as $locationValues) {
          if (!empty($locationValues[$requirement])) {
            return [];
          }
        }
      }
      return [$requirement => $this->getFieldMetadata($requirement)['title']];
    }

    foreach ($requirement as $required) {
      $isOrOperator = isset($requirement[0]) && is_array($requirement[0]);
      $check = $this->checkRequirement($required, $params);
      // A nested array is an 'OR' If we find any one then return.
      if ($isOrOperator && empty($check)) {
        return [];
      }
      $missing = array_merge($missing, $check);
    }
    if (!empty($missing)) {
      $separator = ' ' . ($isOrOperator ? ts('OR') : ts('and')) . ' ';
      return [implode($separator, $missing)];
    }
    return [];
  }

  /**
   * Get the field value, transformed by metadata.
   *
   * @param string $fieldName
   * @param string|int $importedValue
   *   Value as it came in from the datasource.
   *
   * @return string|array|bool|int
   * @throws \CRM_Core_Exception
   */
  protected function getTransformedFieldValue(string $fieldName, $importedValue) {
    if (empty($importedValue)) {
      return $importedValue;
    }
    $fieldMetadata = $this->getFieldMetadata($fieldName);
    if (!empty($fieldMetadata['serialize']) && count(explode(',', $importedValue)) > 1) {
      $values = [];
      foreach (explode(',', $importedValue) as $value) {
        $values[] = $this->getTransformedFieldValue($fieldName, trim($value));
      }
      return $values;
    }
    if ($fieldName === 'url') {
      return CRM_Utils_Rule::url($importedValue) ? $importedValue : 'invalid_import_value';
    }

    if ($fieldName === 'email') {
      return CRM_Utils_Rule::email($importedValue) ? $importedValue : 'invalid_import_value';
    }

    // DataType is defined on apiv4 metadata - ie what we are moving to.
    $typeMap = [
      CRM_Utils_Type::T_FLOAT => 'Float',
      CRM_Utils_Type::T_MONEY => 'Money',
      CRM_Utils_Type::T_BOOLEAN => 'Boolean',
      CRM_Utils_Type::T_DATE => 'Date',
      (CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME) => 'Timestamp',
      CRM_Utils_Type::T_TIMESTAMP => 'Timestamp',
      CRM_Utils_Type::T_INT => 'Integer',
      CRM_Utils_Type::T_TEXT => 'String',
      CRM_Utils_Type::T_STRING => 'String',
    ];
    $dataType = $fieldMetadata['data_type'] ?? $typeMap[$fieldMetadata['type']];

    if ($dataType === 'Float') {
      return CRM_Utils_Rule::numeric($importedValue) ? $importedValue : 'invalid_import_value';
    }
    if ($dataType === 'Money') {
      return CRM_Utils_Rule::money($importedValue, TRUE) ? CRM_Utils_Rule::cleanMoney($importedValue) : 'invalid_import_value';
    }
    if ($dataType === 'Boolean') {
      $value = CRM_Utils_String::strtoboolstr($importedValue);
      if ($value !== FALSE) {
        return (int) $value;
      }
      return 'invalid_import_value';
    }
    if (in_array($dataType, ['Date', 'Timestamp'], TRUE)) {
      $value = CRM_Utils_Date::formatDate($importedValue, (int) $this->getUserJob()['metadata']['import_options']['date_format']);
      return $value ?: 'invalid_import_value';
    }
    $options = $this->getFieldOptions($fieldName);
    if ($options !== FALSE) {
      if ($this->isAmbiguous($fieldName, $importedValue)) {
        // We can't transform it at this stage. Perhaps later we can with
        // other information such as country.
        return $importedValue;
      }

      $comparisonValue = $this->getComparisonValue($importedValue);
      $resolvedValue = $options[$comparisonValue] ?? 'invalid_import_value';
      if (in_array($fieldName, ['state_province_id', 'county_id'], TRUE) && $resolvedValue === 'invalid_import_value') {
        if ($fieldName === 'state_province_id') {
          $stateID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_state_province WHERE name = %1', [1 => [$comparisonValue, 'String']]);
          if (!$stateID) {
            $stateID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_state_province WHERE abbreviation = %1', [1 => [$comparisonValue, 'String']]);
          }
          if ($stateID) {
            $this->importableFieldsMetadata['state_province_id']['options'][$comparisonValue] = $stateID;
            return $stateID;
          }
        }
        if ($fieldName === 'county_id') {
          $countyID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_county WHERE name = %1', [1 => [$comparisonValue, 'String']]);
          if (!$countyID) {
            $countyID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_county WHERE abbreviation = %1', [1 => [$comparisonValue, 'String']]);
          }
          if ($countyID) {
            $this->importableFieldsMetadata['county_id']['options'][$comparisonValue] = $countyID;
            return $countyID;
          }
        }
      }
      return $resolvedValue;
    }
    // @todo - make this generic - for fields where getOptions doesn't fetch
    // getOptions does not retrieve these fields with high potential results
    if ($fieldMetadata['name'] === 'event_id' && $fieldMetadata['fk_entity'] === 'Event') {
      if (!isset(Civi::$statics[__CLASS__][$fieldName][$importedValue])) {
        $event = Event::get()->addClause('OR', ['title', '=', $importedValue], ['id', '=', $importedValue])->addSelect('id')->execute()->first();
        Civi::$statics[__CLASS__][$fieldName][$importedValue] = $event['id'] ?? FALSE;
      }
      return Civi::$statics[__CLASS__][$fieldName][$importedValue] ?? 'invalid_import_value';
    }
    if ($fieldMetadata['name'] === 'campaign_id') {
      if (!isset(Civi::$statics[__CLASS__][$fieldName][$importedValue])) {
        $campaign = Campaign::get()->addClause('OR', ['title', '=', $importedValue], ['name', '=', $importedValue], ['id', '=', $importedValue])->addSelect('id')->execute()->first();
        Civi::$statics[__CLASS__][$fieldName][$importedValue] = $campaign['id'] ?? FALSE;
      }
      return Civi::$statics[__CLASS__][$fieldName][$importedValue] ?: 'invalid_import_value';
    }
    if ($dataType === 'Integer') {
      // We have resolved the options now so any remaining ones should be integers.
      return CRM_Utils_Rule::numeric($importedValue) ? (int) $importedValue : 'invalid_import_value';
    }
    return $importedValue;
  }

  /**
   * @param string $fieldName
   *
   * @return false|array
   *
   */
  protected function getFieldOptions(string $fieldName) {
    return $this->getFieldMetadata($fieldName, TRUE)['options'];
  }

  /**
   * Get the metadata for the field.
   *
   * @param string $fieldName
   * @param bool $loadOptions
   * @param bool $limitToContactType
   *   Only show fields for the type to import (not appropriate when looking up
   *   related contact fields).
   *
   * @return array
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function getFieldMetadata(string $fieldName, bool $loadOptions = FALSE, $limitToContactType = FALSE): array {

    $fieldMap = $this->getOddlyMappedMetadataFields();
    $fieldMapName = empty($fieldMap[$fieldName]) ? $fieldName : $fieldMap[$fieldName];
    // This whole business of only loading metadata for one contact type when we actually need it for all is ... dubious.
    if (empty($this->getImportableFieldsMetadata()[$fieldMapName])) {
      if ($loadOptions || !$limitToContactType) {
        $this->importableFieldsMetadata[$fieldMapName] = CRM_Contact_BAO_Contact::importableFields('All')[$fieldMapName];
      }
    }

    $fieldMetadata = $this->getImportableFieldsMetadata()[$fieldMapName];
    if ($loadOptions && (!isset($fieldMetadata['options']) || $fieldMetadata['options'] === TRUE)) {
      if (($fieldMetadata['data_type'] ?? '') === 'StateProvince') {
        // Probably already loaded and also supports abbreviations - eg. NSW.
        // Supporting for core AND custom state fields is more consistent.
        $this->importableFieldsMetadata[$fieldMapName]['options'] = $this->getFieldOptions('state_province_id');
        return $this->importableFieldsMetadata[$fieldMapName];
      }
      if (($fieldMetadata['data_type'] ?? '') === 'Country') {
        // Probably already loaded and also supports abbreviations - eg. NSW.
        // Supporting for core AND custom state fields is more consistent.
        $this->importableFieldsMetadata[$fieldMapName]['options'] = $this->getFieldOptions('country_id');
        return $this->importableFieldsMetadata[$fieldMapName];
      }
      $optionFieldName = empty($fieldMap[$fieldName]) ? $fieldMetadata['name'] : $fieldName;

      if (!empty($fieldMetadata['custom_field_id']) && !empty($fieldMetadata['is_multiple'])) {
        $options = civicrm_api4('Custom_' . $fieldMetadata['custom_group_id.name'], 'getFields', [
          'loadOptions' => ['id', 'name', 'label', 'abbr'],
          'where' => [['custom_field_id', '=', $fieldMetadata['custom_field_id']]],
          'select' => ['options'],
        ])->first()['options'];
      }
      else {
        if (!empty($fieldMetadata['custom_group_id'])) {
          $customField = CRM_Core_BAO_CustomField::getField($fieldMetadata['custom_field_id']);
          $optionFieldName = $customField['custom_group']['name'] . '.' . $customField['name'];
        }
        $options = civicrm_api4($this->getFieldEntity($fieldName), 'getFields', [
          'loadOptions' => ['id', 'name', 'label', 'abbr'],
          'where' => [['name', '=', $optionFieldName]],
          'select' => ['options'],
        ])->first()['options'];
      }
      if (is_array($options)) {
        // We create an array of the possible variants - notably including
        // name AND label as either might be used. We also lower case before checking
        $values = [];
        foreach ($options as $option) {
          $idKey = $this->getComparisonValue($option['id']);
          $values[$idKey] = $option['id'];
          foreach (['name', 'label', 'abbr'] as $key) {
            $optionValue = $this->getComparisonValue($option[$key] ?? '');
            if ($optionValue !== '') {
              if (isset($values[$optionValue]) && $values[$optionValue] !== $option['id']) {
                if (!isset($this->ambiguousOptions[$fieldName][$optionValue])) {
                  $this->ambiguousOptions[$fieldName][$optionValue] = [$values[$optionValue]];
                }
                $this->ambiguousOptions[$fieldName][$optionValue][] = $option['id'];
              }
              else {
                $values[$optionValue] = $option['id'];
              }
            }
          }
        }
        $this->importableFieldsMetadata[$fieldMapName]['options'] = $values;
      }
      else {
        $this->importableFieldsMetadata[$fieldMapName]['options'] = $options ?: FALSE;
      }
      return $this->importableFieldsMetadata[$fieldMapName];
    }
    return $fieldMetadata;
  }

  /**
   * Get the entity for the given field.
   *
   * @param string $fieldName
   *
   * @return mixed|null
   */
  protected function getFieldEntity(string $fieldName) {
    if ($fieldName === 'do_not_import' || $fieldName === '') {
      return '';
    }
    if (in_array($fieldName, ['email_greeting_id', 'postal_greeting_id', 'addressee_id'], TRUE)) {
      return 'Contact';
    }
    $metadata = $this->getFieldMetadata($fieldName);
    if (!isset($metadata['entity'])) {
      return in_array($metadata['extends'], ['Individual', 'Organization', 'Household'], TRUE) ? 'Contact' : $metadata['extends'];
    }

    // Our metadata for these is fugly. Handling the fugliness during retrieval.
    if (in_array($metadata['entity'], ['Country', 'StateProvince', 'County'], TRUE)) {
      return 'Address';
    }
    return $metadata['entity'];
  }

  /**
   * Validate the import file, updating the import table with results.
   *
   * @throws \CRM_Core_Exception
   */
  public function validate(): void {
    $dataSource = $this->getDataSourceObject();
    $dataSource->setStatuses(['unimported']);
    while ($row = $dataSource->getRow()) {
      $this->validateRow($row);
    }
    $dataSource->setStatuses([]);
  }

  /**
   * Validate the import values.
   *
   * The values array represents a row in the datasource.
   *
   * @param array $values
   *
   * @throws \CRM_Core_Exception
   */
  public function validateValues(array $values): void {
    $params = $this->getMappedRow($values);
    $this->validateParams($params);
  }

  /**
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateParams(array $params): void {
    if (empty($params['id']) && empty($params[$this->getBaseEntity()]['id'])) {
      $entityConfiguration = $this->getAvailableImportEntities()[$this->getBaseEntity()];
      $entity = $entityConfiguration['entity_name'] ?? '';
      $this->validateRequiredFields($this->getRequiredFields(), $params[$this->getBaseEntity()] ?? $params, $entity);
    }
    $errors = [];
    foreach ($params as $key => $value) {
      $errors = array_merge($this->getInvalidValues($value, $key), $errors);
    }
    if ($errors) {
      throw new CRM_Core_Exception('Invalid value for field(s) : ' . implode(',', $errors));
    }
  }

  /**
   * Search the value for the string 'invalid_import_value'.
   *
   * If the string is found it indicates the fields was rejected
   * during `getTransformedValue` as not having valid data.
   *
   * @param string|array|int $value
   * @param string $key
   * @param string $prefixString
   *
   * @return array
   */
  protected function getInvalidValues($value, string $key = '', string $prefixString = ''): array {
    $errors = [];
    if ($value === 'invalid_import_value') {
      if (!is_numeric($key)) {
        if (!isset($this->importableFieldsMetadata[$key]) && isset($this->importableFieldsMetadata[trim($prefixString) . '.' . $key])) {
          $key = trim($prefixString) . '.' . $key;
        }
        $metadata = $this->getFieldMetadata($key);
        $errors[] = $prefixString . ($metadata['label'] ?? $metadata['html']['label'] ?? $metadata['title']);
      }
      else {
        // Numeric key suggests we are drilling into option values
        $errors[] = TRUE;
      }
    }
    elseif (is_array($value)) {
      foreach ($value as $innerKey => $innerValue) {
        if (!$prefixString && !isset($this->importableFieldsMetadata[$innerKey]) && isset($this->importableFieldsMetadata[$key . '.' . $innerKey])) {
          $innerKey = $key . '.' . $innerKey;
        }
        $result = $this->getInvalidValues($innerValue, $innerKey, $prefixString);
        if ($result === [TRUE]) {
          $metadata = $this->getFieldMetadata($key);
          $errors[] = $prefixString . ($metadata['html']['label'] ?? $metadata['title']);
        }
        elseif (!empty($result)) {
          $errors = array_merge($result, $errors);
        }
      }
    }
    return array_filter($errors);
  }

  /**
   * Get the available countries.
   *
   * If the site is not configured with a restriction then all countries are valid
   * but otherwise only a select array are.
   *
   * @return array|false
   *   FALSE indicates no restrictions.
   */
  protected function getAvailableCountries() {
    if ($this->availableCountries === NULL) {
      $availableCountries = Civi::settings()->get('countryLimit');
      $this->availableCountries = !empty($availableCountries) ? array_fill_keys($availableCountries, TRUE) : FALSE;
    }
    return $this->availableCountries;
  }

  /**
   * Get the metadata field for which importable fields does not key the actual field name.
   *
   * @return string[]
   */
  protected function getOddlyMappedMetadataFields(): array {
    return [];
  }

  /**
   * Get the default country for the site.
   *
   * @return int
   */
  protected function getSiteDefaultCountry(): int {
    if ($this->siteDefaultCountry === NULL) {
      $this->siteDefaultCountry = (int) Civi::settings()->get('defaultContactCountry');
    }
    return $this->siteDefaultCountry;
  }

  /**
   * Is the option ambiguous.
   *
   * @param string $fieldName
   * @param string $importedValue
   *
   * @return bool
   */
  protected function isAmbiguous(string $fieldName, $importedValue): bool {
    return !empty($this->ambiguousOptions[$fieldName][$this->getComparisonValue($importedValue)]);
  }

  /**
   * Get the civicrm_mapping_field appropriate layout for the mapper input.
   *
   * For simple parsers (not contribution or contact) the input looks like
   * ['first_name', 'custom_32']
   * and it is converted to
   *
   *  ['name' => 'first_name', 'mapping_id' => 1, 'column_number' => 5],
   *
   * @param array $fieldMapping
   * @param int $mappingID
   * @param int $columnNumber
   *
   * @return array
   */
  public function getMappingFieldFromMapperInput(array $fieldMapping, int $mappingID, int $columnNumber): array {
    return [
      'name' => $fieldMapping[0],
      'mapping_id' => $mappingID,
      'column_number' => $columnNumber,
    ];
  }

  /**
   * The initializer code, called before the processing
   *
   * @return void
   */
  public function init() {
    // Force re-load of user job.
    unset($this->userJob);
    $this->setFieldMetadata();
  }

  /**
   * Get metadata for all importable fields.
   *
   * @return array
   */
  public function getFieldsMetadata() : array {
    if (empty($this->importableFieldsMetadata)) {
      unset($this->userJob);
      $this->setFieldMetadata();
    }
    return $this->importableFieldsMetadata;
  }

  /**
   * Get a list of entities this import supports.
   *
   * @return array
   */
  public function getImportEntities() : array {
    return [
      'Contact' => ['text' => ts('Contact Fields'), 'is_contact' => TRUE],
    ];
  }

  public function getAvailableImportEntities(): array {
    return $this->getImportEntities();
  }

  /**
   * @param array $mappedField
   *   Field detail as would be saved in field_mapping table
   *   or as returned from getMappingFieldFromMapperInput
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getMappedFieldLabel(array $mappedField): string {
    // doNotImport is on it's way out - skip fields will be '' once all is done.
    if (empty($mappedField['name']) || $mappedField['name'] === 'doNotImport') {
      return '';
    }
    $this->setFieldMetadata();
    $metadata = $this->getFieldMetadata($mappedField['name']);
    return $metadata['html']['label'] ?? $metadata['title'];
  }

  /**
   * Get the row from the csv mapped to our parameters.
   *
   * @param array $values
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getMappedRow(array $values): array {
    $params = [];
    foreach ($this->getFieldMappings() as $i => $mappedField) {
      if (!isset($mappedField['name']) || $mappedField['name'] === 'do_not_import') {
        continue;
      }
      if ($mappedField['name']) {
        $fieldSpec = $this->getFieldMetadata($mappedField['name']);
        $params[$fieldSpec['name']] = $this->getTransformedFieldValue($mappedField['name'], $values[$i]);
      }
    }
    return $params;
  }

  /**
   * Get the field mappings for the import.
   *
   * This is the same format as saved in civicrm_mapping_field except
   * that location_type_id = 'Primary' rather than empty where relevant.
   * Also 'im_provider_id' is mapped to the 'real' field name 'provider_id'
   *
   * @return array
   */
  protected function getFieldMappings(): array {
    $mappedFields = $this->getUserJob()['metadata']['import_mappings'] ?? [];
    if (empty($mappedFields)) {
      $mapper = $this->getSubmittedValue('mapper');
      foreach ($mapper as $i => $mapperRow) {
        // Cast to an array as it will be a string for membership
        // and any others we simplify away from using hierselect for a single option.
        $mappedField = $this->getMappingFieldFromMapperInput((array) $mapperRow, 0, $i);
        // Just for clarity since 0 is a pseudo-value
        unset($mappedField['mapping_id']);
        $mappedFields[] = $mappedField;
      }
    }
    return $mappedFields;
  }

  /**
   * Run import.
   *
   * @param \CRM_Queue_TaskContext $taskContext
   *
   * @param int $userJobID
   * @param int $limit
   * @param int $offset
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function runJob(\CRM_Queue_TaskContext $taskContext, int $userJobID, int $limit, int $offset): bool {
    $userJob = UserJob::get()->addWhere('id', '=', $userJobID)
      ->addSelect('job_type', 'start_date')->execute()->first();
    if (!$userJob['start_date']) {
      UserJob::update(FALSE)
        ->setValues([
          'status_id:name' => 'in_progress',
          'start_date' => 'now',
        ])
        ->addWhere('id', '=', $userJob['id'])
        ->execute();
    }
    $parserClass = NULL;
    foreach (CRM_Core_BAO_UserJob::getTypes() as $userJobType) {
      if ($userJob['job_type'] === $userJobType['id']) {
        $parserClass = $userJobType['class'];
      }
    }
    /** @var \CRM_Import_Parser $parser */
    $parser = new $parserClass();
    $parser->setUserJobID($userJobID);
    // Not sure if we still need to init....
    $parser->init();
    $dataSource = $parser->getDataSourceObject();
    $dataSource->setStatuses(['new']);
    $dataSource->setLimit($limit);

    while ($row = $dataSource->getRow()) {
      if ($parser->validateRow($row)) {
        $parser->import($row);
      }
    }
    $parser->doPostImportActions();
    return TRUE;
  }

  /**
   * Update the status of the import row to reflect the processing outcome.
   *
   * @param int $id
   * @param string $status
   * @param string $message
   * @param int|null $entityID
   *   Optional created entity ID
   * @param array $additionalFields
   *  Additional fields to be tracked
   * @param array $createdContactIDs
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function setImportStatus(int $id, string $status, string $message = '', ?int $entityID = NULL, $additionalFields = [], $createdContactIDs = []): void {
    foreach ($createdContactIDs as $createdContactID) {
      // Store any created contacts for post_actions like tag or add to group.
      // These are done on a 'per-batch' status in processPorstActions
      // so holding in a property is OK.
      $this->createdContacts[$createdContactID] = $createdContactID;
    }
    $this->getDataSourceObject()->updateStatus($id, $status, $message, $entityID, $additionalFields);
  }

  /**
   * Get the value to use for option comparison purposes.
   *
   * We do a case-insensitive comparison, also swapping ’ for '
   * which has at least one known usage (Côte d’Ivoire).
   *
   * Note we do this to both sides of the comparison.
   *
   * @param int|string|false|null $importedValue
   *
   * @return false|int|string|null
   */
  protected function getComparisonValue($importedValue) {
    return is_numeric($importedValue) ? $importedValue : mb_strtolower(str_replace('’', "'", $importedValue));
  }

  /**
   * Look up for an existing contact with the given external_identifier.
   *
   * If the identifier is found on a deleted contact then it is not a match
   * but it must be removed from that contact to allow the new contact to
   * have that external_identifier.
   *
   * @param string|null $externalIdentifier
   * @param string|null $contactType
   *   If supplied the contact will be validated against this type.
   * @param int|null $contactID
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function lookupExternalIdentifier(?string $externalIdentifier, ?string $contactType, ?int $contactID): ?int {
    if (!$externalIdentifier) {
      return NULL;
    }
    // Check for any match on external id, deleted or otherwise.
    $foundContact = civicrm_api3('Contact', 'get', [
      'external_identifier' => $externalIdentifier,
      'showAll' => 'all',
      'sequential' => TRUE,
      'return' => ['id', 'contact_is_deleted', 'contact_type'],
    ]);
    if (empty($foundContact['id'])) {
      return NULL;
    }
    if (!empty($foundContact['values'][0]['contact_is_deleted'])) {
      // If the contact is deleted, update external identifier to be blank
      // to avoid key error from MySQL.
      $params = ['id' => $foundContact['id'], 'external_identifier' => ''];
      civicrm_api3('Contact', 'create', $params);
      return NULL;
    }
    if ($contactType && $foundContact['values'][0]['contact_type'] !== $contactType) {
      throw new CRM_Core_Exception('Mismatched contact Types', CRM_Import_Parser::NO_MATCH);
    }
    //check if external identifier exists in database
    if ($contactID && $foundContact['id'] !== $contactID) {
      throw new CRM_Core_Exception(
        ts('Imported external ID already belongs to an existing contact with a different contact ID than the imported contact ID or than the contact ID of the contact matched on the entity imported.'),
        CRM_Import_Parser::ERROR);
    }
    return (int) $foundContact['id'];
  }

  /**
   * @param string $entity
   * @param int $id
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function checkEntityExists(string $entity, int $id): array {
    try {
      return civicrm_api4($entity, 'get', ['where' => [['id', '=', $id]]])->single();
    }
    catch (CRM_Core_Exception $e) {
      throw new CRM_Core_Exception(ts('%1 record not found for id %2', [
        1 => $entity,
        2 => $id,
      ]));
    }
  }

  /**
   * @param array|null $row
   *
   * @return bool
   */
  public function validateRow(?array $row): bool {
    try {
      $rowNumber = $row['_id'];
      $values = array_values($row);
      $this->validateValues($values);
      $this->setImportStatus($rowNumber, 'VALID', '');
      return TRUE;
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return FALSE;
    }
  }

  protected function removeEmptyValues($array) {
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $array[$key] = $this->removeEmptyValues($value);
      }
      elseif ($value === '') {
        unset($array[$key]);
      }
    }
    return $array;
  }

  /**
   * Given an array of contact values, figure out the contact type.
   *
   * @param array $values
   * @return string
   */
  protected function guessContactType(array $values): string {
    if (!empty($values['contact_type'])) {
      return $values['contact_type'];
    }
    $contactFields = \Civi::entity('Contact')->getFields();
    foreach (\CRM_Contact_BAO_ContactType::basicTypes() as $contactType) {
      foreach ($contactFields as $fieldName => $field) {
        if (($field['contact_type'] ?? NULL) === $contactType && !empty($values[$fieldName])) {
          return $contactType;
        }
      }
    }
    return 'Individual';
  }

}
