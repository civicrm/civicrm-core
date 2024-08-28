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

use Civi\Api4\Address;
use Civi\Api4\Campaign;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\DedupeRule;
use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\Email;
use Civi\Api4\Event;
use Civi\Api4\Phone;
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

  /**
   * Return codes
   */
  const VALID = 1, WARNING = 2, ERROR = 4, CONFLICT = 8, STOP = 16, DUPLICATE = 32, MULTIPLE_DUPE = 64, NO_MATCH = 128, UNPARSED_ADDRESS_WARNING = 256;

  /**
   * Codes for duplicate record handling
   */
  const DUPLICATE_SKIP = 1, DUPLICATE_UPDATE = 4, DUPLICATE_FILL = 8, DUPLICATE_NOCHECK = 16;

  /**
   * User job id.
   *
   * This is the primary key of the civicrm_user_job table which is used to
   * track the import.
   *
   * @var int
   */
  protected $userJobID;

  /**
   * The user job in use.
   *
   * @var array
   */
  protected $userJob;

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
   * @return int|null
   */
  public function getUserJobID(): ?int {
    return $this->userJobID;
  }

  /**
   * Ids of contacts created this iteration.
   *
   * @var array
   */
  protected $createdContacts = [];

  /**
   * Set user job ID.
   *
   * @param int $userJobID
   *
   * @return self
   */
  public function setUserJobID(int $userJobID): self {
    $this->userJobID = $userJobID;
    return $this;
  }

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

  /**
   * An array of Custom field mappings for api formatting
   *
   * e.g ['custom_7' => 'IndividualData.Marriage_date']
   *
   * @var array
   */
  protected $customFieldNameMap = [];

  /**
   * Get User Job.
   *
   * API call to retrieve the userJob row.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getUserJob(): array {
    if (empty($this->userJob)) {
      $this->userJob = UserJob::get()
        ->addWhere('id', '=', $this->getUserJobID())
        ->execute()
        ->first();
    }
    return $this->userJob;
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
    return $this->getUserJob()['metadata']['submitted_values'][$fieldName];
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

  private $dedupeRules = [];

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
   * @param string $contactType
   *
   * @return array[]
   */
  protected function getContactFields(string $contactType): array {
    $contactFields = $this->getAllContactFields('');
    $dedupeFields = $this->getDedupeFields($contactType);

    foreach ($dedupeFields as $fieldName => $dedupeField) {
      if (!isset($contactFields[$fieldName])) {
        continue;
      }
      $contactFields[$fieldName]['title'] . ' ' . ts('(match to contact)');
      $contactFields[$fieldName]['match_rule'] = $this->getDefaultRuleForContactType($contactType);
    }

    $contactFields['external_identifier']['title'] .= (' ' . ts('(match to contact)'));
    $contactFields['external_identifier']['match_rule'] = '*';
    return $contactFields;
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
        // Duplicates are being skipped so id matching is not availble.
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
   * Add progress bar to the import process. Calculates time remaining, status etc.
   *
   * @param $statusID
   *   status id of the import process saved in $config->uploadDir.
   * @param bool $startImport
   *   True when progress bar is to be initiated.
   * @param $startTimestamp
   *   Initial timestamp when the import was started.
   * @param $prevTimestamp
   *   Previous timestamp when this function was last called.
   * @param $totalRowCount
   *   Total number of rows in the import file.
   *
   * @deprecated
   *
   * @return NULL|$currTimestamp
   */
  public function progressImport($statusID, $startImport = TRUE, $startTimestamp = NULL, $prevTimestamp = NULL, $totalRowCount = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('no replacement');
    $statusFile = CRM_Core_Config::singleton()->uploadDir . "status_{$statusID}.txt";

    if ($startImport) {
      $status = "<div class='description'>&nbsp; " . ts('No processing status reported yet.') . "</div>";
      //do not force the browser to display the save dialog, CRM-7640
      $contents = json_encode([0, $status]);
      file_put_contents($statusFile, $contents);
    }
    else {
      $rowCount = $this->_rowCount ?? $this->_lineCount;
      $currTimestamp = time();
      $time = ($currTimestamp - $prevTimestamp);
      $recordsLeft = $totalRowCount - $rowCount;
      if ($recordsLeft < 0) {
        $recordsLeft = 0;
      }
      $estimatedTime = ($recordsLeft / 50) * $time;
      $estMinutes = floor($estimatedTime / 60);
      $timeFormatted = '';
      if ($estMinutes > 1) {
        $timeFormatted = $estMinutes . ' ' . ts('minutes') . ' ';
        $estimatedTime = $estimatedTime - ($estMinutes * 60);
      }
      $timeFormatted .= round($estimatedTime) . ' ' . ts('seconds');
      $processedPercent = (int ) (($rowCount * 100) / $totalRowCount);
      $statusMsg = ts('%1 of %2 records - %3 remaining',
        [1 => $rowCount, 2 => $totalRowCount, 3 => $timeFormatted]
      );
      $status = "<div class=\"description\">&nbsp; <strong>{$statusMsg}</strong></div>";
      $contents = json_encode([$processedPercent, $status]);

      file_put_contents($statusFile, $contents);
      return $currTimestamp;
    }
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
   * Do this work on the form layer.
   *
   * @deprecated in 5.54 will be removed around 5.80
   *
   * @return array
   */
  public function getHeaderPatterns(): array {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Import_Forms::getHeaderPatterns');
    $values = [];
    foreach ($this->importableFieldsMetadata as $name => $field) {
      if (isset($field['headerPattern'])) {
        $values[$name] = $field['headerPattern'] ?: '//';
      }
    }
    return $values;
  }

  /**
   * Remove single-quote enclosures from a value array (row).
   *
   * @param array $values
   * @param string $enclosure
   *
   * @deprecated
   *
   * @return void
   */
  public static function encloseScrub(&$values, $enclosure = "'") {
    CRM_Core_Error::deprecatedFunctionWarning('no replacement');
    if (empty($values)) {
      return;
    }

    foreach ($values as $k => $v) {
      $values[$k] = preg_replace("/^$enclosure(.*)$enclosure$/", '$1', $v);
    }
  }

  /**
   * Setter function.
   *
   * @deprecated
   *
   * @param int $max
   *
   * @return void
   */
  public function setMaxLinesToProcess($max) {
    CRM_Core_Error::deprecatedFunctionWarning('no replacement');
    $this->_maxLinesToProcess = $max;
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
    $this->validateRequiredFields($requiredFields, $params, $prefixString);
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
   * Determines the file extension based on error code.
   *
   * @deprecated
   *
   * @var int $type error code constant
   * @return string
   */
  public static function errorFileName($type) {
    CRM_Core_Error::deprecatedFunctionWarning('no replacement');
    $fileName = NULL;
    if (empty($type)) {
      return $fileName;
    }

    $config = CRM_Core_Config::singleton();
    $fileName = $config->uploadDir . "sqlImport";
    switch ($type) {
      case self::ERROR:
        $fileName .= '.errors';
        break;

      case self::DUPLICATE:
        $fileName .= '.duplicates';
        break;

      case self::NO_MATCH:
        $fileName .= '.mismatch';
        break;

      case self::UNPARSED_ADDRESS_WARNING:
        $fileName .= '.unparsedAddress';
        break;
    }

    return $fileName;
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
    $existingContact = Contact::get(FALSE)
      ->addWhere('id', '=', $contactID)
      // Don't auto-filter deleted - people use import to undelete.
      ->addWhere('is_deleted', 'IN', [0, 1])
      ->addSelect('contact_type')->execute()->first();
    if (empty($existingContact['id'])) {
      throw new CRM_Core_Exception('No contact found for this contact ID:' . $contactID, CRM_Import_Parser::NO_MATCH);
    }
    if ($contactType && $existingContact['contact_type'] !== $contactType) {
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
   * Check if contact is a duplicate .
   *
   * @param array $formatValues
   *
   * @deprecated
   *
   * @return array
   */
  protected function checkContactDuplicate(&$formatValues) {
    //retrieve contact id using contact dedupe rule
    $formatValues['contact_type'] ??= $this->getContactType();
    $formatValues['version'] = 3;
    $params = $formatValues;
    static $cIndieFields = NULL;
    static $defaultLocationId = NULL;

    $contactType = $params['contact_type'];
    if ($cIndieFields == NULL) {
      $cTempIndieFields = CRM_Contact_BAO_Contact::importableFields($contactType);
      $cIndieFields = $cTempIndieFields;

      $defaultLocation = CRM_Core_BAO_LocationType::getDefault();

      // set the value to default location id else set to 1
      if (!$defaultLocationId = (int) $defaultLocation->id) {
        $defaultLocationId = 1;
      }
    }

    $locationFields = CRM_Contact_BAO_Query::$_locationSpecificFields;

    $contactFormatted = [];
    foreach ($params as $key => $field) {
      if ($field == NULL || $field === '') {
        continue;
      }
      // CRM-17040, Considering only primary contact when importing contributions. So contribution inserts into primary contact
      // instead of soft credit contact.
      if (is_array($field) && $key !== "soft_credit") {
        foreach ($field as $value) {
          $break = FALSE;
          if (is_array($value)) {
            foreach ($value as $name => $testForEmpty) {
              if ($name !== 'phone_type' &&
                ($testForEmpty === '' || $testForEmpty == NULL)
              ) {
                $break = TRUE;
                break;
              }
            }
          }
          else {
            $break = TRUE;
          }
          if (!$break) {
            $this->_civicrm_api3_deprecated_add_formatted_param($value, $contactFormatted);
          }
        }
        continue;
      }

      $value = [$key => $field];

      // check if location related field, then we need to add primary location type
      if (in_array($key, $locationFields)) {
        $value['location_type_id'] = $defaultLocationId;
      }
      elseif (array_key_exists($key, $cIndieFields)) {
        $value['contact_type'] = $contactType;
      }

      $this->_civicrm_api3_deprecated_add_formatted_param($value, $contactFormatted);
    }

    $contactFormatted['contact_type'] = $contactType;
    $params = &$contactFormatted;
    $id = $params['id'] ?? NULL;
    $externalId = $params['external_identifier'] ?? NULL;
    if ($id || $externalId) {
      $contact = new CRM_Contact_DAO_Contact();

      $contact->id = $id;
      $contact->external_identifier = $externalId;

      if ($contact->find(TRUE)) {
        if ($params['contact_type'] != $contact->contact_type) {
          return ['is_error' => 1, 'error_message' => 'Mismatched contact IDs OR Mismatched contact Types'];
        }
        return [
          'is_error' => 1,
          'error_message' => [
            'code' => CRM_Core_Error::DUPLICATE_CONTACT,
            'params' => [$contact->id],
            'level' => 'Fatal',
            'message' => "Found matching contacts: $contact->id",
          ],
        ];
      }
    }
    else {
      $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($params, $params['contact_type'], 'Unsupervised');

      if (!empty($ids)) {
        return [
          'is_error' => 1,
          'error_message' => [
            'code' => CRM_Core_Error::DUPLICATE_CONTACT,
            'params' => $ids,
            'level' => 'Fatal',
            'message' => 'Found matching contacts: ' . implode(',', $ids),
          ],
        ];
      }
    }
    return ['is_error' => 0];
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
   * Get the dedupe rule name.
   *
   * @param int $id
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDedupeRuleName(int $id): string {
    return DedupeRuleGroup::get(FALSE)
      ->addWhere('id', '=', $id)
      ->addSelect('name')
      ->execute()->first()['name'];
  }

  /**
   * Get the dedupe rule, including an array of fields with weights.
   *
   * The fields are keyed according to the metadata.
   *
   * @param string $contactType
   * @param string|null $name
   *
   * @return array
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getDedupeRule(string $contactType, ?string $name = NULL): array {
    if (!$name) {
      $name = $this->getDefaultRuleForContactType($contactType);
    }
    if (empty($this->dedupeRules[$name])) {
      $where = [['name', '=', $name]];
      $this->loadRules($where);
    }
    return $this->dedupeRules[$name];
  }

  /**
   * Get all dedupe rules.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getAllDedupeRules(): array {
    $this->loadRules();
    return $this->dedupeRules;
  }

  /**
   * This function adds the contact variable in $values to the
   * parameter list $params.  For most cases, $values should have length 1.  If
   * the variable being added is a child of Location, a location_type_id must
   * also be included.  If it is a child of phone, a phone_type must be included.
   *
   * @param array $values
   *   The variable(s) to be added.
   * @param array $params
   *   The structured parameter list.
   *
   * @return bool|CRM_Utils_Error
   *
   * @throws \CRM_Core_Exception
   * @deprecated
   */
  private function _civicrm_api3_deprecated_add_formatted_param(&$values, &$params) {
    // @todo - like most functions in import ... most of this is cruft....
    // Crawl through the possible classes:
    // Contact
    //      Individual
    //      Household
    //      Organization
    //          Location
    //              Address
    //              Email
    //              Phone
    //              IM
    //      Note
    //      Custom

    // Cache the various object fields
    static $fields = NULL;

    if ($fields == NULL) {
      $fields = [];
    }

    // first add core contact values since for other Civi modules they are not added
    $contactFields = CRM_Contact_DAO_Contact::fields();
    _civicrm_api3_store_values($contactFields, $values, $params);

    if (isset($values['contact_type'])) {
      // we're an individual/household/org property

      $fields[$values['contact_type']] = CRM_Contact_DAO_Contact::fields();

      _civicrm_api3_store_values($fields[$values['contact_type']], $values, $params);
      return TRUE;
    }

    if (isset($values['individual_prefix'])) {
      CRM_Core_Error::deprecatedWarning('code should be unreachable, slated for removal');
      if (!empty($params['prefix_id'])) {
        $prefixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
        $params['prefix'] = $prefixes[$params['prefix_id']];
      }
      else {
        $params['prefix'] = $values['individual_prefix'];
      }
      return TRUE;
    }

    if (isset($values['individual_suffix'])) {
      CRM_Core_Error::deprecatedWarning('code should be unreachable, slated for removal');
      if (!empty($params['suffix_id'])) {
        $suffixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');
        $params['suffix'] = $suffixes[$params['suffix_id']];
      }
      else {
        $params['suffix'] = $values['individual_suffix'];
      }
      return TRUE;
    }

    if (isset($values['gender'])) {
      CRM_Core_Error::deprecatedWarning('code should be unreachable, slated for removal');
      if (!empty($params['gender_id'])) {
        $genders = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
        $params['gender'] = $genders[$params['gender_id']];
      }
      else {
        $params['gender'] = $values['gender'];
      }
      return TRUE;
    }

    // format the website params.
    if (!empty($values['url'])) {
      static $websiteFields;
      if (!is_array($websiteFields)) {
        require_once 'CRM/Core/DAO/Website.php';
        $websiteFields = CRM_Core_DAO_Website::fields();
      }
      if (!array_key_exists('website', $params) ||
        !is_array($params['website'])
      ) {
        $params['website'] = [];
      }

      $websiteCount = count($params['website']);
      _civicrm_api3_store_values($websiteFields, $values,
        $params['website'][++$websiteCount]
      );

      return TRUE;
    }

    // get the formatted location blocks into params - w/ 3.0 format, CRM-4605
    if (!empty($values['location_type_id'])) {
      foreach (['Phone', 'Email', 'IM', 'OpenID', 'Phone_Ext'] as $block) {
        $name = strtolower($block);
        if (!array_key_exists($name, $values)) {
          continue;
        }

        if ($name === 'phone_ext') {
          $block = 'Phone';
        }

        // block present in value array.
        if (!array_key_exists($name, $params) || !is_array($params[$name])) {
          $params[$name] = [];
        }

        if (!array_key_exists($block, $fields)) {
          $className = "CRM_Core_DAO_$block";
          $fields[$block] =& $className::fields();
        }

        $blockCnt = count($params[$name]);

        // copy value to dao field name.
        if ($name == 'im') {
          $values['name'] = $values[$name];
        }

        _civicrm_api3_store_values($fields[$block], $values,
          $params[$name][++$blockCnt]
        );

        if (empty($params['id']) && ($blockCnt == 1)) {
          $params[$name][$blockCnt]['is_primary'] = TRUE;
        }

        // we only process single block at a time.
        return TRUE;
      }

      // handle address fields.
      if (!array_key_exists('address', $params) || !is_array($params['address'])) {
        $params['address'] = [];
      }

      $addressCnt = 1;
      foreach ($params['address'] as $cnt => $addressBlock) {
        if (($values['location_type_id'] ?? NULL) ==
          CRM_Utils_Array::value('location_type_id', $addressBlock)
        ) {
          $addressCnt = $cnt;
          break;
        }
        $addressCnt++;
      }

      if (!array_key_exists('Address', $fields)) {
        $fields['Address'] = CRM_Core_DAO_Address::fields();
      }

      // Note: we doing multiple value formatting here for address custom fields, plus putting into right format.
      // The actual formatting (like date, country ..etc) for address custom fields is taken care of while saving
      // the address in CRM_Core_BAO_Address::create method
      if (!empty($values['location_type_id'])) {
        static $customFields = [];
        if (empty($customFields)) {
          $customFields = CRM_Core_BAO_CustomField::getFields('Address');
        }
        // make a copy of values, as we going to make changes
        $newValues = $values;
        foreach ($values as $key => $val) {
          $customFieldID = CRM_Core_BAO_CustomField::getKeyID($key);
          if ($customFieldID && array_key_exists($customFieldID, $customFields)) {
            // mark an entry in fields array since we want the value of custom field to be copied
            $fields['Address'][$key] = NULL;

            $htmlType = $customFields[$customFieldID]['html_type'] ?? NULL;
            if (CRM_Core_BAO_CustomField::isSerialized($customFields[$customFieldID]) && $val) {
              $mulValues = explode(',', $val);
              $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
              $newValues[$key] = [];
              foreach ($mulValues as $v1) {
                foreach ($customOption as $v2) {
                  if ((strtolower($v2['label']) == strtolower(trim($v1))) ||
                    (strtolower($v2['value']) == strtolower(trim($v1)))
                  ) {
                    if ($htmlType == 'CheckBox') {
                      $newValues[$key][$v2['value']] = 1;
                    }
                    else {
                      $newValues[$key][] = $v2['value'];
                    }
                  }
                }
              }
            }
          }
        }
        // consider new values
        $values = $newValues;
      }

      _civicrm_api3_store_values($fields['Address'], $values, $params['address'][$addressCnt]);

      $addressFields = [
        'county',
        'country',
        'state_province',
        'supplemental_address_1',
        'supplemental_address_2',
        'supplemental_address_3',
        'StateProvince.name',
      ];

      foreach ($addressFields as $field) {
        if (array_key_exists($field, $values)) {
          if (!array_key_exists('address', $params)) {
            $params['address'] = [];
          }
          $params['address'][$addressCnt][$field] = $values[$field];
        }
      }

      if ($addressCnt == 1) {

        $params['address'][$addressCnt]['is_primary'] = TRUE;
      }
      return TRUE;
    }

    if (isset($values['note'])) {
      // add a note field
      if (!isset($params['note'])) {
        $params['note'] = [];
      }
      $noteBlock = count($params['note']) + 1;

      $params['note'][$noteBlock] = [];
      if (!isset($fields['Note'])) {
        $fields['Note'] = CRM_Core_DAO_Note::fields();
      }

      // get the current logged in civicrm user
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');

      if ($userID) {
        $values['contact_id'] = $userID;
      }

      _civicrm_api3_store_values($fields['Note'], $values, $params['note'][$noteBlock]);

      return TRUE;
    }

    // Check for custom field values

    if (empty($fields['custom'])) {
      $fields['custom'] = &CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $values),
        FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE
      );
    }

    foreach ($values as $key => $value) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        // check if it's a valid custom field id

        if (!array_key_exists($customFieldID, $fields['custom'])) {
          return civicrm_api3_create_error('Invalid custom field ID');
        }
        else {
          $params[$key] = $value;
        }
      }
    }
  }

  /**
   * Parse a field which could be represented by a label or name value rather than the DB value.
   *
   * We will try to match name first or (per https://lab.civicrm.org/dev/core/issues/1285 if we have an id.
   *
   * but if not available then see if we have a label that can be converted to a name.
   *
   * @deprecated
   *
   * @param string|int|null $submittedValue
   * @param array $fieldSpec
   *   Metadata for the field
   *
   * @return mixed
   */
  protected function parsePseudoConstantField($submittedValue, $fieldSpec) {
    CRM_Core_Error::deprecatedFunctionWarning('no replacement');
    // dev/core#1289 Somehow we have wound up here but the BAO has not been specified in the fieldspec so we need to check this but future us problem, for now lets just return the submittedValue
    if (!isset($fieldSpec['bao'])) {
      return $submittedValue;
    }
    /** @var \CRM_Core_DAO $bao */
    $bao = $fieldSpec['bao'];
    // For historical reasons use validate as context - ie disabled name matches ARE permitted.
    $nameOptions = $bao::buildOptions($fieldSpec['name'], 'validate');
    if (isset($nameOptions[$submittedValue])) {
      return $submittedValue;
    }
    if (in_array($submittedValue, $nameOptions)) {
      return array_search($submittedValue, $nameOptions, TRUE);
    }

    $labelOptions = array_flip($bao::buildOptions($fieldSpec['name'], 'match'));
    if (isset($labelOptions[$submittedValue])) {
      return array_search($labelOptions[$submittedValue], $nameOptions, TRUE);
    }
    return '';
  }

  /**
   * This is code extracted from 4 places where this exact snippet was being duplicated.
   *
   * FIXME: Extracting this was a first step, but there's also
   *  1. Inconsistency in the way other select options are handled.
   *     Contribution adds handling for Select/Radio/Autocomplete
   *     Participant/Activity only handles Select/Radio and misses Autocomplete
   *     Membership is missing all of it
   *  2. Inconsistency with the way this works vs. how it's implemented in Contact import.
   *
   * @param $customFieldID
   * @param $value
   * @param $fieldType
   * @return array
   */
  public static function unserializeCustomValue($customFieldID, $value, $fieldType) {
    $mulValues = explode(',', $value);
    $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
    $values = [];
    foreach ($mulValues as $v1) {
      foreach ($customOption as $customValueID => $customLabel) {
        $customValue = $customLabel['value'];
        if ((strtolower(trim($customLabel['label'])) == strtolower(trim($v1))) ||
          (strtolower(trim($customValue)) == strtolower(trim($v1)))
        ) {
          $values[] = $customValue;
        }
      }
    }
    return $values;
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
   * @param string $prefixString
   *
   * @throws \CRM_Core_Exception Exception thrown if field requirements are not met.
   */
  protected function validateRequiredFields(array $requiredFields, array $params, $prefixString = ''): void {
    $missingFields = $this->getMissingFields($requiredFields, $params);
    if (empty($missingFields)) {
      return;
    }
    throw new CRM_Core_Exception($prefixString . ts('Missing required fields:') . ' ' . implode(' ' . ts('OR') . ' ', $missingFields));
  }

  /**
   * Validate that the mapping has the required fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function validateMapping($mapping): void {
    $mappedFields = [];
    foreach ($mapping as $mappingField) {
      $mappedFields[$mappingField[0]] = $mappingField[0];
    }
    $entity = $this->baseEntity;
    $missingFields = $this->getMissingFields($this->getRequiredFieldsForEntity($entity, $this->getActionForEntity($entity)), $mappedFields);
    if (!empty($missingFields)) {
      $error = [];
      foreach ($missingFields as $missingField) {
        $error[] = ts('Missing required field: %1', [1 => $missingField]);
      }
      throw new CRM_Core_Exception(implode('<br/>', $error));
    }
  }

  /**
   * Get the import action for the given entity.
   *
   * @param string $entity
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getActionForEntity(string $entity): string {
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
   * @throws \API_Exception
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
   * @throws \API_Exception
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
  private function getRequiredFieldsForEntity(string $entity, string $action): array {
    $entityMetadata = $this->getImportEntities()[$entity];
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
      if (!empty($params[$requirement])) {
        if (!is_array($params[$requirement])) {
          return [];
        }
        // Recurse the array looking for the key - eg. look for email
        // in a location values array
        foreach ($params[$requirement] as $locationValues) {
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
      $value = CRM_Utils_Date::formatDate($importedValue, (int) $this->getSubmittedValue('dateFormats'));
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
    if ($fieldName === 'event_id') {
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
   * @throws \CRM_Core_Exception
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
    $fieldMapName = str_replace('__', '.', $fieldMapName);
    // See https://lab.civicrm.org/dev/core/-/issues/4317#note_91322 - a further hack for quickform not
    // handling dots in field names. One day we will get rid of the Quick form screen...
    $fieldMapName = str_replace('~~', '_.', $fieldMapName);
    // This whole business of only loading metadata for one type when we actually need it for all is ... dubious.
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
   * Get the field metadata for fields to be be offered to match the contact.
   * @todo this is very similar to getContactFields - this is called by participant and that
   * by contribution import. They should be reconciled - but note that one is being fixed
   * to support api4 style fields on contribution import - with this import to follow.
   *
   * @return array
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function getContactMatchingFields(): array {
    $contactFields = CRM_Contact_BAO_Contact::importableFields($this->getContactType(), NULL);
    $fields = ['external_identifier' => $contactFields['external_identifier']];
    $fields['external_identifier']['title'] .= ' (match to contact)';
    // Using new Dedupe rule.
    $ruleParams = [
      'contact_type' => $this->getContactType(),
      'used' => $this->getSubmittedValue('dedupe_rule_id') ?? 'Unsupervised',
    ];
    $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

    if (is_array($fieldsArray)) {
      foreach ($fieldsArray as $value) {
        $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
          $value,
          'id',
          'column_name'
        );
        $value = trim($customFieldId ? 'custom_' . $customFieldId : $value);
        $fields[$value] = $contactFields[$value] ?? NULL;
        $title = $fields[$value]['title'] . ' (match to contact)';
        $fields[$value]['title'] = $title;
      }
    }
    return $fields;
  }

  /**
   * Get the entity for the given field.
   *
   * @param string $fieldName
   *
   * @return mixed|null
   * @throws \CRM_Core_Exception
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
    while ($row = $dataSource->getRow()) {
      $this->validateRow($row);
    }
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
    if (empty($params['id'])) {
      $this->validateRequiredFields($this->getRequiredFields(), $params);
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
        $metadata = $this->getFieldMetadata($key);
        $errors[] = $prefixString . ($metadata['html']['label'] ?? $metadata['title']);
      }
      else {
        // Numeric key suggests we are drilling into option values
        $errors[] = TRUE;
      }
    }
    elseif (is_array($value)) {
      foreach ($value as $innerKey => $innerValue) {
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
    return [
      'country_id' => 'country',
      'state_province_id' => 'state_province',
      'county_id' => 'county',
      'email_greeting_id' => 'email_greeting',
      'postal_greeting_id' => 'postal_greeting',
      'addressee_id' => 'addressee',
      'source' => 'contact_source',
    ];
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
    if ($mappedField['name'] === 'doNotImport') {
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
      if ($mappedField['name'] === 'do_not_import') {
        continue;
      }
      if ($mappedField['name']) {
        $params[$this->getFieldMetadata($mappedField['name'])['name']] = $this->getTransformedFieldValue($mappedField['name'], $values[$i]);
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
   * @throws \CRM_Core_Exception
   */
  protected function getFieldMappings(): array {
    $mappedFields = [];
    $mapper = $this->getSubmittedValue('mapper');
    foreach ($mapper as $i => $mapperRow) {
      // Cast to an array as it will be a string for membership
      // and any others we simplify away from using hierselect for a single option.
      $mappedField = $this->getMappingFieldFromMapperInput((array) $mapperRow, 0, $i);
      // Just for clarity since 0 is a pseudo-value
      unset($mappedField['mapping_id']);
      $mappedFields[] = $mappedField;
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
      $values = array_values($row);
      $parser->import($values);
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
   * Get contacts that match the input parameters, using a dedupe rule.
   *
   * @param array $params
   * @param int|null|array $dedupeRuleID
   * @param bool $isApiMetadata
   *   Is the import using api4 style metadata (in which case no conversion needed) - eventually
   *   only contact import will use a different style (as it supports multiple locations) and the
   *   handling will be in that class.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getPossibleMatchesByDedupeRule(array $params, $dedupeRuleID = NULL, $isApiMetadata = TRUE): array {
    if ($isApiMetadata === FALSE) {
      foreach (['email', 'address', 'phone', 'im'] as $locationEntity) {
        if (array_key_exists($locationEntity, $params)) {
          // Prefer primary
          if (array_key_exists('Primary', $params[$locationEntity])) {
            $locationParams = $params[$locationEntity]['Primary'];
          }
          else {
            // Chose the first one - at least they can manipulate the order.
            $locationParams = reset($params[$locationEntity]);
          }
          foreach ($locationParams as $key => $locationParam) {
            // Even though we might not be using 'primary' we 'pretend' here
            // since the apiv4 code expects that...
            $params[$locationEntity . '_primary' . '.' . $key] = $locationParam;
          }
          unset($params[$locationEntity]);
        }
      }
      foreach ($params as $key => $value) {
        if (strpos($key, 'custom_') === 0) {
          $params[$this->getApi4Name($key)] = $value;
          unset($params[$key]);
        }
      }
    }
    $matchIDs = [];
    $dedupeRules = $this->getDedupeRules((array) $dedupeRuleID, $params['contact_type'] ?? NULL);
    foreach ($dedupeRules as $dedupeRule) {
      $possibleMatches = Contact::getDuplicates(FALSE)
        ->setValues($params)
        ->setDedupeRule($dedupeRule)
        ->execute();

      foreach ($possibleMatches as $possibleMatch) {
        $matchIDs[(int) $possibleMatch['id']] = (int) $possibleMatch['id'];
      }
    }

    return $matchIDs;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function checkEntityExists(string $entity, int $id) {
    try {
      civicrm_api4($entity, 'get', ['where' => [['id', '=', $id]], 'select' => ['id']])->single();
    }
    catch (CRM_Core_Exception $e) {
      throw new CRM_Core_Exception(ts('%1 record not found for id %2', [
        1 => $entity,
        2 => $id,
      ]));
    }
  }

  /**
   * Get the Api4 name of a custom field.
   *
   * @param string $key
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getApi4Name(string $key): string {
    if (!isset($this->customFieldNameMap[$key])) {
      $this->customFieldNameMap[$key] = Contact::getFields(FALSE)
        ->addWhere('custom_field_id', '=', str_replace('custom_', '', $key))
        ->addSelect('name')
        ->execute()->first()['name'];
    }
    return $this->customFieldNameMap[$key];
  }

  /**
   * Get the contact ID for the imported row.
   *
   * If we have a contact ID we check it is valid and, if there is also
   * an external identifier we check it does not conflict.
   *
   * Failing those we try a dedupe lookup.
   *
   * @param array $contactParams
   * @param int|null $contactID
   * @param string $entity
   *   Entity, as described in getImportEntities.
   * @param array|null $dedupeRules
   *   Dedupe rules to apply (will default to unsupervised rule)
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContactID(array $contactParams, ?int $contactID, string $entity, ?array $dedupeRules = NULL): ?int {
    $contactType = $contactParams['contact_type'] ?? NULL;
    if ($contactID) {
      $this->validateContactID($contactID, $contactType);
    }
    if (!empty($contactParams['external_identifier'])) {
      $contactID = $this->lookupExternalIdentifier($contactParams['external_identifier'], $contactType, $contactID ?? NULL);
    }
    if (!$contactID) {
      $action = $this->getActionForEntity($entity);
      $possibleMatches = $this->getPossibleMatchesByDedupeRule($contactParams, $dedupeRules);
      if (count($possibleMatches) === 1) {
        $contactID = array_key_first($possibleMatches);
      }
      elseif (count($possibleMatches) > 1) {
        throw new CRM_Core_Exception(ts('Record duplicates multiple contacts: ') . implode(',', $possibleMatches));
      }
      elseif (!in_array($action, ['create', 'ignore', 'save'], TRUE)) {
        throw new CRM_Core_Exception(ts('No matching %1 found', [$entity, 'String']));
      }
    }
    return $contactID;
  }

  /**
   * Get the fields for the dedupe rule.
   *
   * @param string $contactType
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getDedupeFields(string $contactType): array {
    return $this->getDedupeRule($contactType)['fields'];
  }

  /**
   * Get all contact import fields metadata.
   *
   * @param string $prefix
   *
   * @return array
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function getAllContactFields(string $prefix = 'Contact.'): array {
    $allContactFields = (array) Contact::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->addWhere('fk_entity', 'IS EMPTY')
      ->setAction('save')
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    $contactTypeFields['Individual'] = (array) Contact::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->addWhere('fk_entity', 'IS EMPTY')
      ->setAction('save')
      ->setSelect(['name'])
      ->addValue('contact_type', 'Individual')
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    $contactTypeFields['Organization'] = (array) Contact::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->addWhere('fk_entity', 'IS EMPTY')
      ->setAction('save')
      ->setSelect(['name'])
      ->addValue('contact_type', 'Organization')
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    $contactTypeFields['Household'] = (array) Contact::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->addWhere('fk_entity', 'IS EMPTY')
      ->setAction('save')
      ->setSelect(['name'])
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    $prefixedFields = [];
    foreach ($allContactFields as $fieldName => $field) {
      $field['contact_type'] = [];
      foreach ($contactTypeFields as $contactTypeName => $fields) {
        if (array_key_exists($fieldName, $fields)) {
          $field['contact_type'][$contactTypeName] = $contactTypeName;
        }
      }
      $fieldName = $prefix . $fieldName;
      if (!empty($field['custom_field_id'])) {
        $this->customFieldNameMap['custom_' . $field['custom_field_id']] = $fieldName;
      }
      $prefixedFields[$fieldName] = $field;
    }

    $addressFields = (array) Address::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->setAction('save')
      ->addOrderBy('title')
      // Exclude these fields to keep it simpler for now - we just map to primary
      ->addWhere('name', 'NOT IN', ['id', 'location_type_id', 'master_id'])
      ->execute()->indexBy('name');
    foreach ($addressFields as $fieldName => $field) {
      // Set entity to contact as primary fields used in Contact actions
      $field['entity'] = 'Contact';
      $field['name'] = 'address_primary.' . $fieldName;
      $field['contact_type'] = ['Individual' => 'Individual', 'Organization' => 'Organization', 'Household' => 'Household'];
      $prefixedFields[$prefix . 'address_primary.' . $fieldName] = $field;
    }

    $phoneFields = (array) Phone::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->setAction('save')
      // Exclude these fields to keep it simpler for now - we just map to primary
      ->addWhere('name', 'NOT IN', ['id', 'location_type_id', 'phone_type_id'])
      ->addOrderBy('title')
      ->execute()->indexBy('name');
    foreach ($phoneFields as $fieldName => $field) {
      $field['entity'] = 'Contact';
      $field['name'] = 'phone_primary.' . $fieldName;
      $field['contact_type'] = ['Individual' => 'Individual', 'Organization' => 'Organization', 'Household' => 'Household'];
      $prefixedFields[$prefix . 'phone_primary.' . $fieldName] = $field;
    }

    $emailFields = (array) Email::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->setAction('save')
      // Exclude these fields to keep it simpler for now - we just map to primary
      ->addWhere('name', 'NOT IN', ['id', 'location_type_id'])
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    foreach ($emailFields as $fieldName => $field) {
      $field['entity'] = 'Contact';
      $field['name'] = 'email_primary.' . $fieldName;
      $field['contact_type'] = ['Individual' => 'Individual', 'Organization' => 'Organization', 'Household' => 'Household'];
      $prefixedFields[$prefix . 'email_primary.' . $fieldName] = $field;
    }
    return $prefixedFields;
  }

  /**
   * @param array $where
   * @param $name
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function loadRules(array $where = []) {
    $rules = DedupeRuleGroup::get(FALSE)
      ->setWhere($where)
      ->addSelect('threshold', 'name', 'id', 'title', 'contact_type')
      ->execute();
    foreach ($rules as $dedupeRule) {
      $fields = [];
      $name = $dedupeRule['name'];
      $this->dedupeRules[$name] = $dedupeRule;
      $this->dedupeRules[$name]['rule_message'] = $fieldMessage = '';
      // Now we add the fields in a format like ['first_name' => 6, 'custom_8' => 9]
      // The number is the weight and we add both api three & four style fields so the
      // array can be used for converted & unconverted.
      $ruleFields = DedupeRule::get(FALSE)
        ->addWhere('dedupe_rule_group_id', '=', $this->dedupeRules[$name]['id'])
        ->addSelect('id', 'rule_table', 'rule_field', 'rule_weight')
        ->execute();
      foreach ($ruleFields as $ruleField) {
        $fieldMessage .= ' ' . $ruleField['rule_field'] . '(weight ' . $ruleField['rule_weight'] . ')';
        if ($ruleField['rule_table'] === 'civicrm_contact') {
          $fields[$ruleField['rule_field']] = $ruleField['rule_weight'];
        }
        // If not a contact field we add both api variants of fields.
        elseif ($ruleField['rule_table'] === 'civicrm_phone') {
          // Actually the dedupe rule for phone should always be phone_numeric. so checking 'phone' is probably unncessary
          if (in_array($ruleField['rule_field'], ['phone', 'phone_numeric'], TRUE)) {
            $fields['phone'] = $ruleField['rule_weight'];
            $fields['phone_primary.phone'] = $ruleField['rule_weight'];
          }
        }
        elseif ($ruleField['rule_field'] === 'email') {
          $fields['email'] = $ruleField['rule_weight'];
          $fields['email_primary.email'] = $ruleField['rule_weight'];
        }
        elseif ($ruleField['rule_table'] === 'civicrm_address') {
          $fields[$ruleField['rule_field']] = $ruleField['rule_weight'];
          $fields['address_primary' . $ruleField['rule_field']] = $ruleField['rule_weight'];
        }
        else {
          // At this point it must be a custom field.
          $customField = CustomField::get(FALSE)
            ->addWhere('custom_group_id.table_name', '=', $ruleField['rule_table'])
            ->addWhere('column_name', '=', $ruleField['rule_field'])
            ->addSelect('id', 'name', 'custom_group_id.name')
            ->execute()
            ->first();
          $fields['custom_' . $customField['id']] = $ruleField['rule_weight'];
          $fields[$customField['custom_group_id.name'] . '.' . $customField['name']] = $ruleField['rule_weight'];
        }
      }
      $this->dedupeRules[$name]['rule_message'] = ts('Missing required contact matching fields.') . " $fieldMessage " . ts('(Sum of all weights should be greater than or equal to threshold: %1).', [1 => $this->dedupeRules[$name]['threshold']]) . '<br />';

      $this->dedupeRules[$name]['fields'] = $fields;
    }
  }

  /**
   * Get the dedupe rules to use to lookup a contact.
   *
   * @param array $dedupeRuleIDs
   * @param string|array|null $contact_type
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getDedupeRules(array $dedupeRuleIDs, $contact_type) {
    $dedupeRules = [];
    if (!empty($dedupeRuleIDs)) {
      foreach ($dedupeRuleIDs as $dedupeRuleID) {
        $dedupeRules[] = is_numeric($dedupeRuleID) ? $this->getDedupeRuleName($dedupeRuleID) : $dedupeRuleID;
      }
      return $dedupeRules;
    }
    $contactTypes = $contact_type ? (array) $contact_type : CRM_Contact_BAO_ContactType::basicTypes();
    foreach ($contactTypes as $contactType) {
      $dedupeRules[] = $this->getDefaultRuleForContactType($contactType);
    }
    return $dedupeRules;
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

}
