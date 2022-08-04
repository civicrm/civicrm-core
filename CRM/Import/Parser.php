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
use Civi\Api4\CustomField;
use Civi\Api4\Event;
use Civi\Api4\UserJob;
use Civi\UserJob\UserJobInterface;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
abstract class CRM_Import_Parser implements UserJobInterface {
  /**
   * Settings
   */
  const MAX_WARNINGS = 25, DEFAULT_TIMEOUT = 30;

  /**
   * Return codes
   */
  const VALID = 1, WARNING = 2, ERROR = 4, CONFLICT = 8, STOP = 16, DUPLICATE = 32, MULTIPLE_DUPE = 64, NO_MATCH = 128, UNPARSED_ADDRESS_WARNING = 256;

  /**
   * Parser modes
   */
  const MODE_MAPFIELD = 1, MODE_PREVIEW = 2, MODE_SUMMARY = 4, MODE_IMPORT = 8;

  /**
   * Codes for duplicate record handling
   */
  const DUPLICATE_SKIP = 1, DUPLICATE_UPDATE = 4, DUPLICATE_FILL = 8, DUPLICATE_NOCHECK = 16;

  /**
   * Contact types
   */
  const CONTACT_INDIVIDUAL = 1, CONTACT_HOUSEHOLD = 2, CONTACT_ORGANIZATION = 4;

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
   * Get User Job.
   *
   * API call to retrieve the userJob row.
   *
   * @return array
   *
   * @throws \API_Exception
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
   *
   * @throws \API_Exception
   */
  protected function getDataSourceObject(): ?CRM_Import_DataSource {
    $className = $this->getSubmittedValue('dataSource');
    if ($className) {
      /* @var CRM_Import_DataSource $dataSource */
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
   * @throws \API_Exception
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
    if (!$this->_contactType) {
      $contactTypeMapping = [
        CRM_Import_Parser::CONTACT_INDIVIDUAL => 'Individual',
        CRM_Import_Parser::CONTACT_HOUSEHOLD => 'Household',
        CRM_Import_Parser::CONTACT_ORGANIZATION => 'Organization',
      ];
      $this->_contactType = $contactTypeMapping[$this->getSubmittedValue('contactType')];
    }
    return $this->_contactType;
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
   * Total number of non empty lines
   * @var int
   */
  protected $_totalCount;

  /**
   * Running total number of valid lines
   * @var int
   */
  protected $_validCount;

  /**
   * Running total number of invalid rows
   * @var int
   */
  protected $_invalidRowCount;

  /**
   * Maximum number of non-empty/comment lines to process
   *
   * @var int
   */
  protected $_maxLinesToProcess;

  /**
   * Array of error lines, bounded by MAX_ERROR
   * @var array
   */
  protected $_errors;

  /**
   * Total number of duplicate (from database) lines
   * @var int
   */
  protected $_duplicateCount;

  /**
   * Array of duplicate lines
   * @var array
   */
  protected $_duplicates;

  /**
   * Maximum number of warnings to store
   * @var int
   */
  protected $_maxWarningCount = self::MAX_WARNINGS;

  /**
   * Array of warning lines, bounded by MAX_WARNING
   * @var array
   */
  protected $_warnings;

  /**
   * Array of all the fields that could potentially be part
   * of this import process
   * @var array
   */
  protected $_fields;

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
   * Gets the fields available for importing in a key-name, title format.
   *
   * @return array
   *   eg. ['first_name' => 'First Name'.....]
   *
   * @throws \API_Exception
   *
   * @todo - we are constructing the metadata before we
   * have set the contact type so we re-do it here.
   *
   * Once we have cleaned up the way the mapper is handled
   * we can ditch all the existing _construct parameters in favour
   * of just the userJobID - there are current open PRs towards this end.
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
   * @throws \API_Exception
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
   * Array of the fields that are actually part of the import process
   * the position in the array also dictates their position in the import
   * file
   * @var array
   */
  protected $_activeFields = [];

  /**
   * Cache the count of active fields
   *
   * @var int
   */
  protected $_activeFieldCount;

  /**
   * Cache of preview rows
   *
   * @var array
   */
  protected $_rows;

  /**
   * Filename of error data
   *
   * @var string
   */
  protected $_errorFileName;

  /**
   * Filename of duplicate data
   *
   * @var string
   */
  protected $_duplicateFileName;

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
   * Class constructor.
   */
  public function __construct() {
    $this->_maxLinesToProcess = 0;
  }

  /**
   * Set and validate field values.
   *
   * @param array $elements
   *   array.
   */
  public function setActiveFieldValues($elements): void {
    $maxCount = count($elements) < $this->_activeFieldCount ? count($elements) : $this->_activeFieldCount;
    for ($i = 0; $i < $maxCount; $i++) {
      $this->_activeFields[$i]->setValue($elements[$i]);
    }

    // reset all the values that we did not have an equivalent import element
    for (; $i < $this->_activeFieldCount; $i++) {
      $this->_activeFields[$i]->resetValue();
    }
  }

  /**
   * Format the field values for input to the api.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public function &getActiveFieldParams() {
    $params = [];
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if (isset($this->_activeFields[$i]->_value)
        && !isset($params[$this->_activeFields[$i]->_name])
        && !isset($this->_activeFields[$i]->_related)
      ) {

        $params[$this->_activeFields[$i]->_name] = $this->_activeFields[$i]->_value;
      }
    }
    return $params;
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
   * @return NULL|$currTimestamp
   */
  public function progressImport($statusID, $startImport = TRUE, $startTimestamp = NULL, $prevTimestamp = NULL, $totalRowCount = NULL) {
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
   * @return array
   */
  public function getSelectValues(): array {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_title;
    }
    return $values;
  }

  /**
   * @return array
   */
  public function getSelectTypes() {
    $values = [];
    // This is only called from the MapField form in isolation now,
    // so we need to set the metadata.
    $this->init();
    foreach ($this->_fields as $name => $field) {
      if (isset($field->_hasLocationType)) {
        $values[$name] = $field->_hasLocationType;
      }
    }
    return $values;
  }

  /**
   * @return array
   */
  public function getHeaderPatterns(): array {
    $values = [];
    foreach ($this->importableFieldsMetadata as $name => $field) {
      if (isset($field['headerPattern'])) {
        $values[$name] = $field['headerPattern'] ?: '//';
      }
    }
    return $values;
  }

  /**
   * @return array
   */
  public function getDataPatterns():array {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_dataPattern;
    }
    return $values;
  }

  /**
   * Remove single-quote enclosures from a value array (row).
   *
   * @param array $values
   * @param string $enclosure
   *
   * @return void
   */
  public static function encloseScrub(&$values, $enclosure = "'") {
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
   * @param int $max
   *
   * @return void
   */
  public function setMaxLinesToProcess($max) {
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
    $requiredFields = [
      'Individual' => [
        'first_name_last_name' => ['first_name' => ts('First Name'), 'last_name' => ts('Last Name')],
        'email' => ts('Email Address'),
      ],
      'Organization' => ['organization_name' => ts('Organization Name')],
      'Household' => ['household_name' => ts('Household Name')],
    ][$contactType];
    if ($isPermitExistingMatchFields) {
      $requiredFields['external_identifier'] = ts('External Identifier');
      // Historically just an email has been accepted as it is 'usually good enough'
      // for a dedupe rule look up - but really this is a stand in for
      // whatever is needed to find an existing matching contact using the
      // specified dedupe rule (or the default Unsupervised if not specified).
      $requiredFields['email'] = ts('Email Address');
    }
    $this->validateRequiredFields($requiredFields, $params, $prefixString);
  }

  protected function doPostImportActions() {
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

  public function queue() {
    $dataSource = $this->getDataSourceObject();
    $totalRowCount = $totalRows = $dataSource->getRowCount(['new']);
    $queue = Civi::queue('user_job_' . $this->getUserJobID(), ['type' => 'Sql', 'error' => 'abort']);
    $offset = 0;
    $batchSize = 50;
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
   * @var int $type error code constant
   * @return string
   */
  public static function errorFileName($type) {
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
   * Determines the file name based on error code.
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
   * @return array
   */
  protected function checkContactDuplicate(&$formatValues) {
    //retrieve contact id using contact dedupe rule
    $formatValues['contact_type'] = $formatValues['contact_type'] ?? $this->getContactType();
    $formatValues['version'] = 3;
    require_once 'CRM/Utils/DeprecatedUtils.php';
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

    return _civicrm_api3_deprecated_duplicate_formatted_contact($contactFormatted);
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
    require_once 'CRM/Contact/BAO/Contact.php';
    $contactFields = CRM_Contact_DAO_Contact::fields();
    _civicrm_api3_store_values($contactFields, $values, $params);

    if (isset($values['contact_type'])) {
      // we're an individual/household/org property

      $fields[$values['contact_type']] = CRM_Contact_DAO_Contact::fields();

      _civicrm_api3_store_values($fields[$values['contact_type']], $values, $params);
      return TRUE;
    }

    if (isset($values['individual_prefix'])) {
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
      static $fields = NULL;
      if ($fields == NULL) {
        $fields = [];
      }

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
        if (CRM_Utils_Array::value('location_type_id', $values) ==
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
   * @param string|int|null $submittedValue
   * @param array $fieldSpec
   *   Metadata for the field
   *
   * @return mixed
   */
  protected function parsePseudoConstantField($submittedValue, $fieldSpec) {
    // dev/core#1289 Somehow we have wound up here but the BAO has not been specified in the fieldspec so we need to check this but future us problem, for now lets just return the submittedValue
    if (!isset($fieldSpec['bao'])) {
      return $submittedValue;
    }
    /* @var \CRM_Core_DAO $bao */
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
   *     'email' => ts('Email'),
   *     ['first_name' => ts('First Name'), 'last_name' => ts('Last Name')]
   *   ]
   *   Means 'email' OR 'first_name AND 'last_name'.
   * @param string $prefixString
   *
   * @throws \CRM_Core_Exception Exception thrown if field requirements are not met.
   */
  protected function validateRequiredFields(array $requiredFields, array $params, $prefixString = ''): void {
    if (empty($requiredFields)) {
      return;
    }
    $missingFields = [];
    foreach ($requiredFields as $key => $required) {
      if (!is_array($required)) {
        $importParameter = $params[$key] ?? [];
        if (!is_array($importParameter)) {
          if (!empty($importParameter)) {
            return;
          }
        }
        else {
          foreach ($importParameter as $locationValues) {
            if (!empty($locationValues[$key])) {
              return;
            }
          }
        }

        $missingFields[$key] = $required;
      }
      else {
        foreach ($required as $field => $label) {
          if (empty($params[$field])) {
            $missing[$field] = $label;
          }
        }
        if (empty($missing)) {
          return;
        }
        $missingFields[$key] = implode(' ' . ts('and') . ' ', $missing);
      }
    }
    throw new CRM_Core_Exception($prefixString . ts('Missing required fields:') . ' ' . implode(' ' . ts('OR') . ' ', $missingFields));
  }

  /**
   * Get the field value, transformed by metadata.
   *
   * @param string $fieldName
   * @param string|int $importedValue
   *   Value as it came in from the datasource.
   *
   * @return string|array|bool|int
   * @throws \API_Exception
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

    if ($fieldMetadata['type'] === CRM_Utils_Type::T_FLOAT) {
      return CRM_Utils_Rule::numeric($importedValue) ? $importedValue : 'invalid_import_value';
    }
    if ($fieldMetadata['type'] === CRM_Utils_Type::T_MONEY) {
      return CRM_Utils_Rule::money($importedValue, TRUE) ? CRM_Utils_Rule::cleanMoney($importedValue) : 'invalid_import_value';
    }
    if ($fieldMetadata['type'] === CRM_Utils_Type::T_BOOLEAN) {
      $value = CRM_Utils_String::strtoboolstr($importedValue);
      if ($value !== FALSE) {
        return (bool) $value;
      }
      return 'invalid_import_value';
    }
    if ($fieldMetadata['type'] === CRM_Utils_Type::T_DATE || $fieldMetadata['type'] === (CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME) || $fieldMetadata['type'] === CRM_Utils_Type::T_TIMESTAMP) {
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
      return $options[$comparisonValue] ?? 'invalid_import_value';
    }
    if (!empty($fieldMetadata['FKClassName']) || !empty($fieldMetadata['pseudoconstant']['prefetch'])) {
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
          $campaign = Campaign::get()->addClause('OR', ['title', '=', $importedValue], ['name', '=', $importedValue])->addSelect('id')->execute()->first();
          Civi::$statics[__CLASS__][$fieldName][$importedValue] = $campaign['id'] ?? FALSE;
        }
        return Civi::$statics[__CLASS__][$fieldName][$importedValue] ?? 'invalid_import_value';
      }
    }
    if ($fieldMetadata['type'] === CRM_Utils_Type::T_INT) {
      // We have resolved the options now so any remaining ones should be integers.
      return CRM_Utils_Rule::numeric($importedValue) ? $importedValue : 'invalid_import_value';
    }
    return $importedValue;
  }

  /**
   * @param string $fieldName
   *
   * @return false|array
   *
   * @throws \API_Exception
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

    // This whole business of only loading metadata for one type when we actually need it for all is ... dubious.
    if (empty($this->getImportableFieldsMetadata()[$fieldMapName])) {
      if ($loadOptions || !$limitToContactType) {
        $this->importableFieldsMetadata[$fieldMapName] = CRM_Contact_BAO_Contact::importableFields('All')[$fieldMapName];
      }
    }

    $fieldMetadata = $this->getImportableFieldsMetadata()[$fieldMapName];
    if ($loadOptions && !isset($fieldMetadata['options'])) {
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
          $customField = CustomField::get(FALSE)
            ->addWhere('id', '=', $fieldMetadata['custom_field_id'])
            ->addSelect('name', 'custom_group_id.name')
            ->execute()
            ->first();
          $optionFieldName = $customField['custom_group_id.name'] . '.' . $customField['name'];
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
   * @param $customFieldID
   * @param $value
   * @param array $fieldMetaData
   * @param $dateType
   *
   * @return ?string
   */
  protected function validateCustomField($customFieldID, $value, array $fieldMetaData, $dateType): ?string {
    /* validate the data against the CF type */

    if ($value) {
      $dataType = $fieldMetaData['data_type'];
      $htmlType = $fieldMetaData['html_type'];
      $isSerialized = CRM_Core_BAO_CustomField::isSerialized($fieldMetaData);
      if ($dataType === 'Date') {
        $params = ['date_field' => $value];
        if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, 'date_field')) {
          return NULL;
        }
        return $fieldMetaData['label'];
      }
      elseif ($dataType === 'Boolean') {
        if (CRM_Utils_String::strtoboolstr($value) === FALSE) {
          return $fieldMetaData['label'] . '::' . $fieldMetaData['groupTitle'];
        }
      }
      // need not check for label filed import
      $selectHtmlTypes = [
        'CheckBox',
        'Select',
        'Radio',
      ];
      if ((!$isSerialized && !in_array($htmlType, $selectHtmlTypes)) || $dataType == 'Boolean' || $dataType == 'ContactReference') {
        $valid = CRM_Core_BAO_CustomValue::typecheck($dataType, $value);
        if (!$valid) {
          return $fieldMetaData['label'];
        }
      }

      // check for values for custom fields for checkboxes and multiselect
      if ($isSerialized && $dataType != 'ContactReference') {
        $mulValues = array_filter(explode(',', str_replace('|', ',', trim($value))), 'strlen');
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        foreach ($mulValues as $v1) {

          $flag = FALSE;
          foreach ($customOption as $v2) {
            if ((strtolower(trim($v2['label'])) == strtolower(trim($v1))) || (strtolower(trim($v2['value'])) == strtolower(trim($v1)))) {
              $flag = TRUE;
            }
          }

          if (!$flag) {
            return $fieldMetaData['label'];
          }
        }
      }
      elseif ($htmlType == 'Select' || ($htmlType == 'Radio' && $dataType != 'Boolean')) {
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        $flag = FALSE;
        foreach ($customOption as $v2) {
          if ((strtolower(trim($v2['label'])) == strtolower(trim($value))) || (strtolower(trim($v2['value'])) == strtolower(trim($value)))) {
            $flag = TRUE;
          }
        }
        if (!$flag) {
          return $fieldMetaData['label'];
        }
      }
    }

    return NULL;
  }

  /**
   * Get the entity for the given field.
   *
   * @param string $fieldName
   *
   * @return mixed|null
   * @throws \API_Exception
   */
  protected function getFieldEntity(string $fieldName) {
    if ($fieldName === 'do_not_import') {
      return NULL;
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function validate(): void {
    $dataSource = $this->getDataSourceObject();
    while ($row = $dataSource->getRow()) {
      try {
        $rowNumber = $row['_id'];
        $values = array_values($row);
        $this->validateValues($values);
        $this->setImportStatus($rowNumber, 'NEW', '');
      }
      catch (CRM_Core_Exception $e) {
        $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      }
    }
  }

  /**
   * Validate the import values.
   *
   * The values array represents a row in the datasource.
   *
   * @param array $values
   *
   * @throws \API_Exception
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
    ];
  }

  /**
   * Get the default country for the site.
   *
   * @return int
   */
  protected function getSiteDefaultCountry(): int {
    if (!isset($this->siteDefaultCountry)) {
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
   * @param array $mappedField
   *   Field detail as would be saved in field_mapping table
   *   or as returned from getMappingFieldFromMapperInput
   *
   * @return string
   * @throws \API_Exception
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
   * @throws \API_Exception
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
   * @throws \API_Exception
   */
  protected function getFieldMappings(): array {
    $mappedFields = [];
    $mapper = $this->getSubmittedValue('mapper');
    foreach ($mapper as $i => $mapperRow) {
      $mappedField = $this->getMappingFieldFromMapperInput($mapperRow, 0, $i);
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public static function runJob(\CRM_Queue_TaskContext $taskContext, int $userJobID, int $limit, int $offset): bool {
    $userJob = UserJob::get()->addWhere('id', '=', $userJobID)->addSelect('job_type')->execute()->first();
    $parserClass = NULL;
    foreach (CRM_Core_BAO_UserJob::getTypes() as $userJobType) {
      if ($userJob['job_type'] === $userJobType['id']) {
        $parserClass = $userJobType['class'];
      }
    }
    /* @var \CRM_Import_Parser $parser */
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
   * Check if an error in custom data.
   *
   * @deprecated all of this is duplicated if getTransformedValue is used.
   *
   * @param array $params
   * @param string $errorMessage
   *   A string containing all the error-fields.
   *
   * @param null $csType
   */
  public function isErrorInCustomData($params, &$errorMessage, $csType = NULL) {
    $dateType = CRM_Core_Session::singleton()->get("dateTypes");
    $errors = [];

    if (!empty($params['contact_sub_type'])) {
      $csType = $params['contact_sub_type'] ?? NULL;
    }

    if (empty($params['contact_type'])) {
      $params['contact_type'] = 'Individual';
    }

    // get array of subtypes - CRM-18708
    if (in_array($csType, CRM_Contact_BAO_ContactType::basicTypes(TRUE), TRUE)) {
      $csType = $this->getSubtypes($params['contact_type']);
    }

    if (is_array($csType)) {
      // fetch custom fields for every subtype and add it to $customFields array
      // CRM-18708
      $customFields = [];
      foreach ($csType as $cType) {
        $customFields += CRM_Core_BAO_CustomField::getFields($params['contact_type'], FALSE, FALSE, $cType);
      }
    }
    else {
      $customFields = CRM_Core_BAO_CustomField::getFields($params['contact_type'], FALSE, FALSE, $csType);
    }

    foreach ($params as $key => $value) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        //For address custom fields, we do get actual custom field value as an inner array of
        //values so need to modify
        if (!array_key_exists($customFieldID, $customFields)) {
          return ts('field ID');
        }
        /* check if it's a valid custom field id */
        $errors[] = $this->validateCustomField($customFieldID, $value, $customFields[$customFieldID], $dateType);
      }
    }
    if ($errors) {
      $errorMessage .= ($errorMessage ? '; ' : '') . implode('; ', array_filter($errors));
    }
  }

  /**
   * get subtypes given the contact type
   *
   * @param string $contactType
   * @return array $subTypes
   */
  protected function getSubtypes($contactType) {
    $subTypes = [];
    $types = CRM_Contact_BAO_ContactType::subTypeInfo($contactType);

    if (count($types) > 0) {
      foreach ($types as $type) {
        $subTypes[] = $type['name'];
      }
    }
    return $subTypes;
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
   * Convert any given date string to default date array.
   *
   * @param array $params
   *   Has given date-format.
   * @param array $formatted
   *   Store formatted date in this array.
   * @param int $dateType
   *   Type of date.
   * @param string $dateParam
   *   Index of params.
   */
  public static function formatCustomDate(&$params, &$formatted, $dateType, $dateParam) {
    //fix for CRM-2687
    CRM_Utils_Date::convertToDefaultDate($params, $dateType, $dateParam);
    $formatted[$dateParam] = CRM_Utils_Date::processDate($params[$dateParam]);
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

}
