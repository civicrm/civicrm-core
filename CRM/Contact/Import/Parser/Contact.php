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

use Civi\Api4\Contact;
use Civi\Api4\County;
use Civi\Api4\RelationshipType;
use Civi\Api4\StateProvince;
use Civi\Api4\DedupeRuleGroup;

require_once 'api/v3/utils.php';

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * class to parse contact csv files
 */
class CRM_Contact_Import_Parser_Contact extends CRM_Import_Parser {

  private $externalIdentifiers = [];

  /**
   * Array of successfully imported contact id's
   *
   * @var array
   */
  protected $_newContacts = [];

  protected $_tableName;

  /**
   * Total number of lines in file
   *
   * @var int
   */
  protected $_rowCount;

  protected $fieldMetadata = [];

  /**
   * Relationship labels.
   *
   * Temporary cache of labels to reduce queries in getRelationshipLabels.
   *
   * @var array
   *   e.g ['5a_b' => 'Employer', '5b_a' => 'Employee']
   */
  protected $relationshipLabels = [];

  /**
   * Addresses that failed to parse.
   *
   * @var array
   */
  private $_unparsedStreetAddressContacts = [];

  /**
   * The initializer code, called before processing.
   */
  public function init() {
    // Force re-load of user job.
    unset($this->userJob);
    $this->setFieldMetadata();
  }

  /**
   * Get information about the provided job.
   *
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'contact_import' => [
        'id' => 'contact_import',
        'name' => 'contact_import',
        'label' => ts('Contact Import'),
        'entity' => 'Contact',
        'url' => 'civicrm/import/contact',
      ],
    ];
  }

  /**
   * Get the fields to track the import.
   *
   * @return array
   */
  public function getTrackingFields(): array {
    return [
      'related_contact_created' => [
        'name' => 'related_contact_created',
        'operation' => 'SUM',
        'type' => 'INT',
        'description' => ts('Number of related contacts created'),
      ],
      'related_contact_matched' => [
        'name' => 'related_contact_matched',
        'operation' => 'SUM',
        'type' => 'INT',
        'description' => ts('Number of related contacts found (and potentially updated)'),
      ],
    ];
  }

