<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * class to parse contact csv files
 */
class CRM_Contact_Import_Parser_Contact extends CRM_Contact_Import_Parser {
  protected $_mapperKeys;
  protected $_mapperLocType;
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

  protected $_allEmails;

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
   * @array
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
   * @array
   */
  protected $_newRelatedContacts;

  /**
   * Array of all the contacts whose street addresses are not parsed.
   * of this import process
   * @var array
   */
  protected $_unparsedStreetAddressContacts;

  /**
   * Class constructor.
   *
   * @param array $mapperKeys
   * @param int $mapperLocType
   * @param int $mapperPhoneType
   * @param int $mapperImProvider
   * @param int $mapperRelated
   * @param int $mapperRelatedContactType
   * @param array $mapperRelatedContactDetails
   * @param int $mapperRelatedContactLocType
   * @param int $mapperRelatedContactPhoneType
   * @param int $mapperRelatedContactImProvider
   * @param int $mapperWebsiteType
   * @param int $mapperRelatedContactWebsiteType
   */
  public function __construct(
    &$mapperKeys, $mapperLocType = NULL, $mapperPhoneType = NULL, $mapperImProvider = NULL, $mapperRelated = NULL, $mapperRelatedContactType = NULL, $mapperRelatedContactDetails = NULL, $mapperRelatedContactLocType = NULL, $mapperRelatedContactPhoneType = NULL, $mapperRelatedContactImProvider = NULL,
    $mapperWebsiteType = NULL, $mapperRelatedContactWebsiteType = NULL
  ) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
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
    $contactFields = CRM_Contact_BAO_Contact::importableFields($this->_contactType);
    // exclude the address options disabled in the Address Settings
    $fields = CRM_Core_BAO_Address::validateAddressOptions($contactFields);

    //CRM-5125
    //supporting import for contact subtypes
    $csType = NULL;
    if (!empty($this->_contactSubType)) {
      //custom fields for sub type
      $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($this->_contactSubType);

      if (!empty($subTypeFields)) {
        foreach ($subTypeFields as $customSubTypeField => $details) {
          $fields[$customSubTypeField] = $details;
        }
      }
    }

    //Relationship importables
    $this->_relationships = $relations
      = CRM_Contact_BAO_Relationship::getContactRelationshipType(
        NULL, NULL, NULL, $this->_contactType,
        FALSE, 'label', TRUE, $this->_contactSubType
      );
    asort($relations);

    foreach ($relations as $key => $var) {
      list($type) = explode('_', $key);
      $relationshipType[$key]['title'] = $var;
      $relationshipType[$key]['headerPattern'] = '/' . preg_quote($var, '/') . '/';
      $relationshipType[$key]['import'] = TRUE;
      $relationshipType[$key]['relationship_type_id'] = $type;
      $relationshipType[$key]['related'] = TRUE;
    }

    if (!empty($relationshipType)) {
      $fields = array_merge($fields, array(
        'related' => array(
          'title' => ts('- related contact info -'),
        ),
      ), $relationshipType);
    }

    foreach ($fields as $name => $field) {
      $this->addField($name, $field['title'], CRM_Utils_Array::value('type', $field), CRM_Utils_Array::value('headerPattern', $field), CRM_Utils_Array::value('dataPattern', $field), CRM_Utils_Array::value('hasLocationType', $field));
    }

