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
use Civi\Api4\RelationshipType;

require_once 'CRM/Utils/DeprecatedUtils.php';
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

  use CRM_Contact_Import_MetadataTrait;

  protected $_mapperKeys = [];
  protected $_mapperLocType = [];
  protected $_mapperPhoneType;
  protected $_mapperImProvider;
  protected $_mapperWebsiteType;
  protected $_mapperRelated;
  protected $_mapperRelatedContactType;
  protected $_mapperRelatedContactDetails;
  protected $_mapperRelatedContactEmailType;
  protected $_mapperRelatedContactImProvider;
  protected $_mapperRelatedContactWebsiteType;
  protected $_relationships;

  protected $_emailIndex;
  protected $_firstNameIndex;
  protected $_lastNameIndex;

  protected $_householdNameIndex;
  protected $_organizationNameIndex;

  protected $_phoneIndex;

  /**
   * Is update only permitted on an id match.
   *
   * Note this historically was true for when id or external identifier was
   * present. However, CRM-17275 determined that a dedupe-match could over-ride
   * external identifier.
   *
   * @var bool
   */
  protected $_updateWithId;
  protected $_retCode;

  protected $_externalIdentifierIndex;
  protected $_allExternalIdentifiers;
  protected $_parseStreetAddress;

  /**
   * Array of successfully imported contact id's
   *
   * @var array
   */
  protected $_newContacts;

  /**
   * Line count id.
   *
   * @var int
   */
  protected $_lineCount;

  /**
   * Array of successfully imported related contact id's
   *
   * @var array
   */
  protected $_newRelatedContacts;

  /**
   * Array of all the contacts whose street addresses are not parsed.
   * of this import process
   * @var array
   */
  protected $_unparsedStreetAddressContacts;

  protected $_tableName;

  /**
   * Total number of lines in file
   *
   * @var int
   */
  protected $_rowCount;

  /**
   * Running total number of un-matched Contacts.
   *
   * @var int
   */
  protected $_unMatchCount;

  /**
   * Array of unmatched lines.
   *
   * @var array
   */
  protected $_unMatch;

  /**
   * Total number of contacts with unparsed addresses
   * @var int
   */
  protected $_unparsedAddressCount;

  /**
   * Filename of mismatch data
   *
   * @var string
   */
  protected $_misMatchFilemName;

  protected $_primaryKeyName;
  protected $_statusFieldName;

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
   * On duplicate
   *
   * @var int
   */
  public $_onDuplicate;

  /**
   * Dedupe rule group id to use if set
   *
   * @var int
   */
  public $_dedupeRuleGroupID = NULL;

  /**
   * Class constructor.
   *
   * @param array $mapperKeys
   * @param array $mapperLocType
   * @param array $mapperPhoneType
   * @param array $mapperImProvider
   * @param array $mapperRelated
   * @param array $mapperRelatedContactType
   * @param array $mapperRelatedContactDetails
   * @param array $mapperRelatedContactLocType
   * @param array $mapperRelatedContactPhoneType
   * @param array $mapperRelatedContactImProvider
   * @param array $mapperWebsiteType
   * @param array $mapperRelatedContactWebsiteType
   */
  public function __construct(
    $mapperKeys = [], $mapperLocType = [], $mapperPhoneType = [], $mapperImProvider = [], $mapperRelated = [], $mapperRelatedContactType = [], $mapperRelatedContactDetails = [], $mapperRelatedContactLocType = [], $mapperRelatedContactPhoneType = [], $mapperRelatedContactImProvider = [],
    $mapperWebsiteType = [], $mapperRelatedContactWebsiteType = []
  ) {
    parent::__construct();
    $this->_mapperKeys = $mapperKeys;
    $this->_mapperLocType = &$mapperLocType;
    $this->_mapperPhoneType = &$mapperPhoneType;
    $this->_mapperWebsiteType = $mapperWebsiteType;
    // get IM service provider type id for contact
    $this->_mapperImProvider = &$mapperImProvider;
    $this->_mapperRelated = &$mapperRelated;
    $this->_mapperRelatedContactType = &$mapperRelatedContactType;
    $this->_mapperRelatedContactDetails = &$mapperRelatedContactDetails;
    $this->_mapperRelatedContactLocType = &$mapperRelatedContactLocType;
    $this->_mapperRelatedContactPhoneType = &$mapperRelatedContactPhoneType;
    $this->_mapperRelatedContactWebsiteType = $mapperRelatedContactWebsiteType;
    // get IM service provider type id for related contact
    $this->_mapperRelatedContactImProvider = &$mapperRelatedContactImProvider;
  }

  /**
   * The initializer code, called before processing.
   */
  public function init() {
    $this->setFieldMetadata();
    foreach ($this->getImportableFieldsMetadata() as $name => $field) {
      $this->addField($name, $field['title'], CRM_Utils_Array::value('type', $field), CRM_Utils_Array::value('headerPattern', $field), CRM_Utils_Array::value('dataPattern', $field), CRM_Utils_Array::value('hasLocationType', $field));
    }
    $this->_newContacts = [];

    $this->setActiveFields($this->_mapperKeys);
    $this->setActiveFieldLocationTypes($this->_mapperLocType);
    $this->setActiveFieldPhoneTypes($this->_mapperPhoneType);
    $this->setActiveFieldWebsiteTypes($this->_mapperWebsiteType);
    //set active fields of IM provider of contact
    $this->setActiveFieldImProviders($this->_mapperImProvider);

    //related info
    $this->setActiveFieldRelated($this->_mapperRelated);
    $this->setActiveFieldRelatedContactType($this->_mapperRelatedContactType);
    $this->setActiveFieldRelatedContactDetails($this->_mapperRelatedContactDetails);
    $this->setActiveFieldRelatedContactLocType($this->_mapperRelatedContactLocType);
    $this->setActiveFieldRelatedContactPhoneType($this->_mapperRelatedContactPhoneType);
    $this->setActiveFieldRelatedContactWebsiteType($this->_mapperRelatedContactWebsiteType);
    //set active fields of IM provider of related contact
    $this->setActiveFieldRelatedContactImProvider($this->_mapperRelatedContactImProvider);

    $this->_phoneIndex = -1;
    $this->_emailIndex = -1;
    $this->_firstNameIndex = -1;
    $this->_lastNameIndex = -1;
    $this->_householdNameIndex = -1;
    $this->_organizationNameIndex = -1;
    $this->_externalIdentifierIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      if (substr($key, 0, 5) == 'email' && substr($key, 0, 14) != 'email_greeting') {
        $this->_emailIndex = $index;
      }
      if (substr($key, 0, 5) == 'phone') {
        $this->_phoneIndex = $index;
      }
      if ($key == 'first_name') {
        $this->_firstNameIndex = $index;
      }
      if ($key == 'last_name') {
        $this->_lastNameIndex = $index;
      }
      if ($key == 'household_name') {
        $this->_householdNameIndex = $index;
      }
      if ($key == 'organization_name') {
        $this->_organizationNameIndex = $index;
      }

      if ($key == 'external_identifier') {
        $this->_externalIdentifierIndex = $index;
        $this->_allExternalIdentifiers = [];
      }
      $index++;
    }

    $this->_updateWithId = FALSE;
    if (in_array('id', $this->_mapperKeys) || ($this->_externalIdentifierIndex >= 0 && in_array($this->_onDuplicate, [
      CRM_Import_Parser::DUPLICATE_UPDATE,
      CRM_Import_Parser::DUPLICATE_FILL,
    ]))) {
      $this->_updateWithId = TRUE;
    }

    $this->_parseStreetAddress = CRM_Utils_Array::value('street_address_parsing', CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'address_options'), FALSE);
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
      $return[$name] = $field['title'];
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
  private function isSkipDuplicates(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_SKIP;
  }

  /**
   * Handle the values in preview mode.
   *
   * Function will be deprecated in favour of validateValues.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   *   CRM_Import_Parser::ERROR or CRM_Import_Parser::VALID
   */
  public function preview(&$values) {
    return $this->summary($values);
  }

  /**
   * Handle the values in summary mode.
   *
   * Function will be deprecated in favour of validateValues.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return int
   *   the result of this processing
   *   CRM_Import_Parser::ERROR or CRM_Import_Parser::VALID
   */
  public function summary(&$values): int {
    $rowNumber = (int) ($values[count($values) - 1]);
    try {
      $this->validateValues($values);
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      array_unshift($values, $e->getMessage());
      return CRM_Import_Parser::ERROR;
    }
    $this->setImportStatus($rowNumber, 'NEW', '');

    return CRM_Import_Parser::VALID;
  }

  /**
   * Get Array of all the fields that could potentially be part
   * import process
   *
   * @return array
   */
  public function getAllFields() {
    return $this->_fields;
  }

  /**
   * Handle the values in import mode.
   *
   * @param int $onDuplicate
   *   The code for what action to take on duplicates.
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  public function import($onDuplicate, &$values) {
    $this->_unparsedStreetAddressContacts = [];
    if (!$this->getSubmittedValue('doGeocodeAddress')) {
      // CRM-5854, reset the geocode method to null to prevent geocoding
      CRM_Utils_GeocodeProvider::disableForSession();
    }

    // first make sure this is a valid line
    //$this->_updateWithId = false;
    $response = $this->summary($values);
    $statusFieldName = $this->_statusFieldName;

    if ($response != CRM_Import_Parser::VALID) {
      $this->setImportStatus((int) $values[count($values) - 1], 'Invalid', "Invalid (Error Code: $response)");
      return $response;
    }

    $params = &$this->getActiveFieldParams();
    $formatted = [
      'contact_type' => $this->_contactType,
    ];

    $contactFields = CRM_Contact_DAO_Contact::import();

    //check if external identifier exists in database
    if (!empty($params['external_identifier']) && (!empty($params['id']) || in_array($onDuplicate, [
      CRM_Import_Parser::DUPLICATE_SKIP,
      CRM_Import_Parser::DUPLICATE_NOCHECK,
    ]))) {

      $extIDResult = civicrm_api3('Contact', 'get', [
        'external_identifier' => $params['external_identifier'],
        'showAll' => 'all',
        'return' => ['id', 'contact_is_deleted'],
      ]);
      if (isset($extIDResult['id'])) {
        // record with matching external identifier does exist.
        $internalCid = $extIDResult['id'];
        if ($internalCid != CRM_Utils_Array::value('id', $params)) {
          if ($extIDResult['values'][$internalCid]['contact_is_deleted'] == 1) {
            // And it is deleted. What to do? If we skip it, they user
            // will be under the impression that the record exists in
            // the database, yet they won't be able to find it. If we
            // don't skip it, the database will try to insert a new record
            // with an external_identifier that is non-unique. So...
            // we will update this contact to remove the external_identifier
            // and let a new record be created.
            $update_params = ['id' => $internalCid, 'external_identifier' => ''];
            civicrm_api3('Contact', 'create', $update_params);
          }
          else {
            $errorMessage = ts('External ID already exists in Database.');
            array_unshift($values, $errorMessage);
            $this->setImportStatus((int) $values[count($values) - 1], 'ERROR', $errorMessage);
            return CRM_Import_Parser::DUPLICATE;
          }
        }
      }
    }

    if (!empty($this->_contactSubType)) {
      $params['contact_sub_type'] = $this->_contactSubType;
    }

    if ($subType = CRM_Utils_Array::value('contact_sub_type', $params)) {
      if (CRM_Contact_BAO_ContactType::isExtendsContactType($subType, $this->_contactType, FALSE, 'label')) {
        $subTypes = CRM_Contact_BAO_ContactType::subTypePairs($this->_contactType, FALSE, NULL);
        $params['contact_sub_type'] = array_search($subType, $subTypes);
      }
      elseif (!CRM_Contact_BAO_ContactType::isExtendsContactType($subType, $this->_contactType)) {
        $message = "Mismatched or Invalid Contact Subtype.";
        array_unshift($values, $message);
        return CRM_Import_Parser::NO_MATCH;
      }
    }

    // Get contact id to format common data in update/fill mode,
    // prioritising a dedupe rule check over an external_identifier check, but falling back on ext id.
    if ($this->_updateWithId && empty($params['id'])) {
      try {
        $possibleMatches = $this->getPossibleContactMatches($params);
      }
      catch (CRM_Core_Exception $e) {
        $errorMessage = $e->getMessage();
        array_unshift($values, $errorMessage);

        $importRecordParams = [
          $statusFieldName => 'ERROR',
          "${statusFieldName}Msg" => $errorMessage,
        ];
        $this->setImportStatus((int) $values[count($values) - 1], 'ERROR', $errorMessage);
        return CRM_Import_Parser::ERROR;
      }
      foreach ($possibleMatches as $possibleID) {
        $params['id'] = $formatted['id'] = $possibleID;
      }
    }
    //format common data, CRM-4062
    $this->formatCommonData($params, $formatted, $contactFields);

    $relationship = FALSE;
    $createNewContact = TRUE;
    // Support Match and Update Via Contact ID
    if ($this->_updateWithId && isset($params['id'])) {
      $createNewContact = FALSE;
      // @todo - it feels like all the rows from here to the end of the IF
      // could be removed in favour of a simple check for whether the contact_type & id match
      $matchedIDs = $this->getIdsOfMatchingContacts($formatted);
      if (!empty($matchedIDs)) {
        if (count($matchedIDs) >= 1) {
          $updateflag = TRUE;
          foreach ($matchedIDs as $contactId) {
            if ($params['id'] == $contactId) {
              $contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params['id'], 'contact_type');
              if ($formatted['contact_type'] == $contactType) {
                //validation of subtype for update mode
                //CRM-5125
                $contactSubType = NULL;
                if (!empty($params['contact_sub_type'])) {
                  $contactSubType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params['id'], 'contact_sub_type');
                }

                if (!empty($contactSubType) && (!CRM_Contact_BAO_ContactType::isAllowEdit($params['id'], $contactSubType) && $contactSubType != CRM_Utils_Array::value('contact_sub_type', $formatted))) {

                  $message = "Mismatched contact SubTypes :";
                  array_unshift($values, $message);
                  $updateflag = FALSE;
                  $this->_retCode = CRM_Import_Parser::NO_MATCH;
                }
                else {
                  $updateflag = FALSE;
                  $this->_retCode = CRM_Import_Parser::VALID;
                }
              }
              else {
                $message = "Mismatched contact Types :";
                array_unshift($values, $message);
                $updateflag = FALSE;
                $this->_retCode = CRM_Import_Parser::NO_MATCH;
              }
            }
          }
          if ($updateflag) {
            $message = "Mismatched contact IDs OR Mismatched contact Types :";
            array_unshift($values, $message);
            $this->_retCode = CRM_Import_Parser::NO_MATCH;
          }
        }
      }
      else {
        $contactType = NULL;
        if (!empty($params['id'])) {
          $contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params['id'], 'contact_type');
          if ($contactType) {
            if ($formatted['contact_type'] == $contactType) {
              //validation of subtype for update mode
              //CRM-5125
              $contactSubType = NULL;
              if (!empty($params['contact_sub_type'])) {
                $contactSubType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params['id'], 'contact_sub_type');
              }

              if (!empty($contactSubType) && (!CRM_Contact_BAO_ContactType::isAllowEdit($params['id'], $contactSubType) && $contactSubType != CRM_Utils_Array::value('contact_sub_type', $formatted))) {

                $message = "Mismatched contact SubTypes :";
                array_unshift($values, $message);
                $this->_retCode = CRM_Import_Parser::NO_MATCH;
              }
              else {
                $newContact = $this->createContact($formatted, $contactFields, $onDuplicate, $params['id'], FALSE, $this->_dedupeRuleGroupID);
                $this->_retCode = CRM_Import_Parser::VALID;
              }
            }
            else {
              $message = "Mismatched contact Types :";
              array_unshift($values, $message);
              $this->_retCode = CRM_Import_Parser::NO_MATCH;
            }
          }
          else {
            // we should avoid multiple errors for single record
            // since we have already retCode and we trying to force again.
            if ($this->_retCode != CRM_Import_Parser::NO_MATCH) {
              $message = "No contact found for this contact ID:" . $params['id'];
              array_unshift($values, $message);
              $this->_retCode = CRM_Import_Parser::NO_MATCH;
            }
          }
        }
        else {
          //CRM-4148
          //now we want to create new contact on update/fill also.
          $createNewContact = TRUE;
        }
      }

      if (isset($newContact) && is_a($newContact, 'CRM_Contact_BAO_Contact')) {
        $relationship = TRUE;
      }
    }

    //fixed CRM-4148
    //now we create new contact in update/fill mode also.
    $contactID = NULL;
    if ($createNewContact || ($this->_retCode != CRM_Import_Parser::NO_MATCH && $this->_updateWithId)) {
      // @todo - there are multiple places where formatting is done that need consolidation.
      // This handles where the label has been passed in and it has gotten this far.
      // probably a bunch of hard-coded stuff could be removed to rely on this.
      $fields = Contact::getFields(FALSE)
        ->addWhere('options', '=', TRUE)
        ->setLoadOptions(TRUE)
        ->execute()->indexBy('name');
      foreach ($fields as $fieldName => $fieldSpec) {
        if (isset($formatted[$fieldName]) && is_array($formatted[$fieldName])) {
          // If we have an array at this stage, it's probably a multi-select
          // field that has already been parsed properly into the value that
          // should be inserted into the database.
          continue;
        }
        if (!empty($formatted[$fieldName])
          && empty($fieldSpec['options'][$formatted[$fieldName]])) {
          $formatted[$fieldName] = array_search($formatted[$fieldName], $fieldSpec['options'], TRUE) ?? $formatted[$fieldName];
        }
      }
      //CRM-4430, don't carry if not submitted.
      if ($this->_updateWithId && !empty($params['id'])) {
        $contactID = $params['id'];
      }
      $newContact = $this->createContact($formatted, $contactFields, $onDuplicate, $contactID, TRUE, $this->_dedupeRuleGroupID);
    }

    if (isset($newContact) && is_object($newContact) && ($newContact instanceof CRM_Contact_BAO_Contact)) {
      $relationship = TRUE;
      $newContact = clone($newContact);
      $contactID = $newContact->id;
      $this->_newContacts[] = $contactID;

      //get return code if we create new contact in update mode, CRM-4148
      if ($this->_updateWithId) {
        $this->_retCode = CRM_Import_Parser::VALID;
      }
    }
    elseif (isset($newContact) && CRM_Core_Error::isAPIError($newContact, CRM_Core_Error::DUPLICATE_CONTACT)) {
      // if duplicate, no need of further processing
      if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
        $errorMessage = "Skipping duplicate record";
        array_unshift($values, $errorMessage);
        $this->setImportStatus((int) $values[count($values) - 1], 'DUPLICATE', $errorMessage);
        return CRM_Import_Parser::DUPLICATE;
      }

      $relationship = TRUE;
      // CRM-10433/CRM-20739 - IDs could be string or array; handle accordingly
      if (!is_array($dupeContactIDs = $newContact['error_message']['params'][0])) {
        $dupeContactIDs = explode(',', $dupeContactIDs);
      }
      $dupeCount = count($dupeContactIDs);
      $contactID = array_pop($dupeContactIDs);
      // check to see if we had more than one duplicate contact id.
      // if we have more than one, the record will be rejected below
      if ($dupeCount == 1) {
        // there was only one dupe, we will continue normally...
        if (!in_array($contactID, $this->_newContacts)) {
          $this->_newContacts[] = $contactID;
        }
      }
    }

    if ($contactID) {
      // call import hook
      $currentImportID = end($values);

      $hookParams = [
        'contactID' => $contactID,
        'importID' => $currentImportID,
        'importTempTable' => $this->_tableName,
        'fieldHeaders' => $this->_mapperKeys,
        'fields' => $this->_activeFields,
      ];

      CRM_Utils_Hook::import('Contact', 'process', $this, $hookParams);
    }

    if ($relationship) {
      $primaryContactId = NULL;
      if (CRM_Core_Error::isAPIError($newContact, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
        if ($dupeCount == 1 && CRM_Utils_Rule::integer($contactID)) {
          $primaryContactId = $contactID;
        }
      }
      else {
        $primaryContactId = $newContact->id;
      }

      if ((CRM_Core_Error::isAPIError($newContact, CRM_Core_ERROR::DUPLICATE_CONTACT) || is_a($newContact, 'CRM_Contact_BAO_Contact')) && $primaryContactId) {

        //relationship contact insert
        foreach ($params as $key => $field) {
          [$id, $first, $second] = CRM_Utils_System::explode('_', $key, 3);
          if (!($first == 'a' && $second == 'b') && !($first == 'b' && $second == 'a')) {
            continue;
          }

          $relationType = new CRM_Contact_DAO_RelationshipType();
          $relationType->id = $id;
          $relationType->find(TRUE);
          $direction = "contact_sub_type_$second";

          $formatting = [
            'contact_type' => $params[$key]['contact_type'],
          ];

          //set subtype for related contact CRM-5125
          if (isset($relationType->$direction)) {
            //validation of related contact subtype for update mode
            if ($relCsType = CRM_Utils_Array::value('contact_sub_type', $params[$key]) && $relCsType != $relationType->$direction) {
              $errorMessage = ts("Mismatched or Invalid contact subtype found for this related contact.");
              array_unshift($values, $errorMessage);
              return CRM_Import_Parser::NO_MATCH;
            }
            else {
              $formatting['contact_sub_type'] = $relationType->$direction;
            }
          }

          $contactFields = NULL;
          $contactFields = CRM_Contact_DAO_Contact::import();

          //Relation on the basis of External Identifier.
          if (empty($params[$key]['id']) && !empty($params[$key]['external_identifier'])) {
            $params[$key]['id'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params[$key]['external_identifier'], 'id', 'external_identifier');
          }
          // check for valid related contact id in update/fill mode, CRM-4424
          if (in_array($onDuplicate, [
            CRM_Import_Parser::DUPLICATE_UPDATE,
            CRM_Import_Parser::DUPLICATE_FILL,
          ]) && !empty($params[$key]['id'])) {
            $relatedContactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params[$key]['id'], 'contact_type');
            if (!$relatedContactType) {
              $errorMessage = ts("No contact found for this related contact ID: %1", [1 => $params[$key]['id']]);
              array_unshift($values, $errorMessage);
              return CRM_Import_Parser::NO_MATCH;
            }

            //validation of related contact subtype for update mode
            //CRM-5125
            $relatedCsType = NULL;
            if (!empty($formatting['contact_sub_type'])) {
              $relatedCsType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params[$key]['id'], 'contact_sub_type');
            }

            if (!empty($relatedCsType) && (!CRM_Contact_BAO_ContactType::isAllowEdit($params[$key]['id'], $relatedCsType) &&
                $relatedCsType != CRM_Utils_Array::value('contact_sub_type', $formatting))
            ) {
              $errorMessage = ts("Mismatched or Invalid contact subtype found for this related contact.") . ' ' . ts("ID: %1", [1 => $params[$key]['id']]);
              array_unshift($values, $errorMessage);
              return CRM_Import_Parser::NO_MATCH;
            }
            // get related contact id to format data in update/fill mode,
            //if external identifier is present, CRM-4423
            $formatting['id'] = $params[$key]['id'];
          }

          //format common data, CRM-4062
          $this->formatCommonData($field, $formatting, $contactFields);

          //do we have enough fields to create related contact.
          $allowToCreate = $this->checkRelatedContactFields($key, $formatting);

          if (!$allowToCreate) {
            $errorMessage = ts('Related contact required fields are missing.');
            array_unshift($values, $errorMessage);
            return CRM_Import_Parser::NO_MATCH;
          }

          //fixed for CRM-4148
          if (!empty($params[$key]['id'])) {
            $contact = [
              'contact_id' => $params[$key]['id'],
            ];
            $defaults = [];
            $relatedNewContact = CRM_Contact_BAO_Contact::retrieve($contact, $defaults);
          }
          else {
            $relatedNewContact = $this->createContact($formatting, $contactFields, $onDuplicate, NULL, FALSE);
          }

          if (is_object($relatedNewContact) || ($relatedNewContact instanceof CRM_Contact_BAO_Contact)) {
            $relatedNewContact = clone($relatedNewContact);
          }

          $matchedIDs = [];
          // To update/fill contact, get the matching contact Ids if duplicate contact found
          // otherwise get contact Id from object of related contact
          if (is_array($relatedNewContact) && civicrm_error($relatedNewContact)) {
            if (CRM_Core_Error::isAPIError($relatedNewContact, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
              $matchedIDs = $relatedNewContact['error_message']['params'][0];
              if (!is_array($matchedIDs)) {
                $matchedIDs = explode(',', $matchedIDs);
              }
            }
            else {
              $errorMessage = $relatedNewContact['error_message'];
              array_unshift($values, $errorMessage);
              $this->setImportStatus((int) $values[count($values) - 1], 'ERROR', $errorMessage);
              return CRM_Import_Parser::ERROR;
            }
          }
          else {
            $matchedIDs[] = $relatedNewContact->id;
          }
          // update/fill related contact after getting matching Contact Ids, CRM-4424
          if (in_array($onDuplicate, [
            CRM_Import_Parser::DUPLICATE_UPDATE,
            CRM_Import_Parser::DUPLICATE_FILL,
          ])) {
            //validation of related contact subtype for update mode
            //CRM-5125
            $relatedCsType = NULL;
            if (!empty($formatting['contact_sub_type'])) {
              $relatedCsType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $matchedIDs[0], 'contact_sub_type');
            }

            if (!empty($relatedCsType) && (!CRM_Contact_BAO_ContactType::isAllowEdit($matchedIDs[0], $relatedCsType) && $relatedCsType != CRM_Utils_Array::value('contact_sub_type', $formatting))) {
              $errorMessage = ts("Mismatched or Invalid contact subtype found for this related contact.");
              array_unshift($values, $errorMessage);
              return CRM_Import_Parser::NO_MATCH;
            }
            else {
              $updatedContact = $this->createContact($formatting, $contactFields, $onDuplicate, $matchedIDs[0]);
            }
          }
          static $relativeContact = [];
          if (CRM_Core_Error::isAPIError($relatedNewContact, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
            if (count($matchedIDs) >= 1) {
              $relContactId = $matchedIDs[0];
              //add relative contact to count during update & fill mode.
              //logic to make count distinct by contact id.
              if ($this->_newRelatedContacts || !empty($relativeContact)) {
                $reContact = array_keys($relativeContact, $relContactId);

                if (empty($reContact)) {
                  $this->_newRelatedContacts[] = $relativeContact[] = $relContactId;
                }
              }
              else {
                $this->_newRelatedContacts[] = $relativeContact[] = $relContactId;
              }
            }
          }
          else {
            $relContactId = $relatedNewContact->id;
            $this->_newRelatedContacts[] = $relativeContact[] = $relContactId;
          }

          if (CRM_Core_Error::isAPIError($relatedNewContact, CRM_Core_ERROR::DUPLICATE_CONTACT) || ($relatedNewContact instanceof CRM_Contact_BAO_Contact)) {
            //fix for CRM-1993.Checks for duplicate related contacts
            if (count($matchedIDs) >= 1) {
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
          }
        }
      }
    }
    if ($this->_updateWithId) {
      //return warning if street address is unparsed, CRM-5886
      return $this->processMessage($values, $statusFieldName, $this->_retCode);
    }
    //dupe checking
    if (is_array($newContact) && civicrm_error($newContact)) {
      $code = NULL;

      if (($code = CRM_Utils_Array::value('code', $newContact['error_message'])) && ($code == CRM_Core_Error::DUPLICATE_CONTACT)) {
        return $this->handleDuplicateError($newContact, $statusFieldName, $values, $onDuplicate, $formatted, $contactFields);
      }
      // Not a dupe, so we had an error
      $errorMessage = $newContact['error_message'];
      array_unshift($values, $errorMessage);
      $this->setImportStatus((int) $values[count($values) - 1], 'ERROR', $errorMessage);
      return CRM_Import_Parser::ERROR;

    }
    // sleep(3);
    return $this->processMessage($values, $statusFieldName, CRM_Import_Parser::VALID);
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
        $contactID,
        // step 2
        $relatedContactID
      )
    ) {
      return [0, 1];
    }

    $singleInstanceParams = array_merge($params, $contactFields);
    CRM_Contact_BAO_Relationship::add($singleInstanceParams);
    return [1, 0];
  }

  /**
   * Format common params data to proper format to store.
   *
   * @param array $params
   *   Contain record values.
   * @param array $formatted
   *   Array of formatted data.
   * @param array $contactFields
   *   Contact DAO fields.
   */
  private function formatCommonData($params, &$formatted, $contactFields) {
    $csType = [
      CRM_Utils_Array::value('contact_type', $formatted),
    ];

    //CRM-5125
    //add custom fields for contact sub type
    if (!empty($this->_contactSubType)) {
      $csType = $this->_contactSubType;
    }

    if ($relCsType = CRM_Utils_Array::value('contact_sub_type', $formatted)) {
      $csType = $relCsType;
    }

    $customFields = CRM_Core_BAO_CustomField::getFields($formatted['contact_type'], FALSE, FALSE, $csType);

    $addressCustomFields = CRM_Core_BAO_CustomField::getFields('Address');
    $customFields = $customFields + $addressCustomFields;

    //if a Custom Email Greeting, Custom Postal Greeting or Custom Addressee is mapped, and no "Greeting / Addressee Type ID" is provided, then automatically set the type = Customized, CRM-4575
    $elements = [
      'email_greeting_custom' => 'email_greeting',
      'postal_greeting_custom' => 'postal_greeting',
      'addressee_custom' => 'addressee',
    ];
    foreach ($elements as $k => $v) {
      if (array_key_exists($k, $params) && !(array_key_exists($v, $params))) {
        $label = key(CRM_Core_OptionGroup::values($v, TRUE, NULL, NULL, 'AND v.name = "Customized"'));
        $params[$v] = $label;
      }
    }

    //format date first
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get("dateTypes");
    foreach ($params as $key => $val) {
      $customFieldID = CRM_Core_BAO_CustomField::getKeyID($key);
      if ($customFieldID &&
        !array_key_exists($customFieldID, $addressCustomFields)
      ) {
        //we should not update Date to null, CRM-4062
        if ($val && ($customFields[$customFieldID]['data_type'] == 'Date')) {
          //CRM-21267
          CRM_Contact_Import_Parser_Contact::formatCustomDate($params, $formatted, $dateType, $key);
        }
        elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
          if (empty($val) && !is_numeric($val) && $this->_onDuplicate == CRM_Import_Parser::DUPLICATE_FILL) {
            //retain earlier value when Import mode is `Fill`
            unset($params[$key]);
          }
          else {
            $params[$key] = CRM_Utils_String::strtoboolstr($val);
          }
        }
      }

      if ($key == 'birth_date' && $val) {
        CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key);
      }
      elseif ($key == 'deceased_date' && $val) {
        CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key);
        $params['is_deceased'] = 1;
      }
      elseif ($key == 'is_deceased' && $val) {
        $params[$key] = CRM_Utils_String::strtoboolstr($val);
      }
    }

    //now format custom data.
    foreach ($params as $key => $field) {
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
              // check if $value does not contain IM provider or phoneType
              if (($name !== 'phone_type_id' || $name !== 'provider_id') && ($testForEmpty === '' || $testForEmpty == NULL)) {
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
            else {
              // @todo - this is still reachable - e.g. import with related contact info like firstname,lastname,spouse-first-name,spouse-last-name,spouse-home-phone
              CRM_Core_Error::deprecatedFunctionWarning('this is not expected to be reachable now');
              $this->formatContactParameters($value, $formatted);
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

      if (($key !== 'preferred_communication_method') && (array_key_exists($key, $contactFields))) {
        // due to merging of individual table and
        // contact table, we need to avoid
        // preferred_communication_method forcefully
        $formatValues['contact_type'] = $formatted['contact_type'];
      }

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
            $value = strtolower(trim($formatted[$key]));
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
                if ($htmlType == 'CheckBox') {
                  $params[$key][$v2['value']] = $formatted[$key][$v2['value']] = 1;
                }
                else {
                  $params[$key][] = $formatted[$key][] = $v2['value'];
                }
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

    // to check if not update mode and unset the fields with empty value.
    if (!$this->_updateWithId && array_key_exists('custom', $formatted)) {
      foreach ($formatted['custom'] as $customKey => $customvalue) {
        if (empty($formatted['custom'][$customKey][-1]['is_required'])) {
          $formatted['custom'][$customKey][-1]['is_required'] = $customFields[$customKey]['is_required'];
        }
        $emptyValue = $customvalue[-1]['value'] ?? NULL;
        if (!isset($emptyValue)) {
          unset($formatted['custom'][$customKey]);
        }
      }
    }

    // parse street address, CRM-5450
    if ($this->_parseStreetAddress) {
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
   * Get the array of successfully imported contact id's
   *
   * @return array
   */
  public function getImportedContacts() {
    return $this->_newContacts;
  }

  /**
   * Get the array of successfully imported related contact id's
   *
   * @return array
   */
  public function &getRelatedImportedContacts() {
    return $this->_newRelatedContacts;
  }

  /**
   * Check if an error in custom data.
   *
   * @param array $params
   * @param string $errorMessage
   *   A string containing all the error-fields.
   *
   * @param null $csType
   * @param null $relationships
   */
  public static function isErrorInCustomData($params, &$errorMessage, $csType = NULL, $relationships = NULL) {
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
      $csType = self::getSubtypes($params['contact_type']);
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

    $addressCustomFields = CRM_Core_BAO_CustomField::getFields('Address');
    $customFields = $customFields + $addressCustomFields;
    foreach ($params as $key => $value) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        /* check if it's a valid custom field id */

        if (!array_key_exists($customFieldID, $customFields)) {
          $errors[] = ts('field ID');
        }
        // validate null values for required custom fields of type boolean
        if (!empty($customFields[$customFieldID]['is_required']) && (empty($params['custom_' . $customFieldID]) && !is_numeric($params['custom_' . $customFieldID])) && $customFields[$customFieldID]['data_type'] == 'Boolean') {
          $errors[] = $customFields[$customFieldID]['label'] . '::' . $customFields[$customFieldID]['groupTitle'];
        }

        //For address custom fields, we do get actual custom field value as an inner array of
        //values so need to modify
        if (array_key_exists($customFieldID, $addressCustomFields)) {
          $value = $value[0][$key];
        }
        /* validate the data against the CF type */

        if ($value) {
          $dataType = $customFields[$customFieldID]['data_type'];
          $htmlType = $customFields[$customFieldID]['html_type'];
          $isSerialized = CRM_Core_BAO_CustomField::isSerialized($customFields[$customFieldID]);
          if ($dataType == 'Date') {
            if (array_key_exists($customFieldID, $addressCustomFields) && CRM_Utils_Date::convertToDefaultDate($params[$key][0], $dateType, $key)) {
              $value = $params[$key][0][$key];
            }
            elseif (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              $value = $params[$key];
            }
            else {
              $errors[] = $customFields[$customFieldID]['label'];
            }
          }
          elseif ($dataType == 'Boolean') {
            if (CRM_Utils_String::strtoboolstr($value) === FALSE) {
              $errors[] = $customFields[$customFieldID]['label'] . '::' . $customFields[$customFieldID]['groupTitle'];
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
              $errors[] = $customFields[$customFieldID]['label'];
            }
          }

          // check for values for custom fields for checkboxes and multiselect
          if ($isSerialized && $dataType != 'ContactReference') {
            $value = trim($value);
            $value = str_replace('|', ',', $value);
            $mulValues = explode(',', $value);
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
            foreach ($mulValues as $v1) {
              if (strlen($v1) == 0) {
                continue;
              }

              $flag = FALSE;
              foreach ($customOption as $v2) {
                if ((strtolower(trim($v2['label'])) == strtolower(trim($v1))) || (strtolower(trim($v2['value'])) == strtolower(trim($v1)))) {
                  $flag = TRUE;
                }
              }

              if (!$flag) {
                $errors[] = $customFields[$customFieldID]['label'];
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
              $errors[] = $customFields[$customFieldID]['label'];
            }
          }
          elseif ($isSerialized && $dataType === 'StateProvince') {
            $mulValues = explode(',', $value);
            foreach ($mulValues as $stateValue) {
              if ($stateValue) {
                if (self::in_value(trim($stateValue), CRM_Core_PseudoConstant::stateProvinceAbbreviation()) || self::in_value(trim($stateValue), CRM_Core_PseudoConstant::stateProvince())) {
                  continue;
                }
                else {
                  $errors[] = $customFields[$customFieldID]['label'];
                }
              }
            }
          }
          elseif ($isSerialized && $dataType == 'Country') {
            $mulValues = explode(',', $value);
            foreach ($mulValues as $countryValue) {
              if ($countryValue) {
                CRM_Core_PseudoConstant::populate($countryNames, 'CRM_Core_DAO_Country', TRUE, 'name', 'is_active');
                CRM_Core_PseudoConstant::populate($countryIsoCodes, 'CRM_Core_DAO_Country', TRUE, 'iso_code');
                $limitCodes = CRM_Core_BAO_Country::countryLimit();

                $error = TRUE;
                foreach ([
                  $countryNames,
                  $countryIsoCodes,
                  $limitCodes,
                ] as $values) {
                  if (in_array(trim($countryValue), $values)) {
                    $error = FALSE;
                    break;
                  }
                }

                if ($error) {
                  $errors[] = $customFields[$customFieldID]['label'];
                }
              }
            }
          }
        }
      }
      elseif (is_array($params[$key]) && isset($params[$key]["contact_type"])) {
        //CRM-5125
        //supporting custom data of related contact subtypes
        $relation = NULL;
        if ($relationships) {
          if (array_key_exists($key, $relationships)) {
            $relation = $key;
          }
          elseif (CRM_Utils_Array::key($key, $relationships)) {
            $relation = CRM_Utils_Array::key($key, $relationships);
          }
        }
        if (!empty($relation)) {
          [$id, $first, $second] = CRM_Utils_System::explode('_', $relation, 3);
          $direction = "contact_sub_type_$second";
          $relationshipType = new CRM_Contact_BAO_RelationshipType();
          $relationshipType->id = $id;
          if ($relationshipType->find(TRUE)) {
            if (isset($relationshipType->$direction)) {
              $params[$key]['contact_sub_type'] = $relationshipType->$direction;
            }
          }
        }

        self::isErrorInCustomData($params[$key], $errorMessage, $csType, $relationships);
      }
    }
    if ($errors) {
      $errorMessage .= ($errorMessage ? '; ' : '') . implode('; ', $errors);
    }
  }

  /**
   * Check if value present in all genders or.
   * as a substring of any gender value, if yes than return corresponding gender.
   * eg value might be  m/M, ma/MA, mal/MAL, male return 'Male'
   * but if value is 'maleabc' than return false
   *
   * @param string $gender
   *   Check this value across gender values.
   *
   * retunr gender value / false
   *
   * @return bool
   */
  public function checkGender($gender) {
    $gender = trim($gender, '.');
    if (!$gender) {
      return FALSE;
    }

    $allGenders = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
    foreach ($allGenders as $key => $value) {
      if (strlen($gender) > strlen($value)) {
        continue;
      }
      if ($gender == $value) {
        return $value;
      }
      if (substr_compare($value, $gender, 0, strlen($gender), TRUE) === 0) {
        return $value;
      }
    }

    return FALSE;
  }

  /**
   * Check if an error in Core( non-custom fields ) field
   *
   * @param array $params
   * @param string $errorMessage
   *   A string containing all the error-fields.
   */
  public function isErrorInCoreData($params, &$errorMessage) {
    $errors = [];
    foreach ($params as $key => $value) {
      if ($value) {
        $session = CRM_Core_Session::singleton();
        $dateType = $session->get("dateTypes");

        switch ($key) {
          case 'birth_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                $errors[] = ts('Birth Date');
              }
            }
            else {
              $errors[] = ts('Birth-Date');
            }
            break;

          case 'deceased_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                $errors[] = ts('Deceased Date');
              }
            }
            else {
              $errors[] = ts('Deceased Date');
            }
            break;

          case 'is_deceased':
            if (CRM_Utils_String::strtoboolstr($value) === FALSE) {
              $errors[] = ts('Deceased');
            }
            break;

          case 'gender_id':
            if (!self::checkGender($value)) {
              $errors[] = ts('Gender');
            }
            break;

          case 'preferred_communication_method':
            $preffComm = [];
            $preffComm = explode(',', $value);
            foreach ($preffComm as $v) {
              if (!self::in_value(trim($v), CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method'))) {
                $errors[] = ts('Preferred Communication Method');
              }
            }
            break;

          case 'preferred_mail_format':
            if (!array_key_exists(strtolower($value), array_change_key_case(CRM_Core_SelectValues::pmf(), CASE_LOWER))) {
              $errors[] = ts('Preferred Mail Format');
            }
            break;

          case 'individual_prefix':
          case 'prefix_id':
            if (!self::in_value($value, CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id'))) {
              $errors[] = ts('Individual Prefix');
            }
            break;

          case 'individual_suffix':
          case 'suffix_id':
            if (!self::in_value($value, CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id'))) {
              $errors[] = ts('Individual Suffix');
            }
            break;

          case 'state_province':
            if (!empty($value)) {
              foreach ($value as $stateValue) {
                if ($stateValue['state_province']) {
                  if (self::in_value($stateValue['state_province'], CRM_Core_PseudoConstant::stateProvinceAbbreviation()) ||
                    self::in_value($stateValue['state_province'], CRM_Core_PseudoConstant::stateProvince())
                  ) {
                    continue;
                  }
                  else {
                    $errors[] = ts('State/Province');
                  }
                }
              }
            }
            break;

          case 'country':
            if (!empty($value)) {
              foreach ($value as $stateValue) {
                if ($stateValue['country']) {
                  CRM_Core_PseudoConstant::populate($countryNames, 'CRM_Core_DAO_Country', TRUE, 'name', 'is_active');
                  CRM_Core_PseudoConstant::populate($countryIsoCodes, 'CRM_Core_DAO_Country', TRUE, 'iso_code');
                  $limitCodes = CRM_Core_BAO_Country::countryLimit();
                  //If no country is selected in
                  //localization then take all countries
                  if (empty($limitCodes)) {
                    $limitCodes = $countryIsoCodes;
                  }

                  if (self::in_value($stateValue['country'], $limitCodes) || self::in_value($stateValue['country'], CRM_Core_PseudoConstant::country())) {
                    continue;
                  }
                  if (self::in_value($stateValue['country'], $countryIsoCodes) || self::in_value($stateValue['country'], $countryNames)) {
                    $errors[] = ts('Country input value is in table but not "available": "This Country is valid but is NOT in the list of Available Countries currently configured for your site. This can be viewed and modifed from Administer > Localization > Languages Currency Locations." ');
                  }
                  else {
                    $errors[] = ts('Country input value not in country table: "The Country value appears to be invalid. It does not match any value in CiviCRM table of countries."');
                  }
                }
              }
            }
            break;

          case 'county':
            if (!empty($value)) {
              foreach ($value as $county) {
                if ($county['county']) {
                  $countyNames = CRM_Core_PseudoConstant::county();
                  if (!empty($county['county']) && !in_array($county['county'], $countyNames)) {
                    $errors[] = ts('County input value not in county table: The County value appears to be invalid. It does not match any value in CiviCRM table of counties.');
                  }
                }
              }
            }
            break;

          case 'geo_code_1':
            if (!empty($value)) {
              foreach ($value as $codeValue) {
                if (!empty($codeValue['geo_code_1'])) {
                  if (CRM_Utils_Rule::numeric($codeValue['geo_code_1'])) {
                    continue;
                  }
                  $errors[] = ts('Geo code 1');
                }
              }
            }
            break;

          case 'geo_code_2':
            if (!empty($value)) {
              foreach ($value as $codeValue) {
                if (!empty($codeValue['geo_code_2'])) {
                  if (CRM_Utils_Rule::numeric($codeValue['geo_code_2'])) {
                    continue;
                  }
                  $errors[] = ts('Geo code 2');
                }
              }
            }
            break;

          //check for any error in email/postal greeting, addressee,
          //custom email/postal greeting, custom addressee, CRM-4575

          case 'email_greeting':
            $emailGreetingFilter = [
              'contact_type' => $this->_contactType,
              'greeting_type' => 'email_greeting',
            ];
            if (!self::in_value($value, CRM_Core_PseudoConstant::greeting($emailGreetingFilter))) {
              $errors[] = ts('Email Greeting must be one of the configured format options. Check Administer >> System Settings >> Option Groups >> Email Greetings for valid values');
            }
            break;

          case 'postal_greeting':
            $postalGreetingFilter = [
              'contact_type' => $this->_contactType,
              'greeting_type' => 'postal_greeting',
            ];
            if (!self::in_value($value, CRM_Core_PseudoConstant::greeting($postalGreetingFilter))) {
              $errors[] = ts('Postal Greeting must be one of the configured format options. Check Administer >> System Settings >> Option Groups >> Postal Greetings for valid values');
            }
            break;

          case 'addressee':
            $addresseeFilter = [
              'contact_type' => $this->_contactType,
              'greeting_type' => 'addressee',
            ];
            if (!self::in_value($value, CRM_Core_PseudoConstant::greeting($addresseeFilter))) {
              $errors[] = ts('Addressee must be one of the configured format options. Check Administer >> System Settings >> Option Groups >> Addressee for valid values');
            }
            break;

          case 'email_greeting_custom':
            if (array_key_exists('email_greeting', $params)) {
              $emailGreetingLabel = key(CRM_Core_OptionGroup::values('email_greeting', TRUE, NULL, NULL, 'AND v.name = "Customized"'));
              if (CRM_Utils_Array::value('email_greeting', $params) != $emailGreetingLabel) {
                $errors[] = ts('Email Greeting - Custom');
              }
            }
            break;

          case 'postal_greeting_custom':
            if (array_key_exists('postal_greeting', $params)) {
              $postalGreetingLabel = key(CRM_Core_OptionGroup::values('postal_greeting', TRUE, NULL, NULL, 'AND v.name = "Customized"'));
              if (CRM_Utils_Array::value('postal_greeting', $params) != $postalGreetingLabel) {
                $errors[] = ts('Postal Greeting - Custom');
              }
            }
            break;

          case 'addressee_custom':
            if (array_key_exists('addressee', $params)) {
              $addresseeLabel = key(CRM_Core_OptionGroup::values('addressee', TRUE, NULL, NULL, 'AND v.name = "Customized"'));
              if (CRM_Utils_Array::value('addressee', $params) != $addresseeLabel) {
                $errors[] = ts('Addressee - Custom');
              }
            }
            break;

          case 'url':
            if (is_array($value)) {
              foreach ($value as $values) {
                if (!empty($values['url']) && !CRM_Utils_Rule::url($values['url'])) {
                  $errors[] = ts('Website');
                  break;
                }
              }
            }
            break;

          case 'do_not_email':
          case 'do_not_phone':
          case 'do_not_mail':
          case 'do_not_sms':
          case 'do_not_trade':
            if (CRM_Utils_Rule::boolean($value) == FALSE) {
              $key = ucwords(str_replace("_", " ", $key));
              $errors[] = $key;
            }
            break;

          case 'email':
            if (is_array($value)) {
              foreach ($value as $values) {
                if (!empty($values['email']) && !CRM_Utils_Rule::email($values['email'])) {
                  $errors[] = $key;
                  break;
                }
              }
            }
            break;

          default:
            if (is_array($params[$key]) && isset($params[$key]["contact_type"])) {
              //check for any relationship data ,FIX ME
              self::isErrorInCoreData($params[$key], $errorMessage);
            }
        }
      }
    }
    if ($errors) {
      $errorMessage .= ($errorMessage ? '; ' : '') . implode('; ', $errors);
    }
  }

  /**
   * Ckeck a value present or not in a array.
   *
   * @param $value
   * @param $valueArray
   *
   * @return bool
   */
  public static function in_value($value, $valueArray) {
    foreach ($valueArray as $key => $v) {
      //fix for CRM-1514
      if (strtolower(trim($v, ".")) == strtolower(trim($value, "."))) {
        return TRUE;
      }
    }
    return FALSE;
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
   * Method for creating contact.
   *
   * @param array $formatted
   * @param array $contactFields
   * @param int $onDuplicate
   * @param int $contactId
   * @param bool $requiredCheck
   * @param int $dedupeRuleGroupID
   *
   * @return array|bool|\CRM_Contact_BAO_Contact|\CRM_Core_Error|null
   */
  public function createContact(&$formatted, &$contactFields, $onDuplicate, $contactId = NULL, $requiredCheck = TRUE, $dedupeRuleGroupID = NULL) {
    $dupeCheck = FALSE;
    $newContact = NULL;

    if (is_null($contactId) && ($onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK)) {
      $dupeCheck = (bool) ($onDuplicate);
    }

    //get the prefix id etc if exists
    CRM_Contact_BAO_Contact::resolveDefaults($formatted, TRUE);

    //@todo direct call to API function not supported.
    // setting required check to false, CRM-2839
    // plus we do our own required check in import
    try {
      $error = $this->deprecated_contact_check_params($formatted, $dupeCheck, $dedupeRuleGroupID);
      if ($error) {
        return $error;
      }
      $this->deprecated_validate_formatted_contact($formatted);
    }
    catch (CRM_Core_Exception $e) {
      return ['error_message' => $e->getMessage(), 'is_error' => 1, 'code' => $e->getCode()];
    }

    if ($contactId) {
      $this->formatParams($formatted, $onDuplicate, (int) $contactId);
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

    [$data, $contactDetails] = CRM_Contact_BAO_Contact::formatProfileContactParams($formatted, $contactFields, $contactId, NULL, $formatted['contact_type']);

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
    if ($this->_parseStreetAddress && is_object($newContact) && property_exists($newContact, 'address') && $newContact->address) {
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
   * Format params for update and fill mode.
   *
   * @param array $params
   *   reference to an array containing all the.
   *   values for import
   * @param int $onDuplicate
   * @param int $cid
   *   contact id.
   */
  public function formatParams(&$params, $onDuplicate, $cid) {
    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
      return;
    }

    $contactParams = [
      'contact_id' => $cid,
    ];

    $defaults = [];
    $contactObj = CRM_Contact_BAO_Contact::retrieve($contactParams, $defaults);

    $modeFill = ($onDuplicate == CRM_Import_Parser::DUPLICATE_FILL);

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($params['contact_type'], NULL, $cid, 0, NULL);
    CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $defaults, FALSE, FALSE);

    $locationFields = [
      'email' => 'email',
      'phone' => 'phone',
      'im' => 'name',
      'website' => 'website',
      'address' => 'address',
    ];

    $contact = get_object_vars($contactObj);

    foreach ($params as $key => $value) {
      if ($key == 'id' || $key == 'contact_type') {
        continue;
      }

      if (array_key_exists($key, $locationFields)) {
        continue;
      }
      if (in_array($key, [
        'email_greeting',
        'postal_greeting',
        'addressee',
      ])) {
        // CRM-4575, need to null custom
        if ($params["{$key}_id"] != 4) {
          $params["{$key}_custom"] = 'null';
        }
        unset($params[$key]);
      }
      else {
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
        if ($key == 'contact_source') {
          $params['source'] = $params[$key];
          unset($params[$key]);
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
    }

    foreach ($locationFields as $locKeys) {
      if (isset($params[$locKeys]) && is_array($params[$locKeys])) {
        foreach ($params[$locKeys] as $key => $value) {
          if ($modeFill) {
            $getValue = CRM_Utils_Array::retrieveValueRecursive($contact, $locKeys);

            if (isset($getValue)) {
              foreach ($getValue as $cnt => $values) {
                if ($locKeys == 'website') {
                  if (($getValue[$cnt]['website_type_id'] == $params[$locKeys][$key]['website_type_id'])) {
                    unset($params[$locKeys][$key]);
                  }
                }
                else {
                  if ((!empty($getValue[$cnt]['location_type_id']) && !empty($params[$locKeys][$key]['location_type_id'])) && $getValue[$cnt]['location_type_id'] == $params[$locKeys][$key]['location_type_id']) {
                    unset($params[$locKeys][$key]);
                  }
                }
              }
            }
          }
        }
        if (count($params[$locKeys]) == 0) {
          unset($params[$locKeys]);
        }
      }
    }
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
   * Generate status and error message for unparsed street address records.
   *
   * @param array $values
   *   The array of values belonging to each row.
   * @param array $statusFieldName
   *   Store formatted date in this array.
   * @param $returnCode
   *
   * @return int
   */
  public function processMessage(&$values, $statusFieldName, $returnCode) {
    if (empty($this->_unparsedStreetAddressContacts)) {
      $this->setImportStatus((int) ($values[count($values) - 1]), 'IMPORTED', '');
    }
    else {
      $errorMessage = ts("Record imported successfully but unable to parse the street address: ");
      foreach ($this->_unparsedStreetAddressContacts as $contactInfo => $contactValue) {
        $contactUrl = CRM_Utils_System::url('civicrm/contact/add', 'reset=1&action=update&cid=' . $contactValue['id'], TRUE, NULL, FALSE);
        $errorMessage .= "\n Contact ID:" . $contactValue['id'] . " <a href=\"$contactUrl\"> " . $contactValue['streetAddress'] . "</a>";
      }
      array_unshift($values, $errorMessage);
      $returnCode = CRM_Import_Parser::UNPARSED_ADDRESS_WARNING;
      $this->setImportStatus((int) ($values[count($values) - 1]), 'ERROR', $errorMessage);
    }
    return $returnCode;
  }

  /**
   * @param $relKey
   * @param array $params
   *
   * @return bool
   */
  public function checkRelatedContactFields($relKey, $params) {
    //avoid blank contact creation.
    $allowToCreate = FALSE;

    //build the mapper field array.
    static $relatedContactFields = [];
    if (!isset($relatedContactFields[$relKey])) {
      foreach ($this->_mapperRelated as $key => $name) {
        if (!$name) {
          continue;
        }

        if (!empty($relatedContactFields[$name]) && !is_array($relatedContactFields[$name])) {
          $relatedContactFields[$name] = [];
        }
        $fldName = $this->_mapperRelatedContactDetails[$key] ?? NULL;
        if ($fldName == 'url') {
          $fldName = 'website';
        }
        if ($fldName) {
          $relatedContactFields[$name][] = $fldName;
        }
      }
    }

    //validate for passed data.
    if (is_array($relatedContactFields[$relKey])) {
      foreach ($relatedContactFields[$relKey] as $fld) {
        if (!empty($params[$fld])) {
          $allowToCreate = TRUE;
          break;
        }
      }
    }

    return $allowToCreate;
  }

  /**
   * get subtypes given the contact type
   *
   * @param string $contactType
   * @return array $subTypes
   */
  public static function getSubtypes($contactType) {
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
   * Get the possible contact matches.
   *
   * 1) the chosen dedupe rule falling back to
   * 2) a check for the external ID.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-17275
   *
   * @param array $params
   *
   * @return array
   *   IDs of possible matches.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected function getPossibleContactMatches($params) {
    $extIDMatch = NULL;

    if (!empty($params['external_identifier'])) {
      // Check for any match on external id, deleted or otherwise.
      $extIDContact = civicrm_api3('Contact', 'get', [
        'external_identifier' => $params['external_identifier'],
        'showAll' => 'all',
        'return' => ['id', 'contact_is_deleted'],
      ]);
      if (isset($extIDContact['id'])) {
        $extIDMatch = $extIDContact['id'];

        if ($extIDContact['values'][$extIDMatch]['contact_is_deleted'] == 1) {
          // If the contact is deleted, update external identifier to be blank
          // to avoid key error from MySQL.
          $params = ['id' => $extIDMatch, 'external_identifier' => ''];
          civicrm_api3('Contact', 'create', $params);

          // And now it is no longer a match.
          $extIDMatch = NULL;
        }
      }
    }
    $checkParams = ['check_permissions' => FALSE, 'match' => $params];
    $checkParams['match']['contact_type'] = $this->_contactType;

    $possibleMatches = civicrm_api3('Contact', 'duplicatecheck', $checkParams);
    if (!$extIDMatch) {
      return array_keys($possibleMatches['values']);
    }
    if ($possibleMatches['count']) {
      if (array_key_exists($extIDMatch, $possibleMatches['values'])) {
        return [$extIDMatch];
      }
      throw new CRM_Core_Exception(ts(
        'Matching this contact based on the de-dupe rule would cause an external ID conflict'));
    }
    return [$extIDMatch];
  }

  /**
   * Format the form mapping parameters ready for the parser.
   *
   * @param int $count
   *   Number of rows.
   *
   * @return array $parserParameters
   */
  public static function getParameterForParser($count) {
    $baseArray = [];
    for ($i = 0; $i < $count; $i++) {
      $baseArray[$i] = NULL;
    }
    $parserParameters['mapperLocType'] = $baseArray;
    $parserParameters['mapperPhoneType'] = $baseArray;
    $parserParameters['mapperImProvider'] = $baseArray;
    $parserParameters['mapperWebsiteType'] = $baseArray;
    $parserParameters['mapperRelated'] = $baseArray;
    $parserParameters['relatedContactType'] = $baseArray;
    $parserParameters['relatedContactDetails'] = $baseArray;
    $parserParameters['relatedContactLocType'] = $baseArray;
    $parserParameters['relatedContactPhoneType'] = $baseArray;
    $parserParameters['relatedContactImProvider'] = $baseArray;
    $parserParameters['relatedContactWebsiteType'] = $baseArray;

    return $parserParameters;

  }

  /**
   * Set field metadata.
   */
  protected function setFieldMetadata() {
    $this->setImportableFieldsMetadata($this->getContactImportMetadata());
    // Probably no longer needed but here for now.
    $this->_relationships = $this->getRelationships();
  }

  /**
   * @param array $newContact
   * @param $statusFieldName
   * @param array $values
   * @param int $onDuplicate
   * @param array $formatted
   * @param array $contactFields
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function handleDuplicateError(array $newContact, $statusFieldName, array $values, int $onDuplicate, array $formatted, array $contactFields): int {
    $urls = [];
    // need to fix at some stage and decide if the error will return an
    // array or string, crude hack for now
    if (is_array($newContact['error_message']['params'][0])) {
      $cids = $newContact['error_message']['params'][0];
    }
    else {
      $cids = explode(',', $newContact['error_message']['params'][0]);
    }

    foreach ($cids as $cid) {
      $urls[] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $cid, TRUE);
    }

    $url_string = implode("\n", $urls);

    // If we duplicate more than one record, skip no matter what
    if (count($cids) > 1) {
      $errorMessage = ts('Record duplicates multiple contacts');
      //combine error msg to avoid mismatch between error file columns.
      $errorMessage .= "\n" . $url_string;
      array_unshift($values, $errorMessage);
      $this->setImportStatus((int) $values[count($values) - 1], 'ERROR', $errorMessage);
      return CRM_Import_Parser::ERROR;
    }

    // Params only had one id, so shift it out
    $contactId = array_shift($cids);
    $cid = NULL;

    $vals = ['contact_id' => $contactId];

    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_REPLACE) {
      civicrm_api('contact', 'delete', $vals);
      $cid = CRM_Contact_BAO_Contact::createProfileContact($formatted, $contactFields, $contactId, NULL, NULL, $formatted['contact_type']);
    }
    if (in_array((int) $onDuplicate, [CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::DUPLICATE_FILL], TRUE)) {
      $newContact = $this->createContact($formatted, $contactFields, $onDuplicate, $contactId);
    }
    // else skip does nothing and just returns an error code.
    if ($cid) {
      $contact = [
        'contact_id' => $cid,
      ];
      $defaults = [];
      $newContact = CRM_Contact_BAO_Contact::retrieve($contact, $defaults);
    }

    if (civicrm_error($newContact)) {
      if (empty($newContact['error_message']['params'])) {
        // different kind of error other than DUPLICATE
        $errorMessage = $newContact['error_message'];
        array_unshift($values, $errorMessage);
        $this->setImportStatus((int) $values[count($values) - 1], 'ERROR', $errorMessage);
        return CRM_Import_Parser::ERROR;
      }

      $contactID = $newContact['error_message']['params'][0];
      if (is_array($contactID)) {
        $contactID = array_pop($contactID);
      }
      if (!in_array($contactID, $this->_newContacts)) {
        $this->_newContacts[] = $contactID;
      }
    }
    //CRM-262 No Duplicate Checking
    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
      array_unshift($values, $url_string);
      $this->setImportStatus((int) $values[count($values) - 1], 'DUPLICATE', 'Skipping duplicate record');
      return CRM_Import_Parser::DUPLICATE;
    }

    $this->setImportStatus((int) $values[count($values) - 1], 'Imported', '');
    //return warning if street address is not parsed, CRM-5886
    return $this->processMessage($values, $statusFieldName, CRM_Import_Parser::VALID);
  }

  /**
   * Validate a formatted contact parameter list.
   *
   * @param array $params
   *   Structured parameter list (as in crm_format_params).
   *
   * @throw CRM_Core_Error
   */
  public function deprecated_validate_formatted_contact(&$params): void {
    // Look for offending email addresses

    if (array_key_exists('email', $params)) {
      foreach ($params['email'] as $count => $values) {
        if (!is_array($values)) {
          continue;
        }
        if ($email = CRM_Utils_Array::value('email', $values)) {
          // validate each email
          if (!CRM_Utils_Rule::email($email)) {
            throw new CRM_Core_Exception('No valid email address');
          }

          // check for loc type id.
          if (empty($values['location_type_id'])) {
            throw new CRM_Core_Exception('Location Type Id missing.');
          }
        }
      }
    }

    // Validate custom data fields
    if (array_key_exists('custom', $params) && is_array($params['custom'])) {
      foreach ($params['custom'] as $key => $custom) {
        if (is_array($custom)) {
          foreach ($custom as $fieldId => $value) {
            $valid = CRM_Core_BAO_CustomValue::typecheck(CRM_Utils_Array::value('type', $value),
              CRM_Utils_Array::value('value', $value)
            );
            if (!$valid && $value['is_required']) {
              throw new CRM_Core_Exception('Invalid value for custom field \'' .
                $custom['name'] . '\''
              );
            }
            if (CRM_Utils_Array::value('type', $custom) == 'Date') {
              $params['custom'][$key][$fieldId]['value'] = str_replace('-', '', $params['custom'][$key][$fieldId]['value']);
            }
          }
        }
      }
    }
  }

  /**
   * @param array $params
   * @param bool $dupeCheck
   * @param null|int $dedupeRuleGroupID
   *
   * @throws \CRM_Core_Exception
   */
  public function deprecated_contact_check_params(
    &$params,
    $dupeCheck = TRUE,
    $dedupeRuleGroupID = NULL) {

    $requiredCheck = TRUE;

    if (isset($params['id']) && is_numeric($params['id'])) {
      $requiredCheck = FALSE;
    }
    if ($requiredCheck) {
      $required = [
        'Individual' => [
          ['first_name', 'last_name'],
          'email',
        ],
        'Household' => [
          'household_name',
        ],
        'Organization' => [
          'organization_name',
        ],
      ];

      // contact_type has a limited number of valid values
      if (empty($params['contact_type'])) {
        throw new CRM_Core_Exception("No Contact Type");
      }
      $fields = $required[$params['contact_type']] ?? NULL;
      if ($fields == NULL) {
        throw new CRM_Core_Exception("Invalid Contact Type: {$params['contact_type']}");
      }

      if ($csType = CRM_Utils_Array::value('contact_sub_type', $params)) {
        if (!(CRM_Contact_BAO_ContactType::isExtendsContactType($csType, $params['contact_type']))) {
          throw new CRM_Core_Exception("Invalid or Mismatched Contact Subtype: " . implode(', ', (array) $csType));
        }
      }

      if (empty($params['contact_id']) && !empty($params['id'])) {
        $valid = FALSE;
        $error = '';
        foreach ($fields as $field) {
          if (is_array($field)) {
            $valid = TRUE;
            foreach ($field as $element) {
              if (empty($params[$element])) {
                $valid = FALSE;
                $error .= $element;
                break;
              }
            }
          }
          else {
            if (!empty($params[$field])) {
              $valid = TRUE;
            }
          }
          if ($valid) {
            break;
          }
        }

        if (!$valid) {
          throw new CRM_Core_Exception("Required fields not found for {$params['contact_type']} : $error");
        }
      }
    }

    if ($dupeCheck) {
      // @todo switch to using api version
      // $dupes = civicrm_api3('Contact', 'duplicatecheck', (array('match' => $params, 'dedupe_rule_id' => $dedupeRuleGroupID)));
      // $ids = $dupes['count'] ? implode(',', array_keys($dupes['values'])) : NULL;
      $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($params, $params['contact_type'], 'Unsupervised', [], CRM_Utils_Array::value('check_permissions', $params), $dedupeRuleGroupID);
      if ($ids != NULL) {
        $error = CRM_Core_Error::createError("Found matching contacts: " . implode(',', $ids),
          CRM_Core_Error::DUPLICATE_CONTACT,
          'Fatal', $ids
        );
        return civicrm_api3_create_error($error->pop());
      }
    }

    // check for organisations with same name
    if (!empty($params['current_employer'])) {
      $organizationParams = ['organization_name' => $params['current_employer']];
      $dupeIds = CRM_Contact_BAO_Contact::getDuplicateContacts($organizationParams, 'Organization', 'Supervised', [], FALSE);

      // check for mismatch employer name and id
      if (!empty($params['employer_id']) && !in_array($params['employer_id'], $dupeIds)
      ) {
        throw new CRM_Core_Exception('Employer name and Employer id Mismatch');
      }

      // show error if multiple organisation with same name exist
      if (empty($params['employer_id']) && (count($dupeIds) > 1)
      ) {
        return civicrm_api3_create_error('Found more than one Organisation with same Name.');
      }
    }
  }

  /**
   * Run import.
   *
   * @param string $tableName
   * @param array $mapper
   * @param int $mode
   * @param int $contactType
   * @param string $primaryKeyName
   * @param string $statusFieldName
   * @param int $onDuplicate
   * @param int $statusID
   * @param int $totalRowCount
   *
   * @return mixed
   * @throws \API_Exception
   */
  public function run(
    $tableName,
    $mapper = [],
    $mode = self::MODE_PREVIEW,
    $contactType = self::CONTACT_INDIVIDUAL,
    $primaryKeyName = '_id',
    $statusFieldName = '_status',
    $onDuplicate = self::DUPLICATE_SKIP,
    $statusID = NULL,
    $totalRowCount = NULL
  ) {

    // TODO: Make the timeout actually work
    $this->_onDuplicate = $onDuplicate = $this->getSubmittedValue('onDuplicate');
    $this->_dedupeRuleGroupID = $this->getSubmittedValue('dedupe_rule_id');
    // Since $this->_contactType is still being called directly do a get call
    // here to make sure it is instantiated.
    $this->getContactType();
    $this->getContactSubType();

    $this->init();

    $this->_rowCount = 0;
    $this->_invalidRowCount = $this->_validCount = 0;
    $this->_totalCount = 0;

    $this->_errors = [];
    $this->_warnings = [];
    $this->_unparsedAddresses = [];

    $this->_tableName = $tableName = $this->getUserJob()['metadata']['DataSource']['table_name'];
    $this->_primaryKeyName = '_id';
    $this->_statusFieldName = '_status';

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = [];
    }
    else {
      $this->_activeFieldCount = count($this->_activeFields);
    }

    if ($statusID) {
      $this->progressImport($statusID);
      $startTimestamp = $currTimestamp = $prevTimestamp = time();
    }
    // get the contents of the temp. import table
    $query = "SELECT * FROM $tableName";
    if ($mode == self::MODE_IMPORT) {
      $query .= " WHERE _status = 'NEW'";
    }
    if ($this->_maxLinesToProcess > 0) {
      // Note this would only be the case in MapForm mode, where it is set to 100
      // rows. In fact mapField really only needs 2 rows - the reason for
      // 100 seems to be that the other import classes are processing a
      // csv file, and there was a concern that some rows might have more
      // columns than others - hence checking 100 rows perhaps seemed like
      // a good precaution presumably when determining the activeFieldsCount
      // which is the number of columns a row might have.
      // However, the mapField class may no longer use activeFieldsCount for contact
      // to be continued....
      $query .= ' LIMIT ' . $this->_maxLinesToProcess;
    }

    $result = CRM_Core_DAO::executeQuery($query);

    while ($result->fetch()) {
      $values = array_values($result->toArray());
      $this->_rowCount++;

      /* trim whitespace around the values */
      foreach ($values as $k => $v) {
        $values[$k] = trim($v, " \t\r\n");
      }

      $this->_totalCount++;

      if ($mode == self::MODE_MAPFIELD) {
        $returnCode = CRM_Import_Parser::VALID;
      }
      elseif ($mode == self::MODE_PREVIEW) {
        $returnCode = $this->preview($values);
      }
      elseif ($mode == self::MODE_SUMMARY) {
        $returnCode = $this->summary($values);
      }
      elseif ($mode == self::MODE_IMPORT) {
        try {
          $returnCode = $this->import($onDuplicate, $values);
        }
        catch (CiviCRM_API3_Exception $e) {
          // When we catch errors here we are not adding to the errors array - mostly
          // because that will become obsolete once https://github.com/civicrm/civicrm-core/pull/23292
          // is merged and this will replace it as the main way to handle errors (ie. update the table
          // and move on).
          $this->setImportStatus((int) $values[count($values) - 1], 'ERROR', $e->getMessage());
        }
        if ($statusID && (($this->_rowCount % 50) == 0)) {
          $prevTimestamp = $this->progressImport($statusID, FALSE, $startTimestamp, $prevTimestamp, $totalRowCount);
        }
      }
      else {
        $returnCode = self::ERROR;
      }

      // note that a line could be valid but still produce a warning
      if ($returnCode & self::VALID) {
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode & self::ERROR) {
        $this->_invalidRowCount++;
        array_unshift($values, $this->_rowCount);
        $this->_errors[] = $values;
      }

      if ($returnCode & self::NO_MATCH) {
        $this->_unMatchCount++;
        array_unshift($values, $this->_rowCount);
        $this->_unMatch[] = $values;
      }

      if ($returnCode & self::DUPLICATE) {
        $this->_duplicateCount++;
        array_unshift($values, $this->_rowCount);
        $this->_duplicates[] = $values;
        if ($onDuplicate != self::DUPLICATE_SKIP) {
          $this->_validCount++;
        }
      }

      if ($returnCode & self::UNPARSED_ADDRESS_WARNING) {
        $this->_unparsedAddressCount++;
        array_unshift($values, $this->_rowCount);
        $this->_unparsedAddresses[] = $values;
      }

      // see if we've hit our timeout yet
      /* if ( $the_thing_with_the_stuff ) {
      do_something( );
      } */
    }

    if ($mode == self::MODE_PREVIEW || $mode == self::MODE_IMPORT) {
      $customHeaders = $mapper;

      $customfields = CRM_Core_BAO_CustomField::getFields($this->_contactType);
      foreach ($customHeaders as $key => $value) {
        if ($id = CRM_Core_BAO_CustomField::getKeyID($value)) {
          $customHeaders[$key] = $customfields[$id][0];
        }
      }

      if ($this->_unMatchCount) {
        $headers = array_merge([
          ts('Line Number'),
          ts('Reason'),
        ], $customHeaders);

        $this->_misMatchFilemName = self::errorFileName(self::NO_MATCH);
        self::exportCSV($this->_misMatchFilemName, $headers, $this->_unMatch);
      }
      if ($this->_unparsedAddressCount) {
        $headers = array_merge([
          ts('Line Number'),
          ts('Contact Edit URL'),
        ], $customHeaders);
        $this->_errorFileName = self::errorFileName(self::UNPARSED_ADDRESS_WARNING);
        self::exportCSV($this->_errorFileName, $headers, $this->_unparsedAddresses);
      }
    }
  }

  /**
   * Given a list of the importable field keys that the user has selected.
   * set the active fields array to this list
   *
   * @param array $fieldKeys
   *   Mapped array of values.
   */
  public function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key])) {
        $this->_activeFields[] = new CRM_Contact_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  /**
   * @param $elements
   */
  public function setActiveFieldLocationTypes($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_hasLocationType = $elements[$i];
    }
  }

  /**
   * @param $elements
   */

  /**
   * @param $elements
   */
  public function setActiveFieldPhoneTypes($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_phoneType = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  public function setActiveFieldWebsiteTypes($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_websiteType = $elements[$i];
    }
  }

  /**
   * Set IM Service Provider type fields.
   *
   * @param array $elements
   *   IM service provider type ids.
   */
  public function setActiveFieldImProviders($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_imProvider = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  public function setActiveFieldRelated($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_related = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  public function setActiveFieldRelatedContactType($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactType = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  public function setActiveFieldRelatedContactDetails($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactDetails = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  public function setActiveFieldRelatedContactLocType($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactLocType = $elements[$i];
    }
  }

  /**
   * Set active field for related contact's phone type.
   *
   * @param array $elements
   */
  public function setActiveFieldRelatedContactPhoneType($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactPhoneType = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  public function setActiveFieldRelatedContactWebsiteType($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactWebsiteType = $elements[$i];
    }
  }

  /**
   * Set IM Service Provider type fields for related contacts.
   *
   * @param array $elements
   *   IM service provider type ids of related contact.
   */
  public function setActiveFieldRelatedContactImProvider($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactImProvider = $elements[$i];
    }
  }

  /**
   * Format the field values for input to the api.
   *
   * @return array
   *   (reference ) associative array of name/value pairs
   */
  public function &getActiveFieldParams() {
    $params = [];

    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      $fieldName = $this->_activeFields[$i]->_name;
      if ($fieldName === 'do_not_import') {
        continue;
      }
      $relatedContactFieldName = $this->_activeFields[$i]->_relatedContactDetails;
      $relatedContactType = $this->_activeFields[$i]->_relatedContactType;
      $relatedContactLocationTypeID = $this->_activeFields[$i]->_relatedContactLocType;
      $relatedContactWebsiteTypeID = $this->_activeFields[$i]->_relatedContactWebsiteType ?? NULL;
      $relatedContactIMProviderID = $this->_activeFields[$i]->_relatedContactImProvider ?? NULL;
      $relatedContactPhoneTypeID = $this->_activeFields[$i]->_relatedContactPhoneType ?? NULL;
      // The key is in the format 5_a_b where 5 is the relationship_type_id and a_b is the direction.
      $relatedContactKey = $this->_activeFields[$i]->_related;

      $locationTypeID = $this->_activeFields[$i]->_hasLocationType;
      $phoneTypeID = $this->_activeFields[$i]->_phoneType;
      $imProviderID = $this->_activeFields[$i]->_imProvider ?? NULL;
      $websiteTypeID = $this->_activeFields[$i]->_websiteType ?? NULL;

      $importedValue = $this->_activeFields[$i]->_value;

      if (isset($importedValue)) {
        if (isset($locationTypeID)) {
          if (!isset($params[$fieldName])) {
            $params[$fieldName] = [];
          }

          $value = [
            $fieldName => $importedValue,
            'location_type_id' => $locationTypeID,
          ];

          if (isset($phoneTypeID)) {
            $value['phone_type_id'] = $phoneTypeID;
          }

          // get IM service Provider type id
          if (isset($imProviderID)) {
            $value['provider_id'] = $imProviderID;
          }

          $params[$fieldName][] = $value;
        }
        elseif (isset($websiteTypeID)) {
          $value = [
            $fieldName => $importedValue,
            'website_type_id' => $websiteTypeID,
          ];

          $params[$fieldName][] = $value;
        }

        if (!isset($params[$fieldName])) {
          if (!isset($relatedContactKey)) {
            $params[$fieldName] = $importedValue;
          }
        }

        //minor fix for CRM-4062
        if (isset($relatedContactKey)) {
          if (!isset($params[$relatedContactKey])) {
            $params[$relatedContactKey] = [];
          }

          if (!isset($params[$relatedContactKey]['contact_type']) && !empty($relatedContactType)) {
            $params[$relatedContactKey]['contact_type'] = $relatedContactType;
          }

          if (isset($relatedContactLocationTypeID) && !empty($importedValue)) {
            if (!empty($params[$relatedContactKey][$relatedContactFieldName]) &&
              !is_array($params[$relatedContactKey][$relatedContactFieldName])
            ) {
              $params[$relatedContactKey][$relatedContactFieldName] = [];
            }
            $value = [
              $relatedContactFieldName => $importedValue,
              'location_type_id' => $relatedContactLocationTypeID,
            ];

            if (isset($relatedContactPhoneTypeID)) {
              $value['phone_type_id'] = $relatedContactPhoneTypeID;
            }

            // get IM service Provider type id for related contact
            if (isset($relatedContactIMProviderID)) {
              $value['provider_id'] = $relatedContactIMProviderID;
            }

            $params[$relatedContactKey][$relatedContactFieldName][] = $value;
          }
          elseif (isset($relatedContactWebsiteTypeID)) {
            $params[$relatedContactKey][$relatedContactFieldName][] = [
              'url' => $importedValue,
              'website_type_id' => $relatedContactWebsiteTypeID,
            ];
          }
          elseif (empty($importedValue) && isset($relatedContactLocationTypeID)) {
            if (empty($params[$relatedContactKey][$relatedContactFieldName])) {
              $params[$relatedContactKey][$relatedContactFieldName] = [];
            }
          }
          else {
            $params[$relatedContactKey][$relatedContactFieldName] = $importedValue;
          }
        }
      }
    }

    return $params;
  }

  /**
   * @param string $name
   * @param $title
   * @param int $type
   * @param string $headerPattern
   * @param string $dataPattern
   * @param bool $hasLocationType
   */
  public function addField(
    $name, $title, $type = CRM_Utils_Type::T_INT,
    $headerPattern = '//', $dataPattern = '//',
    $hasLocationType = FALSE
  ) {
    $this->_fields[$name] = new CRM_Contact_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Contact_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    }
  }

  /**
   * Store parser values.
   *
   * @param CRM_Core_Session $store
   *
   * @param int $mode
   */
  public function set($store, $mode = self::MODE_SUMMARY) {
    // @todo - this params are being set here because they were / possibly still
    // are in some places being accessed by forms later in the flow
    // ie CRM_Contact_Import_Form_MapField, CRM_Contact_Import_Form_Preview
    // or CRM_Contact_Import_Form_Summary using `$this->get()
    // which was the old way of saving values submitted on this form such that
    // the other forms could access them. Now they should use
    // `getSubmittedValue` or simply not get them if the only
    // reason is to pass to the Parser which can itself
    // call 'getSubmittedValue'
    // Once the mentioned forms no longer call $this->get() all this 'setting'
    // is obsolete.
    $store->set('rowCount', $this->_rowCount);
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('columnCount', $this->_activeFieldCount);

    $store->set('totalRowCount', $this->_totalCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);
    $store->set('unMatchCount', $this->_unMatchCount);

    switch ($this->_contactType) {
      case 'Individual':
        $store->set('contactType', CRM_Import_Parser::CONTACT_INDIVIDUAL);
        break;

      case 'Household':
        $store->set('contactType', CRM_Import_Parser::CONTACT_HOUSEHOLD);
        break;

      case 'Organization':
        $store->set('contactType', CRM_Import_Parser::CONTACT_ORGANIZATION);
    }

    if (isset($this->_rows) && !empty($this->_rows)) {
      $store->set('dataValues', $this->_rows);
    }

    if ($this->_unMatchCount) {
      $store->set('mismatchFileName', $this->_misMatchFilemName);
    }

    if ($mode == self::MODE_IMPORT) {
      $store->set('duplicateRowCount', $this->_duplicateCount);
      $store->set('unparsedAddressCount', $this->_unparsedAddressCount);
      if ($this->_duplicateCount) {
        $store->set('duplicatesFileName', $this->_duplicateFileName);
      }
    }
  }

  /**
   * Export data to a CSV file.
   *
   * @param string $fileName
   * @param array $header
   * @param array $data
   */
  public static function exportCSV($fileName, $header, $data) {

    if (file_exists($fileName) && !is_writable($fileName)) {
      CRM_Core_Error::movedSiteError($fileName);
    }
    //hack to remove '_status', '_statusMsg' and '_id' from error file
    $errorValues = [];
    $dbRecordStatus = ['IMPORTED', 'ERROR', 'DUPLICATE', 'INVALID', 'NEW'];
    foreach ($data as $rowCount => $rowValues) {
      $count = 0;
      foreach ($rowValues as $key => $val) {
        if (in_array($val, $dbRecordStatus) && $count == (count($rowValues) - 3)) {
          break;
        }
        $errorValues[$rowCount][$key] = $val;
        $count++;
      }
    }
    $data = $errorValues;

    $output = [];
    $fd = fopen($fileName, 'w');

    foreach ($header as $key => $value) {
      $header[$key] = "\"$value\"";
    }
    $config = CRM_Core_Config::singleton();
    $output[] = implode($config->fieldSeparator, $header);

    foreach ($data as $datum) {
      foreach ($datum as $key => $value) {
        $datum[$key] = "\"$value\"";
      }
      $output[] = implode($config->fieldSeparator, $datum);
    }
    fwrite($fd, implode("\n", $output));
    fclose($fd);
  }

  /**
   * Set the import status for the given record.
   *
   * If this is a sql import then the sql table will be used and the update
   * will not happen as the relevant fields don't exist in the table - hence
   * the checks that statusField & primary key are set.
   *
   * @param int $id
   * @param string $status
   * @param string $message
   */
  public function setImportStatus(int $id, string $status, string $message): void {
    if ($this->_statusFieldName && $this->_primaryKeyName) {
      CRM_Core_DAO::executeQuery("
        UPDATE $this->_tableName
        SET $this->_statusFieldName = %1,
          {$this->_statusFieldName}Msg = %2
        WHERE  $this->_primaryKeyName = %3
      ", [
        1 => [$status, 'String'],
        2 => [$message, 'String'],
        3 => [$id, 'Integer'],
      ]);
    }
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
    //              Phone
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

    // Cache the various object fields
    // @todo - remove this after confirming this is just a compilation of other-wise-cached fields.
    static $fields = [];

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

    // CRM-4575
    if (isset($values['email_greeting'])) {
      if (!empty($params['email_greeting_id'])) {
        $emailGreetingFilter = [
          'contact_type' => $params['contact_type'] ?? NULL,
          'greeting_type' => 'email_greeting',
        ];
        $emailGreetings = CRM_Core_PseudoConstant::greeting($emailGreetingFilter);
        $params['email_greeting'] = $emailGreetings[$params['email_greeting_id']];
      }
      else {
        $params['email_greeting'] = $values['email_greeting'];
      }

      return TRUE;
    }

    if (isset($values['postal_greeting'])) {
      if (!empty($params['postal_greeting_id'])) {
        $postalGreetingFilter = [
          'contact_type' => $params['contact_type'] ?? NULL,
          'greeting_type' => 'postal_greeting',
        ];
        $postalGreetings = CRM_Core_PseudoConstant::greeting($postalGreetingFilter);
        $params['postal_greeting'] = $postalGreetings[$params['postal_greeting_id']];
      }
      else {
        $params['postal_greeting'] = $values['postal_greeting'];
      }
      return TRUE;
    }

    if (isset($values['addressee'])) {
      $params['addressee'] = $values['addressee'];
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

    if (!empty($values['preferred_communication_method'])) {
      $comm = [];
      $pcm = array_change_key_case(array_flip(CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method')), CASE_LOWER);

      $preffComm = explode(',', $values['preferred_communication_method']);
      foreach ($preffComm as $v) {
        $v = strtolower(trim($v));
        if (array_key_exists($v, $pcm)) {
          $comm[$pcm[$v]] = 1;
        }
      }

      $params['preferred_communication_method'] = $comm;
      return TRUE;
    }

    // format the website params.
    if (!empty($values['url'])) {
      static $websiteFields;
      if (!is_array($websiteFields)) {
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
    $customFields = CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $values),
      FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE
    );

    foreach ($values as $key => $value) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        // check if it's a valid custom field id

        if (!array_key_exists($customFieldID, $customFields)) {
          return civicrm_api3_create_error('Invalid custom field ID');
        }
        else {
          $params[$key] = $value;
        }
      }
    }
    return TRUE;
  }

  /**
   * Format location block ready for importing.
   *
   * There is some test coverage for this in CRM_Contact_Import_Parser_ContactTest
   * e.g. testImportPrimaryAddress.
   *
   * @param array $values
   * @param array $params
   *
   * @return bool
   */
  protected function formatLocationBlock(&$values, &$params) {
    $blockTypes = [
      'phone' => 'Phone',
      'email' => 'Email',
      'im' => 'IM',
      'openid' => 'OpenID',
      'phone_ext' => 'Phone',
    ];
    foreach ($blockTypes as $blockFieldName => $block) {
      if (!array_key_exists($blockFieldName, $values)) {
        continue;
      }
      $blockIndex = $values['location_type_id'] . (!empty($values['phone_type_id']) ? '_' . $values['phone_type_id'] : '');

      // block present in value array.
      if (!array_key_exists($blockFieldName, $params) || !is_array($params[$blockFieldName])) {
        $params[$blockFieldName] = [];
      }

      $fields[$block] = $this->getMetadataForEntity($block);

      // copy value to dao field name.
      if ($blockFieldName == 'im') {
        $values['name'] = $values[$blockFieldName];
      }

      _civicrm_api3_store_values($fields[$block], $values,
        $params[$blockFieldName][$blockIndex]
      );

      $this->fillPrimary($params[$blockFieldName][$blockIndex], $values, $block, CRM_Utils_Array::value('id', $params));

      if (empty($params['id']) && (count($params[$blockFieldName]) == 1)) {
        $params[$blockFieldName][$blockIndex]['is_primary'] = TRUE;
      }

      // we only process single block at a time.
      return TRUE;
    }

    // handle address fields.
    if (!array_key_exists('address', $params) || !is_array($params['address'])) {
      $params['address'] = [];
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

    $fields['Address'] = $this->getMetadataForEntity('Address');
    // @todo this is kinda replicated below....
    _civicrm_api3_store_values($fields['Address'], $values, $params['address'][$values['location_type_id']]);

    $addressFields = [
      'county',
      'country',
      'state_province',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'StateProvince.name',
    ];
    foreach (array_keys($customFields) as $customFieldID) {
      $addressFields[] = 'custom_' . $customFieldID;
    }

    foreach ($addressFields as $field) {
      if (array_key_exists($field, $values)) {
        if (!array_key_exists('address', $params)) {
          $params['address'] = [];
        }
        $params['address'][$values['location_type_id']][$field] = $values[$field];
      }
    }

    $this->fillPrimary($params['address'][$values['location_type_id']], $values, 'address', CRM_Utils_Array::value('id', $params));
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
   * @throws \CiviCRM_API3_Exception
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
   * @param int $mappingID
   * @param int $columnNumber
   *
   * @return array
   * @throws \API_Exception
   */
  public function getMappingFieldFromMapperInput(array $fieldMapping, int $mappingID, int $columnNumber): array {
    $isRelationshipField = preg_match('/\d*_a_b|b_a$/', $fieldMapping[0]);
    $fieldName = $isRelationshipField ? $fieldMapping[1] : $fieldMapping[0];
    $locationTypeID = NULL;
    $possibleLocationField = $isRelationshipField ? 2 : 1;
    if ($fieldName !== 'url' && is_numeric($fieldMapping[$possibleLocationField] ?? NULL)) {
      $locationTypeID = $fieldMapping[$possibleLocationField];
    }
    return [
      'name' => $fieldName,
      'mapping_id' => $mappingID,
      'relationship_type_id' => $isRelationshipField ? substr($fieldMapping[0], 0, -4) : NULL,
      'relationship_direction' => $isRelationshipField ? substr($fieldMapping[0], -3) : NULL,
      'column_number' => $columnNumber,
      'contact_type' => $this->getContactType(),
      'website_type_id' => $fieldName !== 'url' ? NULL : ($isRelationshipField ? $fieldMapping[2] : $fieldMapping[1]),
      'phone_type_id' => $fieldName !== 'phone' ? NULL : ($isRelationshipField ? $fieldMapping[3] : $fieldMapping[2]),
      'im_provider_id' => $fieldName !== 'im' ? NULL : ($isRelationshipField ? $fieldMapping[3] : $fieldMapping[2]),
      'location_type_id' => $locationTypeID,
    ];
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
    $this->setFieldMetadata();
    $title = [];
    if ($mappedField['relationship_type_id']) {
      $title[] = $this->getRelationshipLabel($mappedField['relationship_type_id'], $mappedField['relationship_direction']);
    }
    $title[] = $this->getImportableFieldsMetadata()[$mappedField['name']]['title'];
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
      $title[] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_IM', 'provider_id', $mappedField['provider_id']);
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
   * @throws \API_Exception
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
   * @throws \API_Exception
   */
  public function getMappedRow(array $values): array {
    $this->setActiveFieldValues($values);
    $params = $this->getActiveFieldParams();
    $params['contact_type'] = $this->getContactType();
    return $params;
  }

  /**
   * Is the job complete.
   *
   * This function transitionally accesses the table from the userJob
   * directly - but the function should be moved to the dataSource class.
   *
   * @throws \API_Exception
   */
  public function isComplete() {
    $tableName = $this->getUserJob()['metadata']['DataSource']['table_name'];
    return (bool) CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM $tableName WHERE _status = 'NEW' LIMIT 1");
  }

  /**
   * Validate the import values.
   *
   * The values array represents a row in the datasource.
   *
   * @param array $values
   */
  public function validateValues(array $values): void {
    $errorMessage = NULL;
    $errorRequired = FALSE;
    $params = $this->getMappedRow($values);
    switch ($this->_contactType) {
      case 'Individual':
        $missingNames = [];
        if ($this->_firstNameIndex < 0 || empty($values[$this->_firstNameIndex])) {
          $errorRequired = TRUE;
          $missingNames[] = ts('First Name');
        }
        if ($this->_lastNameIndex < 0 || empty($values[$this->_lastNameIndex])) {
          $errorRequired = TRUE;
          $missingNames[] = ts('Last Name');
        }
        if ($errorRequired) {
          $and = ' ' . ts('and') . ' ';
          $errorMessage = ts('Missing required fields:') . ' ' . implode($and, $missingNames);
        }
        break;

      case 'Household':
        if ($this->_householdNameIndex < 0 || empty($values[$this->_householdNameIndex])) {
          $errorRequired = TRUE;
          $errorMessage = ts('Missing required fields:') . ' ' . ts('Household Name');
        }
        break;

      case 'Organization':
        if ($this->_organizationNameIndex < 0 || empty($values[$this->_organizationNameIndex])) {
          $errorRequired = TRUE;
          $errorMessage = ts('Missing required fields:') . ' ' . ts('Organization Name');
        }
        break;
    }

    if ($this->_emailIndex >= 0) {
      /* If we don't have the required fields, bail */

      if ($this->_contactType === 'Individual' && !$this->_updateWithId) {
        if ($errorRequired && empty($values[$this->_emailIndex])) {
          if ($errorMessage) {
            $errorMessage .= ' ' . ts('OR') . ' ' . ts('Email Address');
          }
          else {
            $errorMessage = ts('Missing required field:') . ' ' . ts('Email Address');
          }
          throw new CRM_Core_Exception($errorMessage);
        }
      }

      $email = $values[$this->_emailIndex] ?? NULL;
      if ($email) {
        if (!CRM_Utils_Rule::email($email)) {
          throw new CRM_Core_Exception($errorMessage);
        }
      }
    }
    elseif ($errorRequired && !$this->_updateWithId) {
      if ($errorMessage) {
        $errorMessage .= ' ' . ts('OR') . ' ' . ts('Email Address');
      }
      else {
        $errorMessage = ts('Missing required field:') . ' ' . ts('Email Address');
      }
      throw new CRM_Core_Exception($errorMessage);
    }

    //check for duplicate external Identifier
    $externalID = $values[$this->_externalIdentifierIndex] ?? NULL;
    if ($externalID) {
      /* If it's a dupe,external Identifier  */

      if ($externalDupe = CRM_Utils_Array::value($externalID, $this->_allExternalIdentifiers)) {
        $errorMessage = ts('External ID conflicts with record %1', [1 => $externalDupe]);
        throw new CRM_Core_Exception($errorMessage);
      }
      //otherwise, count it and move on
      $this->_allExternalIdentifiers[$externalID] = $this->_lineCount;
    }

    //date-format part ends

    $errorMessage = NULL;

    //CRM-5125
    //add custom fields for contact sub type
    $csType = NULL;
    if (!empty($this->_contactSubType)) {
      $csType = $this->_contactSubType;
    }

    //checking error in custom data
    $this->isErrorInCustomData($params, $errorMessage, $csType, $this->_relationships);

    //checking error in core data
    $this->isErrorInCoreData($params, $errorMessage);
    if ($errorMessage) {
      $tempMsg = "Invalid value for field(s) : $errorMessage";
      throw new CRM_Core_Exception($tempMsg);
    }
  }

}