  /**
   * Is street address parsing enabled for the site.
   */
  protected function isParseStreetAddress() : bool {
    return (bool) (CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'address_options')['street_address_parsing'] ?? FALSE);
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import(array $values): void {
    $rowNumber = (int) $values[array_key_last($values)];

    // Put this here for now since we're gettting run by a job and need to
    // reset it for each task run.
    CRM_Utils_Address_USPS::disable($this->getSubmittedValue('disableUSPS'));

    $this->_unparsedStreetAddressContacts = [];
    if (!$this->getSubmittedValue('doGeocodeAddress')) {
      // CRM-5854, reset the geocode method to null to prevent geocoding
      CRM_Utils_GeocodeProvider::disableForSession();
    }
    $relatedContacts = [];

    try {
      $params = $this->getMappedRow($values);
      $formatted = [];
      foreach ($params as $key => $value) {
        if ($value !== '') {
          $formatted[$key] = $value;
        }
      }

      $formatted['id'] = $params['id'] = $this->processContact($params, TRUE);

      //format common data, CRM-4062
      $this->formatCommonData($params, $formatted);

      $newContact = $this->createContact($formatted, $params['id'] ?? NULL);
      $contactID = $newContact->id;

      if ($contactID) {
        // call import hook
        $currentImportID = end($values);
        $hookParams = [
          'contactID' => $contactID,
          'importID' => $currentImportID,
        ];
        CRM_Utils_Hook::import('Contact', 'process', $this, $hookParams);
      }

      $primaryContactId = $newContact->id;

      //relationship contact insert
      foreach ($this->getRelatedContactsParams($params) as $key => $field) {
        $field['id'] = $this->processContact($field, FALSE);
        $formatting = $field;
        //format common data, CRM-4062
        $this->formatCommonData($field, $formatting);
        $isUpdate = empty($formatting['id']) ? 'new' : 'updated';
        if (empty($formatting['id']) || $this->isUpdateExisting()) {
          $relatedNewContact = $this->createContact($formatting, $formatting['id']);
          $formatting['id'] = $relatedNewContact->id;
        }
        if (empty($relatedContacts[$formatting['id']])) {
          $relatedContacts[$formatting['id']] = $isUpdate;
        }

        $this->createRelationship($key, $formatting['id'], $primaryContactId);
      }
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, $this->getStatus($e->getErrorCode()), $e->getMessage());
      return;
    }
    $extraFields = ['related_contact_created' => 0, 'related_contact_matched' => 0];
    foreach ($relatedContacts as $outcome) {
      if ($outcome === 'new') {
        $extraFields['related_contact_created']++;
      }
      else {
        $extraFields['related_contact_matched']++;
      }
    }
    $this->setImportStatus($rowNumber, $this->getStatus(CRM_Import_Parser::VALID), $this->getSuccessMessage(), $contactID, $extraFields, array_merge(array_keys($relatedContacts), [$contactID]));
  }

  /**
   * Only called from import now... plus one place outside of core & tests.
   *
   * @todo - deprecate more aggressively - will involve copying to the import
   * class, adding a deprecation notice here & removing from tests.
   *
   * Takes an associative array and creates a relationship object.
   *
   * @deprecated For single creates use the api instead (it's tested).
   * For multiple a new variant of this function needs to be written and migrated to as this is a bit
   * nasty
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $ids
   *   The array that holds all the db ids.
   *   per http://wiki.civicrm.org/confluence/display/CRM/Database+layer
   *  "we are moving away from the $ids param "
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private static function legacyCreateMultiple($params, $ids = []) {
    // clarify that the only key ever pass in the ids array is 'contact'
    // There is legacy handling for other keys but a universe search on
    // calls to this function (not supported to be called from outside core)
    // only returns 2 calls - one in CRM_Contact_Import_Parser_Contact
    // and the other in jma grant applications (CRM_Grant_Form_Grant_Confirm)
    // both only pass in contact as a key here.
    $contactID = $ids['contact'];
    unset($ids);
    // There is only ever one value passed in from the 2 places above that call
    // this - by clarifying here like this we can cleanup within this
    // function without having to do more universe searches.
    $relatedContactID = key($params['contact_check']);

    // check if the relationship is valid between contacts.
    // step 1: check if the relationship is valid if not valid skip and keep the count
    // step 2: check the if two contacts already have a relationship if yes skip and keep the count
    // step 3: if valid relationship then add the relation and keep the count

    // step 1
    [$contactFields['relationship_type_id'], $firstLetter, $secondLetter] = explode('_', $params['relationship_type_id']);
    $contactFields['contact_id_' . $firstLetter] = $contactID;
    $contactFields['contact_id_' . $secondLetter] = $relatedContactID;
    if (!CRM_Contact_BAO_Relationship::checkRelationshipType($contactFields['contact_id_a'], $contactFields['contact_id_b'],
      $contactFields['relationship_type_id'])) {
      return [0, 0];
    }

    if (
      CRM_Contact_BAO_Relationship::checkDuplicateRelationship(
        $contactFields,
        (int) $contactID,
        // step 2
        (int) $relatedContactID
      )
    ) {
      return [0, 1];
    }

    $singleInstanceParams = array_merge($params, $contactFields);
    CRM_Contact_BAO_Relationship::add($singleInstanceParams);
    return [1, 0];
  }

  /**
   * Format common params data to the format that was required a very long time ago.
   *
   * I think the only useful things this function does now are
   *  1) calls fillPrimary
   *  2) possibly the street address parsing.
   *
   * The other hundred lines do stuff that is done elsewhere. Custom fields
   * should already be formatted by getTransformedValue and we don't need to
   * re-rewrite them to a BAO style array since we call the api which does that.
   *
   * The call to formatLocationBlock just does the address custom fields which,
   * are already formatted by this point.
   *
   * @deprecated
   *
   * @param array $params
   *   Contain record values.
   * @param array $formatted
   *   Array of formatted data.
   */
  private function formatCommonData($params, &$formatted) {
    // @todo - remove just about everything in this function. See docblock.
    $customFields = CRM_Core_BAO_CustomField::getFields($formatted['contact_type'], FALSE, FALSE, $formatted['contact_sub_type'] ?? NULL);

    $addressCustomFields = CRM_Core_BAO_CustomField::getFields('Address');
    $customFields = $customFields + $addressCustomFields;

    foreach ($params as $key => $val) {
      $customFieldID = CRM_Core_BAO_CustomField::getKeyID($key);
      if ($customFieldID &&
        !array_key_exists($customFieldID, $addressCustomFields)
      ) {
        // @todo - this can probably go....
        if ($customFields[$customFieldID]['data_type'] === 'Boolean') {
          if (empty($val) && !is_numeric($val) && $this->isFillDuplicates()) {
            //retain earlier value when Import mode is `Fill`
            unset($params[$key]);
          }
        }
      }
    }
    $metadataBlocks = ['phone', 'im', 'openid', 'email', 'address'];
    foreach ($metadataBlocks as $block) {
      foreach ($formatted[$block] ?? [] as $blockKey => $blockValues) {
        if ($blockValues['location_type_id'] === 'Primary') {
          $this->fillPrimary($formatted[$block][$blockKey], $blockValues, $block, $formatted['id'] ?? NULL);
        }
      }
    }
    //now format custom data.
    foreach ($params as $key => $field) {
      if (in_array($key, $metadataBlocks, TRUE)) {
        // This location block is already fully handled at this point.
        continue;
      }
      if (is_array($field)) {
        $isAddressCustomField = FALSE;

        foreach ($field as $value) {
          $break = FALSE;
          if (is_array($value)) {
            foreach ($value as $name => $testForEmpty) {
              if ($addressCustomFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
                $isAddressCustomField = TRUE;
                break;
              }

              if (($testForEmpty === '' || $testForEmpty == NULL)) {
                $break = TRUE;
                break;
              }
            }
          }
          else {
            $break = TRUE;
          }

          if (!$break) {
            if (!empty($value['location_type_id'])) {
              $this->formatLocationBlock($value, $formatted);
            }
          }
        }
        if (!$isAddressCustomField) {
          continue;
        }
      }

      $formatValues = [
        $key => $field,
      ];

      if ($key == 'id' && isset($field)) {
        $formatted[$key] = $field;
      }
      $this->formatContactParameters($formatValues, $formatted);

      //Handling Custom Data
      // note: Address custom fields will be handled separately inside formatContactParameters
      if (($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) &&
        array_key_exists($customFieldID, $customFields) &&
        !array_key_exists($customFieldID, $addressCustomFields)
      ) {

        $extends = $customFields[$customFieldID]['extends'] ?? NULL;
        $htmlType = $customFields[$customFieldID]['html_type'] ?? NULL;
        $dataType = $customFields[$customFieldID]['data_type'] ?? NULL;
        $serialized = CRM_Core_BAO_CustomField::isSerialized($customFields[$customFieldID]);

        if (!$serialized && in_array($htmlType, ['Select', 'Radio', 'Autocomplete-Select']) && in_array($dataType, ['String', 'Int'])) {
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
          foreach ($customOption as $customValue) {
            $val = $customValue['value'] ?? NULL;
            $label = strtolower($customValue['label'] ?? '');
            $value = strtolower(trim($formatted[$key] ?? ''));
            if (($value == $label) || ($value == strtolower($val))) {
              $params[$key] = $formatted[$key] = $val;
            }
          }
        }
        elseif ($serialized && !empty($formatted[$key]) && !empty($params[$key])) {
          $mulValues = explode(',', $formatted[$key]);
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
          $formatted[$key] = [];
          $params[$key] = [];
          foreach ($mulValues as $v1) {
            foreach ($customOption as $v2) {
              if ((strtolower($v2['label']) == strtolower(trim($v1))) ||
                (strtolower($v2['value']) == strtolower(trim($v1)))
              ) {
                $params[$key][] = $formatted[$key][] = $v2['value'];
              }
            }
          }
        }
      }
    }

    if (!empty($key) && ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) && array_key_exists($customFieldID, $customFields) &&
      !array_key_exists($customFieldID, $addressCustomFields)
    ) {
      // @todo calling api functions directly is not supported
      _civicrm_api3_custom_format_params($params, $formatted, $extends);
    }

    // parse street address, CRM-5450
    if ($this->isParseStreetAddress()) {
      if (array_key_exists('address', $formatted) && is_array($formatted['address'])) {
        foreach ($formatted['address'] as $instance => & $address) {
          $streetAddress = $address['street_address'] ?? NULL;
          if (empty($streetAddress)) {
            continue;
          }
          // parse address field.
          $parsedFields = CRM_Core_BAO_Address::parseStreetAddress($streetAddress);

          //street address consider to be parsed properly,
          //If we get street_name and street_number.
          if (empty($parsedFields['street_name']) || empty($parsedFields['street_number'])) {
            $parsedFields = array_fill_keys(array_keys($parsedFields), '');
          }

          // merge parse address w/ main address block.
          $address = array_merge($address, $parsedFields);
        }
      }
    }
  }

  /**
   * Build error-message containing error-fields
   *
   * Once upon a time there was a dev who hadn't heard of implode. That dev wrote this function.
   *
   * @todo just say no!
   *
   * @param string $errorName
   *   A string containing error-field name.
   * @param string $errorMessage
   *   A string containing all the error-fields, where the new errorName is concatenated.
   *
   */
  public static function addToErrorMsg($errorName, &$errorMessage) {
    if ($errorMessage) {
      $errorMessage .= "; $errorName";
    }
    else {
      $errorMessage = $errorName;
    }
  }

  /**
   * Get sorted available relationships.
   *
   * @return array
   */
  protected function getRelationships(): array {
    $cacheKey = 'importable_contact_relationship_field_metadata' . $this->getContactType() . $this->getContactSubType();
    if (Civi::cache('fields')->has($cacheKey)) {
      return Civi::cache('fields')->get($cacheKey);
    }
    //Relationship importables
    $relations = CRM_Contact_BAO_Relationship::getContactRelationshipType(
      NULL, NULL, NULL, $this->getContactType(),
      FALSE, 'label', TRUE, $this->getContactSubType()
    );
    asort($relations);
    Civi::cache('fields')->set($cacheKey, $relations);
    return $relations;
  }

  /**
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  protected function validateParams(array $params): void {
    $contacts = array_merge(['0' => $params], $this->getRelatedContactsParams($params));
    $errors = [];
    foreach ($contacts as $value) {
      // If we are referencing a related contact, or are in update mode then we
      // don't need all the required fields if we have enough to find an existing contact.
      $useExistingMatchFields = !empty($value['relationship_type_id']) || $this->isUpdateExisting();
      $prefixString = !empty($value['relationship_label']) ? '(' . $value['relationship_label'] . ') ' : '';
      $this->validateRequiredContactFields($value['contact_type'], $value, $useExistingMatchFields, $prefixString);

      $errors = array_merge($errors, $this->getInvalidValuesForContact($value, $prefixString));
      if (!empty($value['contact_sub_type']) && !CRM_Contact_BAO_ContactType::isExtendsContactType($value['contact_sub_type'], $value['contact_type'])) {
        $errors[] = ts('Mismatched or Invalid Contact Subtype.');
      }
      if (!empty($value['relationship_type_id'])) {
        $requiredSubType = $this->getRelatedContactSubType($value['relationship_type_id'], $value['relationship_direction']);
        if ($requiredSubType && $value['contact_sub_type'] && $requiredSubType !== $value['contact_sub_type']) {
          // Tested in CRM_Contact_Import_Parser_ContactTest::testImportContactSubTypes
          throw new CRM_Core_Exception($prefixString . ts('Mismatched or Invalid contact subtype found for this related contact.'), CRM_Import_Parser::ERROR);
        }
      }
    }
    $this->checkForDuplicateExternalIdentifiers($params['external_identifier'] ?? '');

    //date-format part ends

    $errorMessage = implode(', ', $errors);

    //checking error in core data
    if ($errorMessage) {
      $tempMsg = "Invalid value for field(s) : $errorMessage";
      throw new CRM_Core_Exception($tempMsg);
    }
  }

  /**
   * @param $key
   * @param $relContactId
   * @param $primaryContactId
   *
   * @throws \CRM_Core_Exception
   */
  protected function createRelationship($key, $relContactId, $primaryContactId): void {
    //if more than one duplicate contact
    //found, create relationship with first contact
    // now create the relationship record
    $relationParams = [
      'relationship_type_id' => $key,
      'contact_check' => [
        $relContactId => 1,
      ],
      'is_active' => 1,
      'skipRecentView' => TRUE,
    ];

    // we only handle related contact success, we ignore failures for now
    // at some point wold be nice to have related counts as separate
    $relationIds = [
      'contact' => $primaryContactId,
    ];

    [$valid, $duplicate] = self::legacyCreateMultiple($relationParams, $relationIds);

    if ($valid || $duplicate) {
      $relationIds['contactTarget'] = $relContactId;
      $action = ($duplicate) ? CRM_Core_Action::UPDATE : CRM_Core_Action::ADD;
      CRM_Contact_BAO_Relationship::relatedMemberships($primaryContactId, $relationParams, $relationIds, $action);
    }

    //handle current employer, CRM-3532
    if ($valid) {
      $allRelationships = CRM_Core_PseudoConstant::relationshipType('name');
      $relationshipTypeId = str_replace([
        '_a_b',
        '_b_a',
      ], [
        '',
        '',
      ], $key);
      $relationshipType = str_replace($relationshipTypeId . '_', '', $key);
      $orgId = $individualId = NULL;
      if ($allRelationships[$relationshipTypeId]["name_{$relationshipType}"] == 'Employee of') {
        $orgId = $relContactId;
        $individualId = $primaryContactId;
      }
      elseif ($allRelationships[$relationshipTypeId]["name_{$relationshipType}"] == 'Employer of') {
        $orgId = $primaryContactId;
        $individualId = $relContactId;
      }
      if ($orgId && $individualId) {
        $currentEmpParams[$individualId] = $orgId;
        CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($currentEmpParams);
      }
    }
  }

  /**
   * Method for creating contact.
   *
   * @param array $formatted
   * @param int $contactId
   *
   * @return \CRM_Contact_BAO_Contact
   *   If a duplicate is found an array is returned, otherwise CRM_Contact_BAO_Contact
   */
  public function createContact(&$formatted, $contactId = NULL) {

    if ($contactId) {
      $this->formatParams($formatted, (int) $contactId);
    }

    // Resetting and rebuilding cache could be expensive.
    CRM_Core_Config::setPermitCacheFlushMode(FALSE);

    // If a user has logged in, or accessed via a checksum
    // Then deliberately 'blanking' a value in the profile should remove it from their record
    // @todo this should either be TRUE or FALSE in the context of import - once
    // we figure out which we can remove all the rest.
    // Also note the meaning of this parameter is less than it used to
    // be following block cleanup.
    $formatted['updateBlankLocInfo'] = TRUE;
    if ((CRM_Core_Session::singleton()->get('authSrc') & (CRM_Core_Permission::AUTH_SRC_CHECKSUM + CRM_Core_Permission::AUTH_SRC_LOGIN)) == 0) {
      $formatted['updateBlankLocInfo'] = FALSE;
    }

    $contactFields = CRM_Contact_DAO_Contact::import();
    [$data, $contactDetails] = $this->formatProfileContactParams($formatted, $contactFields, $contactId, $formatted['contact_type']);

    // manage is_opt_out
    if (array_key_exists('is_opt_out', $contactFields) && array_key_exists('is_opt_out', $formatted)) {
      $wasOptOut = $contactDetails['is_opt_out'] ?? FALSE;
      $isOptOut = $formatted['is_opt_out'];
      $data['is_opt_out'] = $isOptOut;
      // on change, create new civicrm_subscription_history entry
      if (($wasOptOut != $isOptOut) && !empty($contactDetails['contact_id'])) {
        $shParams = [
          'contact_id' => $contactDetails['contact_id'],
          'status' => $isOptOut ? 'Removed' : 'Added',
          'method' => 'Web',
        ];
        CRM_Contact_BAO_SubscriptionHistory::create($shParams);
      }
    }

    $contact = civicrm_api3('Contact', 'create', $data);
    $cid = $contact['id'];

    CRM_Core_Config::setPermitCacheFlushMode(TRUE);

    $contact = [
      'contact_id' => $cid,
    ];

    $defaults = [];
    $newContact = CRM_Contact_BAO_Contact::retrieve($contact, $defaults);

    //get the id of the contact whose street address is not parsable, CRM-5886
    if ($this->isParseStreetAddress() && property_exists($newContact, 'address') && $newContact->address) {
      foreach ($newContact->address as $address) {
        if (!empty($address['street_address']) && (empty($address['street_number']) || empty($address['street_name']))) {
          $this->_unparsedStreetAddressContacts[] = [
            'id' => $newContact->id,
            'streetAddress' => $address['street_address'],
          ];
        }
      }
    }
    return $newContact;
  }

  /**
   * Legacy format profile contact parameters.
   *
   * This is a formerly shared function - most of the stuff in it probably does
   * nothing but copied here to star unravelling that...
   *
   * @param array $params
   * @param array $fields
   * @param int|null $contactID
   * @param string|null $ctype
   *
   * @return array
   */
  private function formatProfileContactParams(
    &$params,
    $fields,
    $contactID = NULL,
    $ctype = NULL
  ) {

    $data = $contactDetails = [];

    // get the contact details (hier)
    if ($contactID) {
      $details = CRM_Contact_BAO_Contact::getHierContactDetails($contactID, $fields);

      $contactDetails = $details[$contactID];
      $data['contact_type'] = $contactDetails['contact_type'] ?? NULL;
      $data['contact_sub_type'] = $contactDetails['contact_sub_type'] ?? NULL;
    }
    else {
      //we should get contact type only if contact
      if ($ctype) {
        $data['contact_type'] = $ctype;
      }
      else {
        $data['contact_type'] = 'Individual';
      }
    }

    //fix contact sub type CRM-5125
    if (array_key_exists('contact_sub_type', $params) &&
      !empty($params['contact_sub_type'])
    ) {
      $data['contact_sub_type'] = CRM_Utils_Array::implodePadded($params['contact_sub_type']);
    }
    elseif (array_key_exists('contact_sub_type_hidden', $params) &&
      !empty($params['contact_sub_type_hidden'])
    ) {
      // if profile was used, and had any subtype, we obtain it from there
      //CRM-13596 - add to existing contact types, rather than overwriting
      if (empty($data['contact_sub_type'])) {
        // If we don't have a contact ID the $data['contact_sub_type'] will not be defined...
        $data['contact_sub_type'] = CRM_Utils_Array::implodePadded($params['contact_sub_type_hidden']);
      }
      else {
        $data_contact_sub_type_arr = CRM_Utils_Array::explodePadded($data['contact_sub_type']);
        if (!in_array($params['contact_sub_type_hidden'], $data_contact_sub_type_arr)) {
          //CRM-20517 - make sure contact_sub_type gets the correct delimiters
          $data['contact_sub_type'] = trim($data['contact_sub_type'], CRM_Core_DAO::VALUE_SEPARATOR);
          $data['contact_sub_type'] = CRM_Core_DAO::VALUE_SEPARATOR . $data['contact_sub_type'] . CRM_Utils_Array::implodePadded($params['contact_sub_type_hidden']);
        }
      }
    }

    if ($ctype == 'Organization') {
      $data['organization_name'] = $contactDetails['organization_name'] ?? NULL;
    }
    elseif ($ctype == 'Household') {
      $data['household_name'] = $contactDetails['household_name'] ?? NULL;
    }

    $locationType = [];
    $count = 1;

    if ($contactID) {
      //add contact id
      $data['contact_id'] = $contactID;
      $primaryLocationType = CRM_Contact_BAO_Contact::getPrimaryLocationType($contactID);
    }
    else {
      $defaultLocation = CRM_Core_BAO_LocationType::getDefault();
      $defaultLocationId = $defaultLocation->id;
    }

    $billingLocationTypeId = CRM_Core_BAO_LocationType::getBilling();

    $multiplFields = ['url'];

    $session = CRM_Core_Session::singleton();
    foreach ($params as $key => $value) {
      [$fieldName, $locTypeId, $typeId] = CRM_Utils_System::explode('-', $key, 3);

      if ($locTypeId == 'Primary') {
        if ($contactID) {
          $locTypeId = CRM_Contact_BAO_Contact::getPrimaryLocationType($contactID, FALSE, 'address');
          $primaryLocationType = $locTypeId;
        }
        else {
          $locTypeId = $defaultLocationId;
        }
      }

      if (is_numeric($locTypeId) &&
        !in_array($fieldName, $multiplFields) &&
        substr($fieldName, 0, 7) !== 'custom_'
      ) {
        $index = $locTypeId;

        if (is_numeric($typeId)) {
          $index .= '-' . $typeId;
        }
        if (!in_array($index, $locationType)) {
          $locationType[$count] = $index;
          $count++;
        }

        $loc = CRM_Utils_Array::key($index, $locationType);

        $blockName = strtolower($this->getFieldEntity($fieldName));

        $data[$blockName][$loc]['location_type_id'] = $locTypeId;

        //set is_billing true, for location type "Billing"
        if ($locTypeId == $billingLocationTypeId) {
          $data[$blockName][$loc]['is_billing'] = 1;
        }

        if ($contactID) {
          //get the primary location type
          if ($locTypeId == $primaryLocationType) {
            $data[$blockName][$loc]['is_primary'] = 1;
          }
        }
        elseif ($locTypeId == $defaultLocationId) {
          $data[$blockName][$loc]['is_primary'] = 1;
        }

        if ($fieldName === 'state_province') {
          // CRM-3393
          if (is_numeric($value) && ((int ) $value) >= 1000) {
            $data['address'][$loc]['state_province_id'] = $value;
          }
          elseif (empty($value)) {
            $data['address'][$loc]['state_province_id'] = '';
          }
          else {
            $data['address'][$loc]['state_province'] = $value;
          }
        }
        elseif ($fieldName === 'country_id') {
          $data['address'][$loc]['country_id'] = $value;
        }
        elseif ($fieldName === 'county') {
          $data['address'][$loc]['county_id'] = $value;
        }
        elseif ($fieldName == 'address_name') {
          $data['address'][$loc]['name'] = $value;
        }
        elseif (substr($fieldName, 0, 14) === 'address_custom') {
          $data['address'][$loc][substr($fieldName, 8)] = $value;
        }
        else {
          $data[$blockName][$loc][$fieldName] = $value;
        }
      }

      if ($key === 'location') {
        foreach ($value as $locationTypeId => $field) {
          foreach ($field as $block => $val) {
            if ($block === 'address' && array_key_exists('address_name', $val)) {
              $value[$locationTypeId][$block]['name'] = $value[$locationTypeId][$block]['address_name'];
            }
          }
        }
      }
      // Why only these fields...?
      if ($value === '' && in_array($key, ['nick_name', 'job_title', 'middle_name', 'birth_date', 'gender_id', 'current_employer', 'prefix_id', 'suffix_id'], TRUE)
        ) {
        // CRM-10128: if $value is blank, do not fill $data with empty value
        continue;
      }
      else {
        $data[$key] = $value;
      }

    }

    if (!isset($data['contact_type'])) {
      $data['contact_type'] = 'Individual';
    }

    //set the values for checkboxes (do_not_email, do_not_mail, do_not_trade, do_not_phone)
    $privacy = CRM_Core_SelectValues::privacy();
    foreach ($privacy as $key => $value) {
      if (array_key_exists($key, $fields)) {
        // do not reset values for existing contacts, if fields are added to a profile
        if (array_key_exists($key, $params)) {
          $data[$key] = $params[$key];
          if (empty($params[$key])) {
            $data[$key] = 0;
          }
        }
        elseif (!$contactID) {
          $data[$key] = 0;
        }
      }
    }

    return [$data, $contactDetails];
  }

  /**
   * Format params for update and fill mode.
   *
   * @param array $params
   *   reference to an array containing all the.
   *   values for import
   * @param int $cid
   *   contact id.
   */
  private function formatParams(&$params, $cid) {
    if ($this->isSkipDuplicates()) {
      return;
    }

    $contactParams = [
      'contact_id' => $cid,
      // core#4269 - Don't check relationships for values.
      'noRelationships' => TRUE,
    ];

    $defaults = [];
    $contactObj = CRM_Contact_BAO_Contact::retrieve($contactParams, $defaults);

    $modeFill = $this->isFillDuplicates();

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($params['contact_type'], NULL, $cid, 0, NULL);
    CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $defaults, FALSE, FALSE);

    $contact = get_object_vars($contactObj);

    foreach ($params as $key => $value) {
      if (in_array($key, ['id', 'contact_type'])) {
        continue;
      }
      // These values must be handled differently because we need to account for location type.
      $checkLocationType = in_array($key, ['address', 'phone', 'email']);

      if (!$checkLocationType) {
        if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key)) {
          $custom_params = ['id' => $contact['id'], 'return' => $key];
          $getValue = civicrm_api3('Contact', 'getvalue', $custom_params);
          if (empty($getValue)) {
            unset($getValue);
          }
        }
        else {
          $getValue = CRM_Utils_Array::retrieveValueRecursive($contact, $key);
        }

        if ($modeFill && isset($getValue)) {
          unset($params[$key]);
          if ($customFieldId) {
            // Extra values must be unset to ensure the values are not
            // imported.
            unset($params['custom'][$customFieldId]);
          }
        }
      }
      else {

        foreach ($value as $innerKey => $locationValues) {
          if ($modeFill) {
            $getValue = CRM_Utils_Array::retrieveValueRecursive($contact, $key);
            if (isset($getValue)) {
              foreach ($getValue as $cnt => $values) {
                if ((!empty($getValue[$cnt]['location_type_id']) && !empty($params[$key][$innerKey]['location_type_id'])) && $getValue[$cnt]['location_type_id'] == $params[$key][$innerKey]['location_type_id']) {
                  unset($params[$key][$innerKey]);
                }
              }
            }
          }
        }
        if (count($params[$key]) == 0) {
          unset($params[$key]);
        }
      }
    }
  }

  /**
   * Get the message for a successful import.
   *
   * @return string
   */
  private function getSuccessMessage(): string {
    if (!empty($this->_unparsedStreetAddressContacts)) {
      $errorMessage = ts('Record imported successfully but unable to parse the street address: ');
      foreach ($this->_unparsedStreetAddressContacts as $contactInfo => $contactValue) {
        $contactUrl = CRM_Utils_System::url('civicrm/contact/add', 'reset=1&action=update&cid=' . $contactValue['id'], TRUE, NULL, FALSE);
        $errorMessage .= "\n Contact ID:" . $contactValue['id'] . " <a href=\"$contactUrl\"> " . $contactValue['streetAddress'] . '</a>';
      }
      return $errorMessage;
    }
    return '';
  }

  /**
   * Get the possible contact matches.
   *
   * 1) the chosen dedupe rule falling back to
   * 2) a check for the external ID.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-17275
   *
   * @param array $params
   * @param int|null $extIDMatch
   * @param int|string $dedupeRuleID
   *
   * @return int|null
   *   IDs of a possible.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getPossibleContactMatch(array $params, ?int $extIDMatch, $dedupeRuleID): ?int {
    $possibleMatches = $this->getPossibleMatchesByDedupeRule($params, $dedupeRuleID, FALSE);
    if (!$extIDMatch) {
      if (count($possibleMatches) === 1) {
        return array_key_last($possibleMatches);
      }
      if (count($possibleMatches) > 1) {
        throw new CRM_Core_Exception(ts('Record duplicates multiple contacts: ') . implode(',', array_keys($possibleMatches)), CRM_Import_Parser::ERROR);
      }
      return NULL;
    }
    if (count($possibleMatches) > 0) {
      if (array_key_exists($extIDMatch, $possibleMatches)) {
        return $extIDMatch;
      }
      throw new CRM_Core_Exception(ts('Matching this contact based on the de-dupe rule would cause an external ID conflict'), CRM_Import_Parser::ERROR);
    }
    return $extIDMatch;
  }

  /**
   * Set field metadata.
   */
  protected function setFieldMetadata() {
    $this->setImportableFieldsMetadata($this->getContactImportMetadata());
  }

  /**
   * Format contact parameters.
   *
   * @todo this function needs re-writing & re-merging into the main function.
   *
   * Here be dragons.
   *
   * @param array $values
   * @param array $params
   *
   * @return bool
   */
  protected function formatContactParameters(&$values, &$params) {
    // Crawl through the possible classes:
    // Contact
    //      Individual
    //      Household
    //      Organization
    //          Location
    //              Address
    //              Email
    //              IM
    //      Note
    //      Custom

    // first add core contact values since for other Civi modules they are not added
    $contactFields = CRM_Contact_DAO_Contact::fields();
    _civicrm_api3_store_values($contactFields, $values, $params);

    if (isset($values['contact_type'])) {
      // we're an individual/household/org property

      $fields[$values['contact_type']] = CRM_Contact_DAO_Contact::fields();

      _civicrm_api3_store_values($fields[$values['contact_type']], $values, $params);
      return TRUE;
    }
    return TRUE;
  }

  /**
   * Get metadata for contact importable fields.
   *
   * @internal this function will be made private in the near future. It is
   * currently used by a core form but should not be called directly & once fixed
   * will be private.
   *
   * @return array
   */
  public function getContactImportMetadata(): array {
    $cacheKey = 'importable_contact_field_metadata' . $this->getContactType() . $this->getContactSubType();
    if (Civi::cache('fields')->has($cacheKey)) {
      return Civi::cache('fields')->get($cacheKey);
    }
    $contactFields = CRM_Contact_BAO_Contact::importableFields($this->getContactType());
    // exclude the address options disabled in the Address Settings
    $fields = CRM_Core_BAO_Address::validateAddressOptions($contactFields);

    //CRM-5125
    //supporting import for contact subtypes
    $csType = NULL;
    if ($this->getContactSubType()) {
      //custom fields for sub type
      $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($this->getContactSubType());

      if (!empty($subTypeFields)) {
        foreach ($subTypeFields as $customSubTypeField => $details) {
          $fields[$customSubTypeField] = $details;
        }
      }
    }

    foreach ($this->getRelationships() as $key => $var) {
      [$type] = explode('_', $key);
      $relationshipType[$key]['title'] = $var;
      $relationshipType[$key]['headerPattern'] = '/' . preg_quote($var, '/') . '/';
      $relationshipType[$key]['import'] = TRUE;
      $relationshipType[$key]['relationship_type_id'] = $type;
      $relationshipType[$key]['related'] = TRUE;
    }

    if (!empty($relationshipType)) {
      $fields = array_merge($fields, [
        'related' => [
          'title' => ts('- related contact info -'),
        ],
      ], $relationshipType);
    }
    Civi::cache('fields')->set($cacheKey, $fields);
    return $fields;
  }

  /**
   * Format location block ready for importing.
   *
   * Note this formatting should all be by the time the code reaches this point
   *
   * There is some test coverage for this in
   * CRM_Contact_Import_Parser_ContactTest e.g. testImportPrimaryAddress.
   *
   * @deprecated
   *
   * @param array $values
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function formatLocationBlock(&$values) {
    // @todo - remove this function.
    // Original explantion .....
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

    return TRUE;
  }

  /**
   * Get the field metadata for the relevant entity.
   *
   * @param string $entity
   *
   * @return array
   */
  protected function getMetadataForEntity($entity) {
    if (!isset($this->fieldMetadata[$entity])) {
      $className = "CRM_Core_DAO_$entity";
      $this->fieldMetadata[$entity] = $className::fields();
    }
    return $this->fieldMetadata[$entity];
  }

  /**
   * Fill in the primary location.
   *
   * If the contact has a primary address we update it. Otherwise
   * we add an address of the default location type.
   *
   * @param array $params
   *   Address block parameters
   * @param array $values
   *   Input values
   * @param string $entity
   *  - address, email, phone
   * @param int|null $contactID
   *
   * @throws \CRM_Core_Exception
   */
  protected function fillPrimary(&$params, $values, $entity, $contactID) {
    if ($values['location_type_id'] === 'Primary') {
      if ($contactID) {
        $primary = civicrm_api3($entity, 'get', [
          'return' => 'location_type_id',
          'contact_id' => $contactID,
          'is_primary' => 1,
          'sequential' => 1,
        ]);
      }
      $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
      $params['location_type_id'] = (int) (isset($primary) && $primary['count']) ? $primary['values'][0]['location_type_id'] : $defaultLocationType->id;
      $params['is_primary'] = 1;
    }
  }

  /**
   * Get the civicrm_mapping_field appropriate layout for the mapper input.
   *
   * The input looks something like ['street_address', 1]
   * and would be mapped to ['name' => 'street_address', 'location_type_id' =>
   * 1]
   *
   * @param array $fieldMapping
   *   Field as submitted on the MapField form - this is a non-associative array,
   *   the keys of which depend on the data/ field. Generally it will be one of
   *   [$fieldName],
   *   [$fieldName, $locationTypeID, $phoneTypeIDOrIMProviderIDIfRelevant],
   *   [$fieldName, $websiteTypeID],
   *   If the mapping is for a related contact it will be as above but the first
   *   key will be the relationship key - eg. 5_a_b.
   * @param int $mappingID
   * @param int $columnNumber
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getMappingFieldFromMapperInput(array $fieldMapping, int $mappingID, int $columnNumber): array {
    $isRelationshipField = preg_match('/\d*_a_b|b_a$/', $fieldMapping[0]);
    $fieldName = $isRelationshipField ? $fieldMapping[1] : $fieldMapping[0];
    $locationTypeID = NULL;
    $possibleLocationField = $isRelationshipField ? 2 : 1;
    $entity = strtolower($this->getFieldEntity($fieldName));
    if ($entity !== 'website' && is_numeric($fieldMapping[$possibleLocationField] ?? NULL)) {
      $locationTypeID = $fieldMapping[$possibleLocationField];
    }

    return [
      'name' => $fieldName,
      'mapping_id' => $mappingID,
      'relationship_type_id' => $isRelationshipField ? substr($fieldMapping[0], 0, -4) : NULL,
      'relationship_direction' => $isRelationshipField ? substr($fieldMapping[0], -3) : NULL,
      'column_number' => $columnNumber,
      'contact_type' => $this->getContactType(),
      'website_type_id' => $entity !== 'website' ? NULL : ($isRelationshipField ? $fieldMapping[2] : $fieldMapping[1]),
      'phone_type_id' => $entity !== 'phone' ? NULL : ($isRelationshipField ? $fieldMapping[3] : $fieldMapping[2]),
      'im_provider_id' => $entity !== 'im' ? NULL : ($isRelationshipField ? $fieldMapping[3] : $fieldMapping[2]),
      'location_type_id' => $locationTypeID,
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
    $this->setFieldMetadata();
    $title = [];
    if ($mappedField['relationship_type_id']) {
      $title[] = $this->getRelationshipLabel($mappedField['relationship_type_id'], $mappedField['relationship_direction']);
    }
    $title[] = $this->getFieldMetadata($mappedField['name'])['title'];
    if ($mappedField['location_type_id']) {
      $title[] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Address', 'location_type_id', $mappedField['location_type_id']);
    }
    if ($mappedField['website_type_id']) {
      $title[] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Website', 'website_type_id', $mappedField['website_type_id']);
    }
    if ($mappedField['phone_type_id']) {
      $title[] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Phone', 'phone_type_id', $mappedField['phone_type_id']);
    }
    if ($mappedField['im_provider_id']) {
      $title[] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_IM', 'provider_id', $mappedField['im_provider_id']);
    }
    return implode(' - ', $title);
  }

  /**
   * Get the relevant label for the relationship.
   *
   * @param int $id
   * @param string $direction
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getRelationshipLabel(int $id, string $direction): string {
    if (empty($this->relationshipLabels[$id . $direction])) {
      $this->relationshipLabels[$id . $direction] =
      $fieldName = 'label_' . $direction;
      $this->relationshipLabels[$id . $direction] = (string) RelationshipType::get(FALSE)
        ->addWhere('id', '=', $id)
        ->addSelect($fieldName)->execute()->first()[$fieldName];
    }
    return $this->relationshipLabels[$id . $direction];
  }

  /**
   * Transform the input parameters into the form handled by the input routine.
   *
   * @param array $values
   *   Input parameters as they come in from the datasource
   *   eg. ['Bob', 'Smith', 'bob@example.org', '123-456']
   *
   * @return array
   *   Parameters mapped to CiviCRM fields based on the mapping
   *   and specified contact type. eg.
   *   [
   *     'contact_type' => 'Individual',
   *     'first_name' => 'Bob',
   *     'last_name' => 'Smith',
   *     'phone' => ['phone' => '123', 'location_type_id' => 1, 'phone_type_id' => 1],
   *     '5_a_b' => ['contact_type' => 'Organization', 'url' => ['url' => 'https://example.org', 'website_type_id' => 1]]
   *     'im' => ['im' => 'my-handle', 'location_type_id' => 1, 'provider_id' => 1],
   *
   * @throws \CRM_Core_Exception
   */
  public function getMappedRow(array $values): array {
    $params = ['relationship' => []];

    foreach ($this->getFieldMappings() as $i => $mappedField) {
      // The key is in the format 5_a_b where 5 is the relationship_type_id and a_b is the direction.
      $relatedContactKey = $mappedField['relationship_type_id'] ? ($mappedField['relationship_type_id'] . '_' . $mappedField['relationship_direction']) : NULL;
      $fieldName = $mappedField['name'];
      $importedValue = $values[$i];
      if ($fieldName === 'do_not_import' || $importedValue === NULL) {
        continue;
      }

      $locationFields = ['location_type_id', 'phone_type_id', 'provider_id', 'website_type_id'];
      $locationValues = array_filter(array_intersect_key($mappedField, array_fill_keys($locationFields, 1)));

      if ($relatedContactKey) {
        if ($importedValue !== '') {
          if (!isset($params['relationship'][$relatedContactKey])) {
            $params['relationship'][$relatedContactKey] = [
              // These will be over-written by any the importer has chosen but defaults are based on the relationship.
              'contact_type' => $this->getRelatedContactType($mappedField['relationship_type_id'], $mappedField['relationship_direction']),
              'contact_sub_type' => $this->getRelatedContactSubType($mappedField['relationship_type_id'], $mappedField['relationship_direction']),
            ];
          }
          $this->addFieldToParams($params['relationship'][$relatedContactKey], $locationValues, $fieldName, $importedValue);
        }
      }
      else {
        $this->addFieldToParams($params, $locationValues, $fieldName, $importedValue);
      }
    }

    $this->fillStateProvince($params);

    $params['contact_type'] = $this->getContactType();
    if ($this->getContactSubType()) {
      $params['contact_sub_type'] = $this->getContactSubType();
    }
    return $params;
  }

  /**
   * Get the invalid values in the params for the given contact.
   *
   * @param array|int|string $value
   * @param string $prefixString
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  protected function getInvalidValuesForContact($value, string $prefixString): array {
    $errors = [];
    foreach ($value as $contactKey => $contactValue) {
      if ($contactKey !== 'relationship') {
        $result = $this->getInvalidValues($contactValue, $contactKey, $prefixString);
        if (!empty($result)) {
          $errors = array_merge($errors, $result);
        }
      }
    }
    return $errors;
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
    foreach ($this->getSubmittedValue('mapper') as $i => $mapperRow) {
      $mappedField = $this->getMappingFieldFromMapperInput($mapperRow, 0, $i);
      if (!$mappedField['location_type_id'] && !empty($this->importableFieldsMetadata[$mappedField['name']]['hasLocationType'])) {
        $mappedField['location_type_id'] = 'Primary';
      }
      // Just for clarity since 0 is a pseudo-value
      unset($mappedField['mapping_id']);
      // Annoyingly the civicrm_mapping_field name for this differs from civicrm_im.
      // Test cover in `CRM_Contact_Import_Parser_ContactTest::testMapFields`
      $mappedField['provider_id'] = $mappedField['im_provider_id'];
      unset($mappedField['im_provider_id']);
      $mappedFields[] = $mappedField;
    }
    return $mappedFields;
  }

  /**
   * Get the related contact type.
   *
   * @param int|null $relationshipTypeID
   * @param int|string $relationshipDirection
   *
   * @return null|string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRelatedContactType($relationshipTypeID, $relationshipDirection): ?string {
    if (!$relationshipTypeID) {
      return NULL;
    }
    $relationshipField = 'contact_type_' . substr($relationshipDirection, -1);
    return $this->getRelationshipType($relationshipTypeID)[$relationshipField];
  }

  /**
   * Get the related contact sub type.
   *
   * @param int|null $relationshipTypeID
   * @param int|string $relationshipDirection
   *
   * @return null|string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRelatedContactSubType(int $relationshipTypeID, $relationshipDirection): ?string {
    if (!$relationshipTypeID) {
      return NULL;
    }
    $relationshipField = 'contact_sub_type_' . substr($relationshipDirection, -1);
    return $this->getRelationshipType($relationshipTypeID)[$relationshipField];
  }

  /**
   * Get the related contact type.
   *
   * @param int|null $relationshipTypeID
   * @param int|string $relationshipDirection
   *
   * @return null|string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRelatedContactLabel($relationshipTypeID, $relationshipDirection): ?string {
    $relationshipField = 'label_' . $relationshipDirection;
    return $this->getRelationshipType($relationshipTypeID)[$relationshipField];
  }

  /**
   * Get the relationship type.
   *
   * @param int $relationshipTypeID
   *
   * @return string[]
   * @throws \CRM_Core_Exception
   */
  protected function getRelationshipType(int $relationshipTypeID): array {
    $cacheKey = 'relationship_type' . $relationshipTypeID;
    if (!isset(Civi::$statics[__CLASS__][$cacheKey])) {
      Civi::$statics[__CLASS__][$cacheKey] = RelationshipType::get(FALSE)
        ->addWhere('id', '=', $relationshipTypeID)
        ->addSelect('*')->execute()->first();
    }
    return Civi::$statics[__CLASS__][$cacheKey];
  }

  /**
   * Add the given field to the contact array.
   *
   * @param array $contactArray
   * @param array $locationValues
   * @param string $fieldName
   * @param mixed $importedValue
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  private function addFieldToParams(array &$contactArray, array $locationValues, string $fieldName, $importedValue): void {
    if (!empty($locationValues)) {
      $fieldMap = ['country' => 'country_id', 'state_province' => 'state_province_id', 'county' => 'county_id'];
      $realFieldName = empty($fieldMap[$fieldName]) ? $fieldName : $fieldMap[$fieldName];
      $entity = strtolower($this->getFieldEntity($fieldName));

      // The entity key is either location_type_id for address, email - eg. 1, or
      // location_type_id + '_' + phone_type_id or im_provider_id
      // or the value for website(since websites are not historically one-per-type)
      $entityKey = $locationValues['location_type_id'] ?? $importedValue;
      if (!empty($locationValues['phone_type_id']) || !empty($locationValues['provider_id'])) {
        $entityKey .= '_' . ($locationValues['phone_type_id'] ?? '' . $locationValues['provider_id'] ?? '');
      }
      $fieldValue = $this->getTransformedFieldValue($realFieldName, $importedValue);

      if (!isset($contactArray[$entity][$entityKey])) {
        $contactArray[$entity][$entityKey] = $locationValues;
      }
      // So im has really non-standard handling...
      $reallyRealFieldName = $realFieldName === 'im' ? 'name' : $realFieldName;
      $contactArray[$entity][$entityKey][$reallyRealFieldName] = $fieldValue;
    }
    else {
      $fieldName = array_search($fieldName, $this->getOddlyMappedMetadataFields(), TRUE) ?: $fieldName;
      $importedValue = $this->getTransformedFieldValue($fieldName, $importedValue);
      if ($importedValue === '' && !empty($contactArray[$fieldName])) {
        // If we have already calculated contact type or subtype based on the relationship
        // do not overwrite it with an empty value.
        return;
      }
      $contactArray[$fieldName] = $importedValue;
    }
  }

  /**
   * Get any related contacts designated for update.
   *
   * This extracts the parts that relate to separate related
   * contacts from the 'params' array.
   *
   * It is probably a bit silly not to nest them more clearly in
   * `getParams` in the first place & maybe in future we can do that.
   *
   * @param array $params
   *
   * @return array
   *   e.g ['5_a_b' => ['contact_type' => 'Organization', 'organization_name' => 'The Firm']]
   * @throws \CRM_Core_Exception
   */
  protected function getRelatedContactsParams(array $params): array {
    $relatedContacts = [];
    foreach ($params['relationship'] as $key => $value) {
      // If the key is a relationship key - eg. 5_a_b or 10_b_a
      // then the value is an array that describes an existing contact.
      // We need to check the fields are present to identify or create this
      // contact.
      if (preg_match('/^\d+_[a|b]_[a|b]$/', $key)) {
        $value['relationship_type_id'] = substr($key, 0, -4);
        $value['relationship_direction'] = substr($key, -3);
        $value['relationship_label'] = $this->getRelationshipLabel($value['relationship_type_id'], $value['relationship_direction']);
        $relatedContacts[$key] = $value;
      }
    }
    return $relatedContacts;
  }

  /**
   * Lookup the contact's contact ID.
   *
   * @param array $params
   * @param bool $isMainContact
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function lookupContactID(array $params, bool $isMainContact): ?int {
    $contactID = !empty($params['id']) ? (int) $params['id'] : NULL;
    $extIDMatch = $this->lookupExternalIdentifier($params['external_identifier'] ?? NULL, $params['contact_type'], $contactID);
    if ($extIDMatch && $isMainContact && ($this->isSkipDuplicates() || $this->isIgnoreDuplicates())) {
      throw new CRM_Core_Exception(ts('External ID already exists in Database.'), CRM_Import_Parser::DUPLICATE);
    }
    if ($contactID) {
      $this->validateContactID($contactID, $params['contact_type']);
      return $contactID;
    }
    // Time to see if we can find an existing contact ID to make this an update
    // not a create.
    if ($extIDMatch || !$this->isIgnoreDuplicates()) {
      if (isset($params['relationship'])) {
        unset($params['relationship']);
      }
      $ruleId = $this->getSubmittedValue('dedupe_rule_id') ?: NULL;
      // if this is not the main contact and the contact types are not the same
      $mainContactType = $this->getSubmittedValue('contactType');
      if (!$isMainContact && $params['contact_type'] !== $mainContactType) {
        // use the unsupervised dedupe rule for this contact type
        $ruleId = DedupeRuleGroup::get(FALSE)
          ->addSelect('id')
          ->addWhere('contact_type', '=', $params['contact_type'])
          ->addWhere('used', '=', 'Unsupervised')
          ->execute()
          ->first()['id'];
      }
      $id = $this->getPossibleContactMatch($params, $extIDMatch, $ruleId);
      if ($id && $isMainContact && $this->isSkipDuplicates()) {
        throw new CRM_Core_Exception(ts('Contact matched by dedupe rule already exists in the database.'), CRM_Import_Parser::DUPLICATE);
      }
      return $id;
    }
    return NULL;
  }

  /**
   * @param array $params
   * @param bool $isMainContact
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  protected function processContact(array $params, bool $isMainContact): ?int {
    $contactID = $this->lookupContactID($params, $isMainContact);
    if ($contactID && !empty($params['contact_sub_type'])) {
      $contactSubType = Contact::get(FALSE)
        ->addWhere('id', '=', $contactID)
        ->addSelect('contact_sub_type')
        ->execute()
        ->first()['contact_sub_type'];
      if (!empty($contactSubType) && $contactSubType[0] !== $params['contact_sub_type'] && !CRM_Contact_BAO_ContactType::isAllowEdit($contactID, $contactSubType[0])) {
        throw new CRM_Core_Exception('Mismatched contact SubTypes :', CRM_Import_Parser::NO_MATCH);
      }
    }
    return $contactID;
  }

  /**
   * Try to get the correct state province using what country information we have.
   *
   * If the state matches more than one possibility then either the imported
   * country of the site country should help us....
   *
   * @param string $stateProvince
   * @param int|null|string $countryID
   *
   * @return int|string
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function tryToResolveStateProvince(string $stateProvince, $countryID) {
    if ($stateProvince === 'invalid_import_value') {
      return $stateProvince;
    }
    // Try to disambiguate since we likely have the country now.
    $possibleStates = $this->ambiguousOptions['state_province_id'][mb_strtolower($stateProvince)];
    if ($countryID) {
      return $this->checkStatesForCountry($countryID, $possibleStates) ?: 'invalid_import_value';
    }
    // Try the default country next.
    $defaultCountryMatch = $this->checkStatesForCountry($this->getSiteDefaultCountry(), $possibleStates);
    if ($defaultCountryMatch) {
      return $defaultCountryMatch;
    }

    if ($this->getAvailableCountries()) {
      $countryMatches = [];
      foreach ($this->getAvailableCountries() as $availableCountryID) {
        $possible = $this->checkStatesForCountry($availableCountryID, $possibleStates);
        if ($possible) {
          $countryMatches[] = $possible;
        }
      }
      if (count($countryMatches) === 1) {
        return reset($countryMatches);
      }

    }
    return $stateProvince;
  }

  /**
   * @param array $params
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function fillStateProvince(array &$params): array {
    foreach ($params as $key => $value) {
      if ($key === 'address') {
        foreach ($value as $index => $address) {
          $stateProvinceID = $address['state_province_id'] ?? NULL;
          $countyID = $address['county_id'] ?? NULL;
          $countryID = $address['country_id'] ?? NULL;
          if ($stateProvinceID) {
            if (!is_numeric($stateProvinceID)) {
              $params['address'][$index]['state_province_id'] = $stateProvinceID = $this->tryToResolveStateProvince($stateProvinceID, $countryID);
            }
            elseif ($countryID && is_numeric($countryID)) {
              if (!$this->checkStatesForCountry((int) $address['country_id'], [$stateProvinceID])) {
                $params['address'][$index]['state_province_id'] = 'invalid_import_value';
              }
            }
          }
          if ($countyID && !is_numeric($countyID)) {
            $params['address'][$index]['county_id'] = $this->tryToResolveCounty($countyID, $stateProvinceID, $countryID);
          }
        }
      }
      elseif (is_array($value) && !in_array($key, ['email', 'phone', 'im', 'website', 'openid'], TRUE)) {
        $this->fillStateProvince($params[$key]);
      }
    }
    return $params;
  }

  /**
   * Check is any of the given states correlate to the country.
   *
   * @param int $countryID
   * @param array $possibleStates
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  private function checkStatesForCountry(int $countryID, array $possibleStates) {
    foreach ($possibleStates as $index => $state) {
      if (!empty($this->statesByCountry[$state])) {
        if ($this->statesByCountry[$state] === $countryID) {
          return $state;
        }
        unset($possibleStates[$index]);
      }
    }
    if (!empty($possibleStates)) {
      $states = StateProvince::get(FALSE)
        ->addSelect('country_id')
        ->addWhere('id', 'IN', $possibleStates)
        ->execute()
        ->indexBy('country_id');
      foreach ($states as $state) {
        $this->statesByCountry[$state['id']] = $state['country_id'];
      }
      foreach ($possibleStates as $state) {
        if ($this->statesByCountry[$state] === $countryID) {
          return $state;
        }
      }
    }
    return FALSE;
  }

  /**
   * @param int|null|string $outcome
   *
   * @return string
   */
  protected function getStatus($outcome): string {
    if ($outcome === CRM_Import_Parser::VALID) {
      return empty($this->_unparsedStreetAddressContacts) ? 'IMPORTED' : 'warning_unparsed_address';
    }
    return [
      CRM_Import_Parser::DUPLICATE => 'DUPLICATE',
      CRM_Import_Parser::ERROR => 'ERROR',
      CRM_Import_Parser::NO_MATCH => 'invalid_no_match',
    ][$outcome] ?? 'ERROR';
  }

  /**
   * Return an error if the csv has more than one row with the same external identifier.
   *
   * @param string $externalIdentifier
   *
   * @throws \CRM_Core_Exception
   */
  protected function checkForDuplicateExternalIdentifiers(string $externalIdentifier): void {
    if ($externalIdentifier) {
      $existingRow = array_search($externalIdentifier, $this->externalIdentifiers, TRUE);
      if ($existingRow !== FALSE) {
        throw new CRM_Core_Exception(ts('External ID conflicts with record %1', [1 => $existingRow + 1]));
      }
      $this->externalIdentifiers[] = $externalIdentifier;
    }
  }

  /**
   * @param string $countyID
   * @param string|int|null $stateProvinceID
   * @param string|int|null $countryID
   *
   * @return string|int
   * @throws \CRM_Core_Exception
   */
  private function tryToResolveCounty(string $countyID, $stateProvinceID, $countryID) {
    $cacheString = $countryID . '_' . $stateProvinceID . '_' . $countyID;
    if (!isset(\Civi::$statics[$cacheString])) {
      $possibleCounties = $this->ambiguousOptions['county_id'][mb_strtolower($countyID)] ?? NULL;
      if (!$possibleCounties || $countyID === 'invalid_import_value') {
        \Civi::$statics[$cacheString] = $countyID;
      }
      else {
        if ($stateProvinceID === NULL && $countryID === NULL) {
          $countryID = \Civi::settings()->get('defaultContactCountry');
        }
        $countyLookUp = County::get(FALSE)
          ->addWhere('id', 'IN', $possibleCounties);
        if ($countryID && is_numeric($countryID)) {
          $countyLookUp->addWhere('state_province_id.country_id', '=', $countryID);
        }
        if ($stateProvinceID && is_numeric($stateProvinceID)) {
          $countyLookUp->addWhere('state_province_id', '=', $stateProvinceID);
        }
        $county = $countyLookUp->execute();
        if (count($county) === 1) {
          \Civi::$statics[$cacheString] = $county->first()['id'];
        }
        else {
          \Civi::$statics[$cacheString] = 'invalid_import_value';
        }
      }
    }
    return \Civi::$statics[$cacheString];
  }

}