    $this->_newContacts = array();

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
        $this->_allEmails = array();
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
        $this->_allExternalIdentifiers = array();
      }
      $index++;
    }

    $this->_updateWithId = FALSE;
    if (in_array('id', $this->_mapperKeys) || ($this->_externalIdentifierIndex >= 0 && in_array($this->_onDuplicate, array(
          CRM_Import_Parser::DUPLICATE_UPDATE,
          CRM_Import_Parser::DUPLICATE_FILL,
        )))
    ) {
      $this->_updateWithId = TRUE;
    }

    $this->_parseStreetAddress = CRM_Utils_Array::value('street_address_parsing', CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'address_options'), FALSE);
  }

  /**
   * Handle the values in mapField mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   */
  public function mapField(&$values) {
    return CRM_Import_Parser::VALID;
  }

  /**
   * Handle the values in preview mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   */
  public function preview(&$values) {
    return $this->summary($values);
  }

  /**
   * Handle the values in summary mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   */
  public function summary(&$values) {
    $erroneousField = NULL;
    $response = $this->setActiveFieldValues($values, $erroneousField);

    $errorMessage = NULL;
    $errorRequired = FALSE;
    switch ($this->_contactType) {
      case 'Individual':
        $missingNames = array();
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

    $statusFieldName = $this->_statusFieldName;

    if ($this->_emailIndex >= 0) {
      /* If we don't have the required fields, bail */

      if ($this->_contactType == 'Individual' && !$this->_updateWithId) {
        if ($errorRequired && empty($values[$this->_emailIndex])) {
          if ($errorMessage) {
            $errorMessage .= ' ' . ts('OR') . ' ' . ts('Email Address');
          }
          else {
            $errorMessage = ts('Missing required field:') . ' ' . ts('Email Address');
          }
          array_unshift($values, $errorMessage);
          $importRecordParams = array(
            $statusFieldName => 'ERROR',
            "${statusFieldName}Msg" => $errorMessage,
          );
          $this->updateImportRecord($values[count($values) - 1], $importRecordParams);

          return CRM_Import_Parser::ERROR;
        }
      }

      $email = CRM_Utils_Array::value($this->_emailIndex, $values);
      if ($email) {
        /* If the email address isn't valid, bail */

        if (!CRM_Utils_Rule::email($email)) {
          $errorMessage = ts('Invalid Email address');
          array_unshift($values, $errorMessage);
          $importRecordParams = array(
            $statusFieldName => 'ERROR',
            "${statusFieldName}Msg" => $errorMessage,
          );
          $this->updateImportRecord($values[count($values) - 1], $importRecordParams);

          return CRM_Import_Parser::ERROR;
        }

        /* otherwise, count it and move on */
        $this->_allEmails[$email] = $this->_lineCount;
      }
    }
    elseif ($errorRequired && !$this->_updateWithId) {
      if ($errorMessage) {
        $errorMessage .= ' ' . ts('OR') . ' ' . ts('Email Address');
      }
      else {
        $errorMessage = ts('Missing required field:') . ' ' . ts('Email Address');
      }
      array_unshift($values, $errorMessage);
      $importRecordParams = array(
        $statusFieldName => 'ERROR',
        "${statusFieldName}Msg" => $errorMessage,
      );
      $this->updateImportRecord($values[count($values) - 1], $importRecordParams);

      return CRM_Import_Parser::ERROR;
    }

    //check for duplicate external Identifier
    $externalID = CRM_Utils_Array::value($this->_externalIdentifierIndex, $values);
    if ($externalID) {
      /* If it's a dupe,external Identifier  */

      if ($externalDupe = CRM_Utils_Array::value($externalID, $this->_allExternalIdentifiers)) {
        $errorMessage = ts('External ID conflicts with record %1', array(1 => $externalDupe));
        array_unshift($values, $errorMessage);
        $importRecordParams = array(
          $statusFieldName => 'ERROR',
          "${statusFieldName}Msg" => $errorMessage,
        );
        $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
        return CRM_Import_Parser::ERROR;
      }
      //otherwise, count it and move on
      $this->_allExternalIdentifiers[$externalID] = $this->_lineCount;
    }

    //Checking error in custom data
    $params = &$this->getActiveFieldParams();
    $params['contact_type'] = $this->_contactType;
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
      // put the error message in the import record in the DB
      $importRecordParams = array(
        $statusFieldName => 'ERROR',
        "${statusFieldName}Msg" => $tempMsg,
      );
      $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
      array_unshift($values, $tempMsg);
      $errorMessage = NULL;
      return CRM_Import_Parser::ERROR;
    }

    //if user correcting errors by walking back
    //need to reset status ERROR msg to null
    //now currently we are having valid data.
    $importRecordParams = array(
      $statusFieldName => 'NEW',
    );
    $this->updateImportRecord($values[count($values) - 1], $importRecordParams);

    return CRM_Import_Parser::VALID;
  }

  /**
   * Handle the values in import mode.
   *
   * @param int $onDuplicate
   *   The code for what action to take on duplicates.
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @param bool $doGeocodeAddress
   *
   * @return bool
   *   the result of this processing
   */
  public function import($onDuplicate, &$values, $doGeocodeAddress = FALSE) {
    $config = CRM_Core_Config::singleton();
    $this->_unparsedStreetAddressContacts = array();
    if (!$doGeocodeAddress) {
      // CRM-5854, reset the geocode method to null to prevent geocoding
      $config->geocodeMethod = NULL;
    }

    // first make sure this is a valid line
    //$this->_updateWithId = false;
    $response = $this->summary($values);
    $statusFieldName = $this->_statusFieldName;

    if ($response != CRM_Import_Parser::VALID) {
      $importRecordParams = array(
        $statusFieldName => 'INVALID',
        "${statusFieldName}Msg" => "Invalid (Error Code: $response)",
      );
      $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
      return $response;
    }

    $params = &$this->getActiveFieldParams();
    $formatted = array(
      'contact_type' => $this->_contactType,
    );

    static $contactFields = NULL;
    if ($contactFields == NULL) {
      $contactFields = CRM_Contact_DAO_Contact::import();
    }

    //check if external identifier exists in database
    if (!empty($params['external_identifier']) && (!empty($params['id']) || in_array($onDuplicate, array(
          CRM_Import_Parser::DUPLICATE_SKIP,
          CRM_Import_Parser::DUPLICATE_NOCHECK,
        )))
    ) {

      if ($internalCid = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params['external_identifier'], 'id', 'external_identifier')) {
        if ($internalCid != CRM_Utils_Array::value('id', $params)) {
          $errorMessage = ts('External ID already exists in Database.');
          array_unshift($values, $errorMessage);
          $importRecordParams = array(
            $statusFieldName => 'ERROR',
            "${statusFieldName}Msg" => $errorMessage,
          );
          $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
          return CRM_Import_Parser::DUPLICATE;
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
      $possibleMatches = $this->getPossibleContactMatches($params);
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
      // the call to the deprecated function seems to add no value other that to do an additional
      // check for the contact_id & type.
      $error = _civicrm_api3_deprecated_duplicate_formatted_contact($formatted);
      if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
        $matchedIDs = explode(',', $error['error_message']['params'][0]);
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
      elseif (is_a($error, 'CRM_Core_Error')) {
        $newContact = $error;
        $relationship = TRUE;
      }
    }

    //fixed CRM-4148
    //now we create new contact in update/fill mode also.
    $contactID = NULL;
    if ($createNewContact || ($this->_retCode != CRM_Import_Parser::NO_MATCH && $this->_updateWithId)) {

      //CRM-4430, don't carry if not submitted.
      foreach (array('prefix_id', 'suffix_id', 'gender_id') as $name) {
        if (!empty($formatted[$name])) {
          $options = CRM_Contact_BAO_Contact::buildOptions($name, 'get');
          if (!isset($options[$formatted[$name]])) {
            $formatted[$name] = CRM_Utils_Array::key((string) $formatted[$name], $options);
          }
        }
      }
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
    elseif (isset($newContact) && CRM_Core_Error::isAPIError($newContact, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
      // if duplicate, no need of further processing
      if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
        $errorMessage = "Skipping duplicate record";
        array_unshift($values, $errorMessage);
        $importRecordParams = array(
          $statusFieldName => 'DUPLICATE',
          "${statusFieldName}Msg" => $errorMessage,
        );
        $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
        return CRM_Import_Parser::DUPLICATE;
      }

      $relationship = TRUE;
      # see CRM-10433 - might return comma separate list of all dupes
      $dupeContactIDs = explode(',', $newContact['error_message']['params'][0]);
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

      $hookParams = array(
        'contactID' => $contactID,
        'importID' => $currentImportID,
        'importTempTable' => $this->_tableName,
        'fieldHeaders' => $this->_mapperKeys,
        'fields' => $this->_activeFields,
      );

      CRM_Utils_Hook::import('Contact', 'process', $this, $hookParams);
    }

    if ($relationship) {
      $primaryContactId = NULL;
      if (CRM_Core_Error::isAPIError($newContact, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
        if (CRM_Utils_Rule::integer($newContact['error_message']['params'][0])) {
          $primaryContactId = $newContact['error_message']['params'][0];
        }
      }
      else {
        $primaryContactId = $newContact->id;
      }

      if ((CRM_Core_Error::isAPIError($newContact, CRM_Core_ERROR::DUPLICATE_CONTACT) || is_a($newContact, 'CRM_Contact_BAO_Contact')) && $primaryContactId) {

        //relationship contact insert
        foreach ($params as $key => $field) {
          list($id, $first, $second) = CRM_Utils_System::explode('_', $key, 3);
          if (!($first == 'a' && $second == 'b') && !($first == 'b' && $second == 'a')) {
            continue;
          }

          $relationType = new CRM_Contact_DAO_RelationshipType();
          $relationType->id = $id;
          $relationType->find(TRUE);
          $direction = "contact_sub_type_$second";

          $formatting = array(
            'contact_type' => $params[$key]['contact_type'],
          );

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
          $relationType->free();

          $contactFields = NULL;
          $contactFields = CRM_Contact_DAO_Contact::import();

          //Relation on the basis of External Identifier.
          if (empty($params[$key]['id']) && !empty($params[$key]['external_identifier'])) {
            $params[$key]['id'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params[$key]['external_identifier'], 'id', 'external_identifier');
          }
          // check for valid related contact id in update/fill mode, CRM-4424
          if (in_array($onDuplicate, array(
              CRM_Import_Parser::DUPLICATE_UPDATE,
              CRM_Import_Parser::DUPLICATE_FILL,
            )) && !empty($params[$key]['id'])
          ) {
            $relatedContactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params[$key]['id'], 'contact_type');
            if (!$relatedContactType) {
              $errorMessage = ts("No contact found for this related contact ID: %1", array(1 => $params[$key]['id']));
              array_unshift($values, $errorMessage);
              return CRM_Import_Parser::NO_MATCH;
            }
            else {
              //validation of related contact subtype for update mode
              //CRM-5125
              $relatedCsType = NULL;
              if (!empty($formatting['contact_sub_type'])) {
                $relatedCsType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params[$key]['id'], 'contact_sub_type');
              }

              if (!empty($relatedCsType) && (!CRM_Contact_BAO_ContactType::isAllowEdit($params[$key]['id'], $relatedCsType) &&
                  $relatedCsType != CRM_Utils_Array::value('contact_sub_type', $formatting))
              ) {
                $errorMessage = ts("Mismatched or Invalid contact subtype found for this related contact.") . ' ' . ts("ID: %1", array(1 => $params[$key]['id']));
                array_unshift($values, $errorMessage);
                return CRM_Import_Parser::NO_MATCH;
              }
              else {
                // get related contact id to format data in update/fill mode,
                //if external identifier is present, CRM-4423
                $formatting['id'] = $params[$key]['id'];
              }
            }
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
            $contact = array(
              'contact_id' => $params[$key]['id'],
            );
            $defaults = array();
            $relatedNewContact = CRM_Contact_BAO_Contact::retrieve($contact, $defaults);
          }
          else {
            $relatedNewContact = $this->createContact($formatting, $contactFields, $onDuplicate, NULL, FALSE);
          }

          if (is_object($relatedNewContact) || ($relatedNewContact instanceof CRM_Contact_BAO_Contact)) {
            $relatedNewContact = clone($relatedNewContact);
          }

          $matchedIDs = array();
          // To update/fill contact, get the matching contact Ids if duplicate contact found
          // otherwise get contact Id from object of related contact
          if (is_array($relatedNewContact) && civicrm_error($relatedNewContact)) {
            if (CRM_Core_Error::isAPIError($relatedNewContact, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
              $matchedIDs = explode(',', $relatedNewContact['error_message']['params'][0]);
            }
            else {
              $errorMessage = $relatedNewContact['error_message'];
              array_unshift($values, $errorMessage);
              $importRecordParams = array(
                $statusFieldName => 'ERROR',
                "${statusFieldName}Msg" => $errorMessage,
              );
              $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
              return CRM_Import_Parser::ERROR;
            }
          }
          else {
            $matchedIDs[] = $relatedNewContact->id;
          }
          // update/fill related contact after getting matching Contact Ids, CRM-4424
          if (in_array($onDuplicate, array(
            CRM_Import_Parser::DUPLICATE_UPDATE,
            CRM_Import_Parser::DUPLICATE_FILL,
          ))) {
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
          static $relativeContact = array();
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
              $relationParams = array();
              $relationParams = array(
                'relationship_type_id' => $key,
                'contact_check' => array(
                  $relContactId => 1,
                ),
                'is_active' => 1,
                'skipRecentView' => TRUE,
              );

              // we only handle related contact success, we ignore failures for now
              // at some point wold be nice to have related counts as separate
              $relationIds = array(
                'contact' => $primaryContactId,
              );

              list($valid, $invalid, $duplicate, $saved, $relationshipIds) = CRM_Contact_BAO_Relationship::legacyCreateMultiple($relationParams, $relationIds);

              if ($valid || $duplicate) {
                $relationIds['contactTarget'] = $relContactId;
                $action = ($duplicate) ? CRM_Core_Action::UPDATE : CRM_Core_Action::ADD;
                CRM_Contact_BAO_Relationship::relatedMemberships($primaryContactId, $relationParams, $relationIds, $action);
              }

              //handle current employer, CRM-3532
              if ($valid) {
                $allRelationships = CRM_Core_PseudoConstant::relationshipType('name');
                $relationshipTypeId = str_replace(array(
                  '_a_b',
                  '_b_a',
                ), array(
                  '',
                  '',
                ), $key);
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
        $urls = array();
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
          $importRecordParams = array(
            $statusFieldName => 'ERROR',
            "${statusFieldName}Msg" => $errorMessage,
          );

          //combine error msg to avoid mismatch between error file columns.
          $errorMessage .= "\n" . $url_string;
          array_unshift($values, $errorMessage);
          $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
          return CRM_Import_Parser::ERROR;
        }

        // Params only had one id, so shift it out
        $contactId = array_shift($cids);
        $cid = NULL;

        $vals = array('contact_id' => $contactId);

        if ($onDuplicate == CRM_Import_Parser::DUPLICATE_REPLACE) {
          civicrm_api('contact', 'delete', $vals);
          $cid = CRM_Contact_BAO_Contact::createProfileContact($formatted, $contactFields, $contactId, NULL, NULL, $formatted['contact_type']);
        }
        elseif ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
          $newContact = $this->createContact($formatted, $contactFields, $onDuplicate, $contactId);
        }
        elseif ($onDuplicate == CRM_Import_Parser::DUPLICATE_FILL) {
          $newContact = $this->createContact($formatted, $contactFields, $onDuplicate, $contactId);
        }
        // else skip does nothing and just returns an error code.
        if ($cid) {
          $contact = array(
            'contact_id' => $cid,
          );
          $defaults = array();
          $newContact = CRM_Contact_BAO_Contact::retrieve($contact, $defaults);
        }

        if (civicrm_error($newContact)) {
          if (empty($newContact['error_message']['params'])) {
            // different kind of error other than DUPLICATE
            $errorMessage = $newContact['error_message'];
            array_unshift($values, $errorMessage);
            $importRecordParams = array(
              $statusFieldName => 'ERROR',
              "${statusFieldName}Msg" => $errorMessage,
            );
            $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
            return CRM_Import_Parser::ERROR;
          }

          $contactID = $newContact['error_message']['params'][0];
          if (!in_array($contactID, $this->_newContacts)) {
            $this->_newContacts[] = $contactID;
          }
        }
        //CRM-262 No Duplicate Checking
        if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {
          array_unshift($values, $url_string);
          $importRecordParams = array(
            $statusFieldName => 'DUPLICATE',
            "${statusFieldName}Msg" => "Skipping duplicate record",
          );
          $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
          return CRM_Import_Parser::DUPLICATE;
        }

        $importRecordParams = array(
          $statusFieldName => 'IMPORTED',
        );
        $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
        //return warning if street address is not parsed, CRM-5886
        return $this->processMessage($values, $statusFieldName, CRM_Import_Parser::VALID);
      }
      else {
        // Not a dupe, so we had an error
        $errorMessage = $newContact['error_message'];
        array_unshift($values, $errorMessage);
        $importRecordParams = array(
          $statusFieldName => 'ERROR',
          "${statusFieldName}Msg" => $errorMessage,
        );
        $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
        return CRM_Import_Parser::ERROR;
      }
    }
    // sleep(3);
    return $this->processMessage($values, $statusFieldName, CRM_Import_Parser::VALID);
  }

  /**
   * Get the array of successfully imported contact id's
   *
   * @return array
   */
  public function &getImportedContacts() {
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
   * The initializer code, called before the processing.
   */
  public function fini() {
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
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get("dateTypes");

    if (!empty($params['contact_sub_type'])) {
      $csType = CRM_Utils_Array::value('contact_sub_type', $params);
    }

    if (empty($params['contact_type'])) {
      $params['contact_type'] = 'Individual';
    }

    // get array of subtypes - CRM-18708
    if (in_array($csType, array('Individual', 'Organization', 'Household'))) {
      $csType = self::getSubtypes($params['contact_type']);
    }

    if (is_array($csType)) {
      // fetch custom fields for every subtype and add it to $customFields array
      // CRM-18708
      $customFields = array();
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
          self::addToErrorMsg(ts('field ID'), $errorMessage);
        }
        // validate null values for required custom fields of type boolean
        if (!empty($customFields[$customFieldID]['is_required']) && (empty($params['custom_' . $customFieldID]) && !is_numeric($params['custom_' . $customFieldID])) && $customFields[$customFieldID]['data_type'] == 'Boolean') {
          self::addToErrorMsg($customFields[$customFieldID]['label'] . '::' . $customFields[$customFieldID]['groupTitle'], $errorMessage);
        }

        //For address custom fields, we do get actual custom field value as an inner array of
        //values so need to modify
        if (array_key_exists($customFieldID, $addressCustomFields)) {
          $value = $value[0][$key];
        }
        /* validate the data against the CF type */

        if ($value) {
          if ($customFields[$customFieldID]['data_type'] == 'Date') {
            if (array_key_exists($customFieldID, $addressCustomFields) && CRM_Utils_Date::convertToDefaultDate($params[$key][0], $dateType, $key)) {
              $value = $params[$key][0][$key];
            }
            elseif (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              $value = $params[$key];
            }
            else {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }
          elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
            if (CRM_Utils_String::strtoboolstr($value) === FALSE) {
              self::addToErrorMsg($customFields[$customFieldID]['label'] . '::' . $customFields[$customFieldID]['groupTitle'], $errorMessage);
            }
          }
          // need not check for label filed import
          $htmlType = array(
            'CheckBox',
            'Multi-Select',
            'AdvMulti-Select',
            'Select',
            'Radio',
            'Multi-Select State/Province',
            'Multi-Select Country',
          );
          if (!in_array($customFields[$customFieldID]['html_type'], $htmlType) || $customFields[$customFieldID]['data_type'] == 'Boolean' || $customFields[$customFieldID]['data_type'] == 'ContactReference') {
            $valid = CRM_Core_BAO_CustomValue::typecheck($customFields[$customFieldID]['data_type'], $value);
            if (!$valid) {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }

          // check for values for custom fields for checkboxes and multiselect
          if ($customFields[$customFieldID]['html_type'] == 'CheckBox' || $customFields[$customFieldID]['html_type'] == 'AdvMulti-Select' || $customFields[$customFieldID]['html_type'] == 'Multi-Select') {
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
                self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
              }
            }
          }
          elseif ($customFields[$customFieldID]['html_type'] == 'Select' || ($customFields[$customFieldID]['html_type'] == 'Radio' && $customFields[$customFieldID]['data_type'] != 'Boolean')) {
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
            $flag = FALSE;
            foreach ($customOption as $v2) {
              if ((strtolower(trim($v2['label'])) == strtolower(trim($value))) || (strtolower(trim($v2['value'])) == strtolower(trim($value)))) {
                $flag = TRUE;
              }
            }
            if (!$flag) {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }
          elseif ($customFields[$customFieldID]['html_type'] == 'Multi-Select State/Province') {
            $mulValues = explode(',', $value);
            foreach ($mulValues as $stateValue) {
              if ($stateValue) {
                if (self::in_value(trim($stateValue), CRM_Core_PseudoConstant::stateProvinceAbbreviation()) || self::in_value(trim($stateValue), CRM_Core_PseudoConstant::stateProvince())) {
                  continue;
                }
                else {
                  self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
                }
              }
            }
          }
          elseif ($customFields[$customFieldID]['html_type'] == 'Multi-Select Country') {
            $mulValues = explode(',', $value);
            foreach ($mulValues as $countryValue) {
              if ($countryValue) {
                CRM_Core_PseudoConstant::populate($countryNames, 'CRM_Core_DAO_Country', TRUE, 'name', 'is_active');
                CRM_Core_PseudoConstant::populate($countryIsoCodes, 'CRM_Core_DAO_Country', TRUE, 'iso_code');
                $limitCodes = CRM_Core_BAO_Country::countryLimit();

                $error = TRUE;
                foreach (array(
                           $countryNames,
                           $countryIsoCodes,
                           $limitCodes,
                         ) as $values) {
                  if (in_array(trim($countryValue), $values)) {
                    $error = FALSE;
                    break;
                  }
                }

                if ($error) {
                  self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
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
          list($id, $first, $second) = CRM_Utils_System::explode('_', $relation, 3);
          $direction = "contact_sub_type_$second";
          $relationshipType = new CRM_Contact_BAO_RelationshipType();
          $relationshipType->id = $id;
          if ($relationshipType->find(TRUE)) {
            if (isset($relationshipType->$direction)) {
              $params[$key]['contact_sub_type'] = $relationshipType->$direction;
            }
          }
          $relationshipType->free();
        }

        self::isErrorInCustomData($params[$key], $errorMessage, $csType, $relationships);
      }
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
    foreach ($params as $key => $value) {
      if ($value) {
        $session = CRM_Core_Session::singleton();
        $dateType = $session->get("dateTypes");

        switch ($key) {
          case 'birth_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                self::addToErrorMsg(ts('Birth Date'), $errorMessage);
              }
            }
            else {
              self::addToErrorMsg(ts('Birth-Date'), $errorMessage);
            }
            break;

          case 'deceased_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                self::addToErrorMsg(ts('Deceased Date'), $errorMessage);
              }
            }
            else {
              self::addToErrorMsg(ts('Deceased Date'), $errorMessage);
            }
            break;

          case 'is_deceased':
            if (CRM_Utils_String::strtoboolstr($value) === FALSE) {
              self::addToErrorMsg(ts('Deceased'), $errorMessage);
            }
            break;

          case 'gender':
          case 'gender_id':
            if (!self::checkGender($value)) {
              self::addToErrorMsg(ts('Gender'), $errorMessage);
            }
            break;

          case 'preferred_communication_method':
            $preffComm = array();
            $preffComm = explode(',', $value);
            foreach ($preffComm as $v) {
              if (!self::in_value(trim($v), CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method'))) {
                self::addToErrorMsg(ts('Preferred Communication Method'), $errorMessage);
              }
            }
            break;

          case 'preferred_mail_format':
            if (!array_key_exists(strtolower($value), array_change_key_case(CRM_Core_SelectValues::pmf(), CASE_LOWER))) {
              self::addToErrorMsg(ts('Preferred Mail Format'), $errorMessage);
            }
            break;

          case 'individual_prefix':
          case 'prefix_id':
            if (!self::in_value($value, CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id'))) {
              self::addToErrorMsg(ts('Individual Prefix'), $errorMessage);
            }
            break;

          case 'individual_suffix':
          case 'suffix_id':
            if (!self::in_value($value, CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id'))) {
              self::addToErrorMsg(ts('Individual Suffix'), $errorMessage);
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
                    self::addToErrorMsg(ts('State/Province'), $errorMessage);
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
                  else {
                    if (self::in_value($stateValue['country'], $countryIsoCodes) || self::in_value($stateValue['country'], $countryNames)) {
                      self::addToErrorMsg(ts('Country input value is in table but not "available": "This Country is valid but is NOT in the list of Available Countries currently configured for your site. This can be viewed and modifed from Administer > Localization > Languages Currency Locations." '), $errorMessage);
                    }
                    else {
                      self::addToErrorMsg(ts('Country input value not in country table: "The Country value appears to be invalid. It does not match any value in CiviCRM table of countries."'), $errorMessage);
                    }
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
                    self::addToErrorMsg(ts('County input value not in county table: The County value appears to be invalid. It does not match any value in CiviCRM table of counties.'), $errorMessage);
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
                  else {
                    self::addToErrorMsg(ts('Geo code 1'), $errorMessage);
                  }
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
                  else {
                    self::addToErrorMsg(ts('Geo code 2'), $errorMessage);
                  }
                }
              }
            }
            break;

          //check for any error in email/postal greeting, addressee,
          //custom email/postal greeting, custom addressee, CRM-4575

          case 'email_greeting':
            $emailGreetingFilter = array(
              'contact_type' => $this->_contactType,
              'greeting_type' => 'email_greeting',
            );
            if (!self::in_value($value, CRM_Core_PseudoConstant::greeting($emailGreetingFilter))) {
              self::addToErrorMsg(ts('Email Greeting must be one of the configured format options. Check Administer >> System Settings >> Option Groups >> Email Greetings for valid values'), $errorMessage);
            }
            break;

          case 'postal_greeting':
            $postalGreetingFilter = array(
              'contact_type' => $this->_contactType,
              'greeting_type' => 'postal_greeting',
            );
            if (!self::in_value($value, CRM_Core_PseudoConstant::greeting($postalGreetingFilter))) {
              self::addToErrorMsg(ts('Postal Greeting must be one of the configured format options. Check Administer >> System Settings >> Option Groups >> Postal Greetings for valid values'), $errorMessage);
            }
            break;

          case 'addressee':
            $addresseeFilter = array(
              'contact_type' => $this->_contactType,
              'greeting_type' => 'addressee',
            );
            if (!self::in_value($value, CRM_Core_PseudoConstant::greeting($addresseeFilter))) {
              self::addToErrorMsg(ts('Addressee must be one of the configured format options. Check Administer >> System Settings >> Option Groups >> Addressee for valid values'), $errorMessage);
            }
            break;

          case 'email_greeting_custom':
            if (array_key_exists('email_greeting', $params)) {
              $emailGreetingLabel = key(CRM_Core_OptionGroup::values('email_greeting', TRUE, NULL, NULL, 'AND v.name = "Customized"'));
              if (CRM_Utils_Array::value('email_greeting', $params) != $emailGreetingLabel) {
                self::addToErrorMsg(ts('Email Greeting - Custom'), $errorMessage);
              }
            }
            break;

          case 'postal_greeting_custom':
            if (array_key_exists('postal_greeting', $params)) {
              $postalGreetingLabel = key(CRM_Core_OptionGroup::values('postal_greeting', TRUE, NULL, NULL, 'AND v.name = "Customized"'));
              if (CRM_Utils_Array::value('postal_greeting', $params) != $postalGreetingLabel) {
                self::addToErrorMsg(ts('Postal Greeting - Custom'), $errorMessage);
              }
            }
            break;

          case 'addressee_custom':
            if (array_key_exists('addressee', $params)) {
              $addresseeLabel = key(CRM_Core_OptionGroup::values('addressee', TRUE, NULL, NULL, 'AND v.name = "Customized"'));
              if (CRM_Utils_Array::value('addressee', $params) != $addresseeLabel) {
                self::addToErrorMsg(ts('Addressee - Custom'), $errorMessage);
              }
            }
            break;

          case 'url':
            if (is_array($value)) {
              foreach ($value as $values) {
                if (!empty($values['url']) && !CRM_Utils_Rule::url($values['url'])) {
                  self::addToErrorMsg(ts('Website'), $errorMessage);
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
              self::addToErrorMsg($key, $errorMessage);
            }
            break;

          case 'email':
            if (is_array($value)) {
              foreach ($value as $values) {
                if (!empty($values['email']) && !CRM_Utils_Rule::email($values['email'])) {
                  self::addToErrorMsg($key, $errorMessage);
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

    require_once 'CRM/Utils/DeprecatedUtils.php';
    //@todo direct call to API function not supported.
    // setting required check to false, CRM-2839
    // plus we do our own required check in import
    $error = _civicrm_api3_deprecated_contact_check_params($formatted, $dupeCheck, TRUE, FALSE, $dedupeRuleGroupID);

    if ((is_null($error)) && (civicrm_error(_civicrm_api3_deprecated_validate_formatted_contact($formatted)))) {
      $error = _civicrm_api3_deprecated_validate_formatted_contact($formatted);
    }

    $newContact = $error;

    if (is_null($error)) {
      if ($contactId) {
        $this->formatParams($formatted, $onDuplicate, (int) $contactId);
      }

      // pass doNotResetCache flag since resetting and rebuilding cache could be expensive.
      $config = CRM_Core_Config::singleton();
      $config->doNotResetCache = 1;
      $cid = CRM_Contact_BAO_Contact::createProfileContact($formatted, $contactFields, $contactId, NULL, NULL, $formatted['contact_type']);
      $config->doNotResetCache = 0;

      $contact = array(
        'contact_id' => $cid,
      );

      $defaults = array();
      $newContact = CRM_Contact_BAO_Contact::retrieve($contact, $defaults);
    }

    //get the id of the contact whose street address is not parsable, CRM-5886
    if ($this->_parseStreetAddress && is_object($newContact) && property_exists($newContact, 'address') && $newContact->address) {
      foreach ($newContact->address as $address) {
        if (!empty($address['street_address']) && (empty($address['street_number']) || empty($address['street_name']))) {
          $this->_unparsedStreetAddressContacts[] = array(
            'id' => $newContact->id,
            'streetAddress' => $address['street_address'],
          );
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

    $contactParams = array(
      'contact_id' => $cid,
    );

    $defaults = array();
    $contactObj = CRM_Contact_BAO_Contact::retrieve($contactParams, $defaults);

    $modeUpdate = $modeFill = FALSE;

    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
      $modeUpdate = TRUE;
    }

    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_FILL) {
      $modeFill = TRUE;
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($params['contact_type'], CRM_Core_DAO::$_nullObject, $cid, 0, NULL);
    CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $defaults, FALSE, FALSE);

    $locationFields = array(
      'email' => 'email',
      'phone' => 'phone',
      'im' => 'name',
      'website' => 'website',
      'address' => 'address',
    );

    $contact = get_object_vars($contactObj);

    foreach ($params as $key => $value) {
      if ($key == 'id' || $key == 'contact_type') {
        continue;
      }

      if (array_key_exists($key, $locationFields)) {
        continue;
      }
      elseif (in_array($key, array(
        'email_greeting',
        'postal_greeting',
        'addressee',
      ))) {
        // CRM-4575, need to null custom
        if ($params["{$key}_id"] != 4) {
          $params["{$key}_custom"] = 'null';
        }
        unset($params[$key]);
      }
      elseif ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $custom = TRUE;
      }
      else {
        $getValue = CRM_Utils_Array::retrieveValueRecursive($contact, $key);

        if ($key == 'contact_source') {
          $params['source'] = $params[$key];
          unset($params[$key]);
        }

        if ($modeFill && isset($getValue)) {
          unset($params[$key]);
        }
      }
    }

    foreach ($locationFields as $locKeys) {
      if (is_array(CRM_Utils_Array::value($locKeys, $params))) {
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
   * Format common params data to proper format to store.
   *
   * @param array $params
   *   Contain record values.
   * @param array $formatted
   *   Array of formatted data.
   * @param array $contactFields
   *   Contact DAO fields.
   */
  public function formatCommonData($params, &$formatted, &$contactFields) {
    $csType = array(
      CRM_Utils_Array::value('contact_type', $formatted),
    );

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
    $elements = array(
      'email_greeting_custom' => 'email_greeting',
      'postal_greeting_custom' => 'postal_greeting',
      'addressee_custom' => 'addressee',
    );
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
          self::formatCustomDate($params, $formatted, $dateType, $key);
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
      }
      elseif ($key == 'is_deceased' && $val) {
        $params[$key] = CRM_Utils_String::strtoboolstr($val);
      }
      elseif ($key == 'gender') {
        //CRM-4360
        $params[$key] = $this->checkGender($val);
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
            require_once 'CRM/Utils/DeprecatedUtils.php';
            _civicrm_api3_deprecated_add_formatted_param($value, $formatted);
          }
        }
        if (!$isAddressCustomField) {
          continue;
        }
      }

      $formatValues = array(
        $key => $field,
      );

      if (($key !== 'preferred_communication_method') && (array_key_exists($key, $contactFields))) {
        // due to merging of individual table and
        // contact table, we need to avoid
        // preferred_communication_method forcefully
        $formatValues['contact_type'] = $formatted['contact_type'];
      }

      if ($key == 'id' && isset($field)) {
        $formatted[$key] = $field;
      }
      require_once 'CRM/Utils/DeprecatedUtils.php';
      _civicrm_api3_deprecated_add_formatted_param($formatValues, $formatted);

      //Handling Custom Data
      // note: Address custom fields will be handled separately inside _civicrm_api3_deprecated_add_formatted_param
      if (($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) &&
        array_key_exists($customFieldID, $customFields) &&
        !array_key_exists($customFieldID, $addressCustomFields)
      ) {

        $extends = CRM_Utils_Array::value('extends', $customFields[$customFieldID]);
        $htmlType = CRM_Utils_Array::value('html_type', $customFields[$customFieldID]);
        switch ($htmlType) {
          case 'Select':
          case 'Radio':
          case 'Autocomplete-Select':
            if ($customFields[$customFieldID]['data_type'] == 'String') {
              $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
              foreach ($customOption as $customValue) {
                $val = CRM_Utils_Array::value('value', $customValue);
                $label = CRM_Utils_Array::value('label', $customValue);
                $label = strtolower($label);
                $value = strtolower(trim($formatted[$key]));
                if (($value == $label) || ($value == strtolower($val))) {
                  $params[$key] = $formatted[$key] = $val;
                }
              }
            }
            break;

          case 'CheckBox':
          case 'AdvMulti-Select':
          case 'Multi-Select':

            if (!empty($formatted[$key]) && !empty($params[$key])) {
              $mulValues = explode(',', $formatted[$key]);
              $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
              $formatted[$key] = array();
              $params[$key] = array();
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
            break;
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
        $emptyValue = CRM_Utils_Array::value('value', $customvalue[-1]);
        if (!isset($emptyValue)) {
          unset($formatted['custom'][$customKey]);
        }
      }
    }

    // parse street address, CRM-5450
    if ($this->_parseStreetAddress) {
      if (array_key_exists('address', $formatted) && is_array($formatted['address'])) {
        foreach ($formatted['address'] as $instance => & $address) {
          $streetAddress = CRM_Utils_Array::value('street_address', $address);
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
      $importRecordParams = array(
        $statusFieldName => 'IMPORTED',
      );
    }
    else {
      $errorMessage = ts("Record imported successfully but unable to parse the street address: ");
      foreach ($this->_unparsedStreetAddressContacts as $contactInfo => $contactValue) {
        $contactUrl = CRM_Utils_System::url('civicrm/contact/add', 'reset=1&action=update&cid=' . $contactValue['id'], TRUE, NULL, FALSE);
        $errorMessage .= "\n Contact ID:" . $contactValue['id'] . " <a href=\"$contactUrl\"> " . $contactValue['streetAddress'] . "</a>";
      }
      array_unshift($values, $errorMessage);
      $importRecordParams = array(
        $statusFieldName => 'ERROR',
        "${statusFieldName}Msg" => $errorMessage,
      );
      $returnCode = CRM_Import_Parser::UNPARSED_ADDRESS_WARNING;
    }
    $this->updateImportRecord($values[count($values) - 1], $importRecordParams);
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
    static $relatedContactFields = array();
    if (!isset($relatedContactFields[$relKey])) {
      foreach ($this->_mapperRelated as $key => $name) {
        if (!$name) {
          continue;
        }

        if (!empty($relatedContactFields[$name]) && !is_array($relatedContactFields[$name])) {
          $relatedContactFields[$name] = array();
        }
        $fldName = CRM_Utils_Array::value($key, $this->_mapperRelatedContactDetails);
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
    $subTypes = array();
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
   * CRM-17275
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
      $extIDContact = civicrm_api3('Contact', 'get', array(
        'external_identifier' => $params['external_identifier'],
        'return' => 'id',
      ));
      if (isset($extIDContact['id'])) {
        $extIDMatch = $extIDContact['id'];
      }
    }
    $checkParams = array('check_permissions' => FALSE, 'match' => $params);
    $checkParams['match']['contact_type'] = $this->_contactType;

    $possibleMatches = civicrm_api3('Contact', 'duplicatecheck', $checkParams);
    if (!$extIDMatch) {
      return array_keys($possibleMatches['values']);
    }
    if ($possibleMatches['count']) {
      if (in_array($extIDMatch, array_keys($possibleMatches['values']))) {
        return array($extIDMatch);
      }
      else {
        throw new CRM_Core_Exception(ts(
          'Matching this contact based on the de-dupe rule would cause an external ID conflict'));
      }
    }
    return array($extIDMatch);
  }

}
