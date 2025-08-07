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

use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Token\TokenProcessor;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_BAO_Contact extends CRM_Contact_DAO_Contact implements Civi\Core\HookInterface {

  /**
   * SQL function used to format the phone_numeric field via trigger.
   *
   * @see self::triggerInfo()
   *
   * Note that this is also used by the 4.3 upgrade script.
   * @see CRM_Upgrade_Incremental_php_FourThree
   */
  const DROP_STRIP_FUNCTION_43 = "DROP FUNCTION IF EXISTS civicrm_strip_non_numeric";

  const CREATE_STRIP_FUNCTION_43 = "
    CREATE FUNCTION civicrm_strip_non_numeric(input VARCHAR(255))
      RETURNS VARCHAR(255)
      DETERMINISTIC
      NO SQL
    BEGIN
      DECLARE output   VARCHAR(255) DEFAULT '';
      DECLARE iterator INT          DEFAULT 1;
      WHILE iterator < (LENGTH(input) + 1) DO
        IF SUBSTRING(input, iterator, 1) IN ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9') THEN
          SET output = CONCAT(output, SUBSTRING(input, iterator, 1));
        END IF;
        SET iterator = iterator + 1;
      END WHILE;
      RETURN output;
    END";

  /**
   * The types of communication preferences.
   *
   * @var array
   */
  public static $_commPrefs = [
    'do_not_phone',
    'do_not_email',
    'do_not_mail',
    'do_not_sms',
    'do_not_trade',
  ];

  /**
   * Types of greetings.
   *
   * @var array
   */
  public static $_greetingTypes = [
    'addressee',
    'email_greeting',
    'postal_greeting',
  ];

  /**
   * Static field for all the contact information that we can potentially import.
   *
   * @var array
   */
  public static $_importableFields = [];

  /**
   * Static field for all the contact information that we can potentially export.
   *
   * @var array
   */
  public static $_exportableFields = NULL;

  /**
   * Takes an associative array and creates a contact object.
   *
   * The function extracts all the params it needs to initialize the create a
   * contact object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Contact_DAO_Contact|CRM_Core_Error|NULL
   *   Created or updated contact object or error object.
   *   (error objects are being phased out in favour of exceptions)
   * @throws \CRM_Core_Exception
   */
  public static function add(&$params) {
    $contact = new CRM_Contact_DAO_Contact();
    $contactID = $params['contact_id'] ?? NULL;
    if (empty($params)) {
      return NULL;
    }

    // Fix for validate contact sub type CRM-5143.
    if (isset($params['contact_sub_type'])) {
      if (empty($params['contact_sub_type'])) {
        $params['contact_sub_type'] = 'null';
      }
      elseif ($params['contact_sub_type'] !== 'null') {
        if (!CRM_Contact_BAO_ContactType::isExtendsContactType($params['contact_sub_type'],
          $params['contact_type'], TRUE
        )
        ) {
          // we'll need to fix tests to handle this
          // CRM-7925
          throw new CRM_Core_Exception(ts('The Contact Sub Type does not match the Contact type for this record'));
        }
        // Ensure value is an array so it can be handled properly by `copyValues()`
        if (is_string($params['contact_sub_type'])) {
          $params['contact_sub_type'] = CRM_Core_DAO::unSerializeField($params['contact_sub_type'], self::fields()['contact_sub_type']['serialize']);
        }
      }
    }

    if (isset($params['preferred_communication_method']) && is_array($params['preferred_communication_method'])) {
      if (!empty($params['preferred_communication_method']) && empty($params['preferred_communication_method'][0])) {
        CRM_Core_Error::deprecatedWarning(' Form layer formatting should never get to the BAO');
        CRM_Utils_Array::formatArrayKeys($params['preferred_communication_method']);
        $contact->preferred_communication_method = CRM_Utils_Array::implodePadded($params['preferred_communication_method']);
        unset($params['preferred_communication_method']);
      }
    }

    $defaults = ['source' => $params['contact_source'] ?? NULL];
    if ($params['contact_type'] === 'Organization' && isset($params['organization_name'])) {
      $defaults['display_name'] = $params['organization_name'];
      $defaults['sort_name'] = $params['organization_name'];
    }
    if ($params['contact_type'] === 'Household' && isset($params['household_name'])) {
      $defaults['display_name'] = $params['household_name'];
      $defaults['sort_name'] = $params['household_name'];
    }
    $params = array_merge($defaults, $params);

    if (!empty($params['deceased_date']) && $params['deceased_date'] !== 'null') {
      $params['is_deceased'] = TRUE;
    }
    $allNull = $contact->copyValues($params);

    $contact->id = $contactID;

    if ($contact->contact_type === 'Individual') {
      $allNull = FALSE;
      // @todo get rid of this - most of this formatting should
      // be done by time we get here - maybe start with some
      // deprecation notices.
      CRM_Contact_BAO_Individual::format($params, $contact);
    }

    // Note that copyValues() above might already call this, via
    // CRM_Utils_String::ellipsify(), but e.g. for Individual it gets put
    // back or altered by Individual::format() just above, so we need to
    // check again.
    // Note also orgs will get ellipsified, but if we do that here then
    // some existing tests on individual fail.
    // Also api v3 will enforce org naming length by failing, v4 will truncate.
    if (mb_strlen(($contact->display_name ?? ''), 'UTF-8') > 128) {
      $contact->display_name = mb_substr($contact->display_name, 0, 128, 'UTF-8');
    }
    if (mb_strlen(($contact->sort_name ?? ''), 'UTF-8') > 128) {
      $contact->sort_name = mb_substr($contact->sort_name, 0, 128, 'UTF-8');
    }

    $privacy = $params['privacy'] ?? NULL;
    if ($privacy && is_array($privacy)) {
      $allNull = FALSE;
      foreach (self::$_commPrefs as $name) {
        $contact->$name = $privacy[$name] ?? FALSE;
      }
    }

    // Since hash was required, make sure we have a 0 value for it (CRM-1063).
    // @todo - does this mean we can remove this block?
    // Fixed in 1.5 by making hash optional, only do this in create mode, not update.
    if ((!isset($contact->hash) || !$contact->hash) && !$contact->id) {
      $allNull = FALSE;
      $contact->hash = bin2hex(random_bytes(16));
    }

    // Even if we don't need $employerId, it's important to call getFieldValue() before
    // the contact is saved because we want the existing value to be cached.
    // createCurrentEmployerRelationship() needs the old value not the updated one. CRM-10788
    $employerId = (!$contactID || $contact->contact_type !== 'Individual') ? NULL : CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contact->id, 'employer_id');

    if (!$allNull) {
      $contact->save();

      CRM_Core_BAO_Log::register($contact->id,
        'civicrm_contact',
        $contact->id
      );
    }

    if ($contact->contact_type === 'Individual' && (isset($params['current_employer']) || isset($params['employer_id']))) {
      // Create current employer.
      $newEmployer = !empty($params['employer_id']) ? $params['employer_id'] : $params['current_employer'] ?? NULL;

      $newContact = empty($params['contact_id']);
      if (!CRM_Utils_System::isNull($newEmployer)) {
        CRM_Contact_BAO_Contact_Utils::createCurrentEmployerRelationship($contact->id, $newEmployer, $employerId, $newContact);
      }
      elseif ($employerId) {
        CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($contact->id, $employerId);
      }
    }

    // Update cached employer name if the name of an existing organization is being updated.
    if ($contact->contact_type === 'Organization' && !empty($params['organization_name']) && $contactID) {
      CRM_Contact_BAO_Contact_Utils::updateCurrentEmployer($contact->id);
    }

    return $contact;
  }

  /**
   * Create contact.
   *
   * takes an associative array and creates a contact object and all the associated
   * derived objects (i.e. individual, location, email, phone etc)
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param bool $fixAddress
   *   If we need to fix address.
   * @param bool $invokeHooks
   *   If we need to invoke hooks.
   *
   * @param bool $skipDelete
   *   Unclear parameter, passed to website create
   *
   * @return CRM_Contact_BAO_Contact|CRM_Core_Error|NULL
   *   Created or updated contribution object. We are deprecating returning an error in
   *   favour of exceptions
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function &create(&$params, $fixAddress = TRUE, $invokeHooks = TRUE, $skipDelete = FALSE) {
    $contact = NULL;
    if (empty($params['contact_type']) && empty($params['contact_id'])) {
      return $contact;
    }

    $isEdit = !empty($params['contact_id']);

    if ($isEdit && empty($params['contact_type'])) {
      $params['contact_type'] = self::getContactType($params['contact_id']);
    }

    if (!empty($params['check_permissions']) && isset($params['api_key'])
      && !CRM_Core_Permission::check([['edit api keys', 'administer CiviCRM']])
      && !($isEdit && CRM_Core_Permission::check('edit own api keys') && $params['contact_id'] == CRM_Core_Session::getLoggedInContactID())
    ) {
      throw new \Civi\API\Exception\UnauthorizedException('Permission denied to modify api key');
    }

    if ($invokeHooks) {
      if (!empty($params['contact_id'])) {
        CRM_Utils_Hook::pre('edit', $params['contact_type'], $params['contact_id'], $params);
      }
      else {
        CRM_Utils_Hook::pre('create', $params['contact_type'], NULL, $params);
      }
    }

    self::ensureGreetingParamsAreSet($params);

    // CRM-6942: set preferred language to the current language if it’s unset (and we’re creating a contact).
    if (empty($params['contact_id'])) {
      // A case could be made for checking isset rather than empty but this is more consistent with previous behaviour.
      if (empty($params['preferred_language']) && ($language = CRM_Core_I18n::getContactDefaultLanguage()) != FALSE) {
        $params['preferred_language'] = $language;
      }

      // CRM-21041: set default 'Communication Style' if unset when creating a contact.
      if (empty($params['communication_style_id'])) {
        $defaultCommunicationStyleId = CRM_Core_OptionGroup::values('communication_style', TRUE, NULL, NULL, 'AND is_default = 1');
        $params['communication_style_id'] = array_pop($defaultCommunicationStyleId);
      }
    }

    $transaction = new CRM_Core_Transaction();

    $contact = self::add($params);

    $params['contact_id'] = $contact->id;

    if (!$isEdit && Civi::settings()->get('multisite_is_enabled')) {
      // Enabling multisite causes the contact to be added to the domain group.
      $domainGroupID = CRM_Core_BAO_Domain::getGroupId();
      if (!empty($domainGroupID)) {
        if (!empty($params['group']) && is_array($params['group'])) {
          $params['group'][$domainGroupID] = 1;
        }
        else {
          $params['group'] = [$domainGroupID => 1];
        }
      }
    }

    if (!empty($params['group'])) {
      $contactIds = [$params['contact_id']];
      foreach ($params['group'] as $groupId => $flag) {
        if ($flag == 1) {
          CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId);
        }
        elseif ($flag == -1) {
          CRM_Contact_BAO_GroupContact::removeContactsFromGroup($contactIds, $groupId);
        }
      }
    }

    // Add location Block data.
    $blocks = CRM_Core_BAO_Location::create($params, $fixAddress);
    foreach ($blocks as $name => $value) {
      $contact->$name = $value;
    }
    if (!empty($params['updateBlankLocInfo'])) {
      $skipDelete = TRUE;
    }

    if (isset($params['website'])) {
      CRM_Core_BAO_Website::process($params['website'], $contact->id, $skipDelete);
    }

    $userID = CRM_Core_Session::singleton()->get('userID');
    // add notes
    if (!empty($params['note'])) {
      if (is_array($params['note'])) {
        foreach ($params['note'] as $note) {
          $contactId = $contact->id;
          if (isset($note['contact_id'])) {
            $contactId = $note['contact_id'];
          }
          //if logged in user, overwrite contactId
          if ($userID) {
            $contactId = $userID;
          }

          $noteParams = [
            'entity_id' => $contact->id,
            'entity_table' => 'civicrm_contact',
            'note' => $note['note'],
            'subject' => $note['subject'] ?? NULL,
            'contact_id' => $contactId,
          ];
          CRM_Core_BAO_Note::add($noteParams);
        }
      }
      else {
        $contactId = $contact->id;
        //if logged in user, overwrite contactId
        if ($userID) {
          $contactId = $userID;
        }

        $noteParams = [
          'entity_id' => $contact->id,
          'entity_table' => 'civicrm_contact',
          'note' => $params['note'],
          'subject' => $params['subject'] ?? NULL,
          'contact_id' => $contactId,
        ];
        CRM_Core_BAO_Note::add($noteParams);
      }
    }

    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contact', $contact->id, $isEdit ? 'edit' : 'create');
    }

    $transaction->commit();

    // CRM-6367: fetch the right label for contact type’s display
    $contact->contact_type_display = CRM_Core_DAO::getFieldValue(
      'CRM_Contact_DAO_ContactType',
      $contact->contact_type,
      'label',
      'name'
    );

    CRM_Contact_BAO_Contact_Utils::clearContactCaches();

    if ($invokeHooks) {
      if ($isEdit) {
        CRM_Utils_Hook::post('edit', $params['contact_type'], $contact->id, $contact);
      }
      else {
        CRM_Utils_Hook::post('create', $params['contact_type'], $contact->id, $contact);
      }
    }

    // In order to prevent a series of expensive queries in intensive batch processing
    // api calls may pass in skip_greeting_processing, probably doing it later via the
    // scheduled job. CRM-21551
    if (empty($params['skip_greeting_processing'])) {
      self::processGreetings($contact);
    }

    if (!empty($params['check_permissions'])) {
      $contacts = [&$contact];
      self::unsetProtectedFields($contacts);
    }

    if (!empty($params['is_deceased'])) {
      // Edit Membership Status
      $deceasedParams = [
        'contact_id' => $contact->id,
        'is_deceased' => $params['is_deceased'],
        'deceased_date' => $params['deceased_date'] ?? NULL,
      ];
      CRM_Member_BAO_Membership::updateMembershipStatus($deceasedParams);
    }

    return $contact;
  }

  /**
   * Check if a contact has a name.
   *
   * - Individuals need a first_name or last_name
   * - Organizations need organization_name
   * - Households need household_name
   *
   * @param array $contact
   * @return bool
   */
  public static function hasName(array $contact): bool {
    $nameFields = [
      'Individual' => ['first_name', 'last_name'],
      'Organization' => ['organization_name'],
      'Household' => ['household_name'],
    ];
    // Casting to int filters out the string 'null'
    $cid = (int) ($contact['id'] ?? NULL);
    $contactType = $contact['contact_type'] ?? NULL;
    if (!$contactType && $cid) {
      $contactType = CRM_Core_DAO::getFieldValue(__CLASS__, $cid, 'contact_type');
    }
    if (!$contactType || !isset($nameFields[$contactType])) {
      throw new CRM_Core_Exception('No contact_type given to ' . __CLASS__ . '::' . __FUNCTION__);
    }
    foreach ($nameFields[$contactType] as $field) {
      if (isset($contact[$field]) && is_string($contact[$field]) && $contact[$field] !== '') {
        return TRUE;
      }
    }
    // For existing contacts, look up name from database
    if ($cid) {
      foreach ($nameFields[$contactType] as $field) {
        $value = $contact[$field] ?? CRM_Core_DAO::getFieldValue(__CLASS__, $cid, $field);
        if (isset($value) && $value !== '') {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Format the output of the create contact function
   *
   * @param CRM_Contact_DAO_Contact[]|array[] $contacts
   */
  public static function unsetProtectedFields(&$contacts) {
    if (!CRM_Core_Permission::check([['edit api keys', 'administer CiviCRM']])) {
      $currentUser = CRM_Core_Session::getLoggedInContactID();
      $editOwn = $currentUser && CRM_Core_Permission::check('edit own api keys');
      foreach ($contacts as &$contact) {
        $cid = is_object($contact) ? $contact->id : $contact['id'] ?? NULL;
        if (!($editOwn && $cid == $currentUser)) {
          if (is_object($contact)) {
            unset($contact->api_key);
          }
          else {
            unset($contact['api_key']);
          }
        }
      }
    }
  }

  /**
   * Ensure greeting parameters are set.
   *
   * By always populating greetings here we can be sure they are set if required & avoid a call later.
   * (ie. knowing we have definitely tried disambiguates between NULL & not loaded.)
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  public static function ensureGreetingParamsAreSet(&$params) {
    $allGreetingParams = ['addressee' => 'addressee_id', 'postal_greeting' => 'postal_greeting_id', 'email_greeting' => 'email_greeting_id'];
    $missingGreetingParams = [];

    foreach ($allGreetingParams as $greetingIndex => $greetingParam) {
      if (!empty($params[$greetingIndex . '_custom']) && empty($params[$greetingParam])) {
        $params[$greetingParam] = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', $greetingParam, 'Customized');
      }
      // An empty string means NULL
      if (($params[$greetingParam] ?? NULL) === '') {
        $params[$greetingParam] = 'null';
      }
      if (empty($params[$greetingParam])) {
        $missingGreetingParams[$greetingIndex] = $greetingParam;
      }
    }

    if (!empty($params['contact_id']) && !empty($missingGreetingParams)) {
      $savedGreetings = civicrm_api3('Contact', 'getsingle', [
        'id' => $params['contact_id'],
        'return' => array_keys($missingGreetingParams),
      ]);

      foreach (array_keys($missingGreetingParams) as $missingGreetingParam) {
        if (!empty($savedGreetings[$missingGreetingParam . '_custom'])) {
          $missingGreetingParams[$missingGreetingParam . '_custom'] = $missingGreetingParam . '_custom';
        }
      }
      // Filter out other fields.
      $savedGreetings = array_intersect_key($savedGreetings, array_flip($missingGreetingParams));
      $params = array_merge($params, $savedGreetings);
    }
    else {
      foreach ($missingGreetingParams as $greetingName => $greeting) {
        $params[$greeting] = CRM_Contact_BAO_Contact_Utils::defaultGreeting($params['contact_type'], $greetingName);
      }
    }

    foreach ($allGreetingParams as $greetingIndex => $greetingParam) {
      if ($params[$greetingParam] === 'null') {
        //  If we are setting it to null then null out the display field.
        $params[$greetingIndex . '_display'] = 'null';
      }
      if ((int) $params[$greetingParam] != CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', $greetingParam, 'Customized')) {
        $params[$greetingIndex . '_custom'] = 'null';
      }

    }
  }

  /**
   * Get the display name and image of a contact.
   *
   * @param int $id
   *   The contactId.
   *
   * @param bool $includeTypeInReturnParameters
   *   Should type be part of the returned array?
   *
   * @return array|null
   *   the displayName and contactImage for this contact
   */
  public static function getDisplayAndImage($id, $includeTypeInReturnParameters = FALSE) {
    //CRM-14276 added the * on the civicrm_contact table so that we have all the contact info available
    $sql = "
SELECT    civicrm_contact.*,
          civicrm_email.email          as email
FROM      civicrm_contact
LEFT JOIN civicrm_email ON civicrm_email.contact_id = civicrm_contact.id
     AND  civicrm_email.is_primary = 1
WHERE     civicrm_contact.id = " . CRM_Utils_Type::escape($id, 'Integer');
    $dao = new CRM_Core_DAO();
    $dao->query($sql);
    if ($dao->fetch()) {
      $image = CRM_Contact_BAO_Contact_Utils::getImage($dao->contact_sub_type ?
        $dao->contact_sub_type : $dao->contact_type, FALSE, $id
      );
      $imageUrl = CRM_Contact_BAO_Contact_Utils::getImage($dao->contact_sub_type ?
        $dao->contact_sub_type : $dao->contact_type, TRUE, $id
      );

      // use email if display_name is empty
      if (empty($dao->display_name)) {
        $displayName = $dao->email;
      }
      else {
        $displayName = $dao->display_name;
      }

      CRM_Utils_Hook::alterDisplayName($displayName, $id, $dao);

      return $includeTypeInReturnParameters ? [
        $displayName,
        $image,
        $dao->contact_type,
        $dao->contact_sub_type,
        $imageUrl,
      ] : [$displayName, $image, $imageUrl];
    }
    return NULL;
  }

  /**
   * Add billing fields to the params if appropriate.
   *
   * If we have ANY name fields then we want to ignore all the billing name fields. However, if we
   * don't then we should set the name fields to the billing fields AND add the preserveDBName
   * parameter (which will tell the BAO only to set those fields if none already exist.
   *
   * We specifically don't want to set first name from billing and last name form an on-page field. Mixing &
   * matching is best done by hipsters.
   *
   * @param array $params
   *
   * @fixme How does this relate to almost the same thing being done in CRM_Core_Form::formatParamsForPaymentProcessor()
   */
  public static function addBillingNameFieldsIfOtherwiseNotSet(&$params) {
    $nameFields = ['first_name', 'middle_name', 'last_name', 'nick_name', 'prefix_id', 'suffix_id'];
    foreach ($nameFields as $field) {
      if (!empty($params[$field])) {
        return;
      }
    }
    // There are only 3 - we can iterate through them twice :-)
    foreach ($nameFields as $field) {
      if (!empty($params['billing_' . $field])) {
        $params[$field] = $params['billing_' . $field];
      }
      $params['preserveDBName'] = TRUE;
    }

  }

  /**
   * Resolve a state province string (UT or Utah) to an ID.
   *
   * If country has been passed in we should select a state belonging to that country.
   *
   * Alternatively we should choose from enabled countries, prioritising the default country.
   *
   * @param array $values
   * @param int|null $countryID
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   */
  protected static function resolveStateProvinceID($values, $countryID) {

    if ($countryID) {
      $stateProvinceList = CRM_Core_PseudoConstant::stateProvinceForCountry($countryID);
      if (CRM_Utils_Array::lookupValue($values,
        'state_province',
        $stateProvinceList,
        TRUE
      )) {
        return $values['state_province_id'];
      }
      $stateProvinceList = CRM_Core_PseudoConstant::stateProvinceForCountry($countryID, 'abbreviation');
      if (CRM_Utils_Array::lookupValue($values,
        'state_province',
        $stateProvinceList,
        TRUE
      )) {
        return $values['state_province_id'];
      }
      return NULL;
    }
    else {
      // The underlying lookupValue function needs some de-fanging. Until that has been unravelled we
      // continue to resolve stateprovince lists in descending order of preference & just 'keep trying'.
      // prefer matching country..
      $stateProvinceList = CRM_Core_BAO_Address::buildOptions('state_province_id', NULL, ['country_id' => Civi::settings()->get('defaultContactCountry')]);
      if (CRM_Utils_Array::lookupValue($values,
        'state_province',
        $stateProvinceList,
        TRUE
      )) {
        return $values['state_province_id'];
      }

      $stateProvinceList = CRM_Core_PseudoConstant::stateProvince();
      if (CRM_Utils_Array::lookupValue($values,
        'state_province',
        $stateProvinceList,
        TRUE
      )) {
        return $values['state_province_id'];
      }

      $stateProvinceList = CRM_Core_PseudoConstant::stateProvinceAbbreviationForDefaultCountry();
      if (CRM_Utils_Array::lookupValue($values,
        'state_province',
        $stateProvinceList,
        TRUE
      )) {
        return $values['state_province_id'];
      }
      $stateProvinceList = CRM_Core_PseudoConstant::stateProvinceAbbreviation();
      if (CRM_Utils_Array::lookupValue($values,
        'state_province',
        $stateProvinceList,
        TRUE
      )) {
        return $values['state_province_id'];
      }
    }

    return NULL;
  }

  /**
   * Get the relevant location entity for the array key.
   *
   * Based on the field name we determine which location entity
   * we are dealing with. Apart from a few specific ones they
   * are mostly 'address' (the default).
   *
   * @param string $fieldName
   *
   * @return string
   */
  protected static function getLocationEntityForKey($fieldName) {
    if (in_array($fieldName, ['email', 'phone', 'im', 'openid'])) {
      return $fieldName;
    }
    if ($fieldName === 'phone_ext') {
      return 'phone';
    }
    return 'address';
  }

  /**
   * Soft delete a contact.
   *
   * Call this via the api, not directly.
   *
   * @param \CRM_Contact_DAO_Contact $contact
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected static function contactTrash(CRM_Contact_DAO_Contact $contact): bool {
    $updateParams = [
      'id' => $contact->id,
      'is_deleted' => 1,
    ];
    CRM_Utils_Hook::pre('edit', $contact->contact_type, $contact->id, $updateParams);

    $contact->copyValues($updateParams);
    $contact->save();
    CRM_Core_BAO_Log::register($contact->id, 'civicrm_contact', $contact->id);

    CRM_Utils_Hook::post('edit', $contact->contact_type, $contact->id, $contact);

    return TRUE;
  }

  /**
   * Fetch object based on array of properties.
   *
   * @deprecated This is called from a few places but creates rather than solves
   * complexity.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the name / value pairs.
   *                        in a hierarchical manner
   * @param bool $microformat
   *   Deprecated value
   *
   * @return CRM_Contact_BAO_Contact
   */
  public static function &retrieve(&$params, &$defaults = [], $microformat = FALSE) {
    if ($microformat) {
      CRM_Core_Error::deprecatedWarning('microformat is deprecated in CRM_Contact_BAO_Contact::retrieve');
    }
    if (array_key_exists('contact_id', $params)) {
      $params['id'] = $params['contact_id'];
    }
    elseif (array_key_exists('id', $params)) {
      $params['contact_id'] = $params['id'];
    }

    $contact = self::getValues($params, $defaults);

    unset($params['id']);

    $contact->im = $defaults['im'] = CRM_Core_BAO_IM::getValues(['contact_id' => $params['contact_id']]);
    $contact->email = $defaults['email'] = CRM_Core_BAO_Email::getValues(['contact_id' => $params['contact_id']]);
    $contact->openid = $defaults['openid'] = CRM_Core_BAO_OpenID::getValues(['contact_id' => $params['contact_id']]);
    $contact->phone = $defaults['phone'] = CRM_Core_BAO_Phone::getValues(['contact_id' => $params['contact_id']]);
    $contact->address = $defaults['address'] = CRM_Core_BAO_Address::getValues(['contact_id' => $params['contact_id']], $microformat);
    $contact->website = CRM_Core_BAO_Website::getValues($params, $defaults);

    if (!isset($params['noNotes'])) {
      $contact->notes = CRM_Core_BAO_Note::getValues($params, $defaults);
    }

    if (!isset($params['noRelationships'])) {
      $contact->relationship = CRM_Contact_BAO_Relationship::getValues($params, $defaults);
    }

    if (!isset($params['noGroups'])) {
      $contact->groupContact = CRM_Contact_BAO_GroupContact::getValues($params, $defaults);
    }

    return $contact;
  }

  /**
   * Get the display name of a contact.
   *
   * @param int $id
   *   Id of the contact.
   *
   * @return null|string
   *   display name of the contact if found
   */
  public static function displayName($id) {
    $displayName = NULL;
    if ($id) {
      $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $id, 'display_name');
    }

    return $displayName;
  }

  /**
   * Get the CMS user id
   *
   * @param int $contactId
   * @return int
   */
  private static function getUFId(int $contactId): int {
    // Note: we're not using CRM_Core_BAO_UFMatch::getUFId() because that's cached.
    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->contact_id = $contactId;
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    return $ufmatch->find(TRUE) ? $ufmatch->uf_id : 0;
  }

  /**
   * Delete a contact and all its associated records.
   *
   * @param int $id
   *   Id of the contact to delete.
   * @param bool $restore
   *   Whether to actually restore, not delete.
   * @param bool $skipUndelete
   *   Whether to force contact delete or not.
   * @param bool $checkPermissions
   *
   * @return bool
   *   Was contact deleted?
   *
   * @throws \CRM_Core_Exception
   */
  public static function deleteContact(int $id, bool $restore = FALSE, bool $skipUndelete = FALSE, bool $checkPermissions = TRUE): bool {
    // If trash is disabled in system settings then we always skip
    if (!Civi::settings()->get('contact_undelete')) {
      $skipUndelete = TRUE;
    }

    // make sure we have edit permission for this contact
    // before we delete
    if ($checkPermissions && (($skipUndelete && !CRM_Core_Permission::check('delete contacts')) ||
        ($restore && !CRM_Core_Permission::check('access deleted contacts')))
    ) {
      return FALSE;
    }

    // CRM-12929
    // Restrict contact to be delete if contact has financial trxns
    $error = NULL;
    if ($skipUndelete && CRM_Financial_BAO_FinancialItem::checkContactPresent([$id], $error)) {
      return FALSE;
    }

    // make sure this contact_id does not have any membership types
    $membershipTypeID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
      $id,
      'id',
      'member_of_contact_id'
    );
    if ($membershipTypeID) {
      return FALSE;
    }

    $contact = new CRM_Contact_DAO_Contact();
    $contact->id = $id;
    if (!$contact->find(TRUE)) {
      return FALSE;
    }

    $contactType = $contact->contact_type;
    if ($restore) {
      // @todo deprecate calling contactDelete with the intention to restore.
      $updateParams = [
        'id' => $contact->id,
        'is_deleted' => FALSE,
      ];
      self::create($updateParams);
      return TRUE;
    }

    // start a new transaction
    $transaction = new CRM_Core_Transaction();

    if ($skipUndelete) {
      $hookParams = [
        'check_permissions' => $checkPermissions,
        'uf_id' => self::getUFId($id),
      ];

      // Hook might delete the CMS user
      CRM_Utils_Hook::pre('delete', $contactType, $id, $hookParams);

      // Do not permit a contact to be deleted if it is (still) linked to a site user.
      if ($hookParams['uf_id'] && self::getUFId($id)) {
        $transaction->rollback();
        return FALSE;
      }

      //delete billing address if exists.
      CRM_Contribute_BAO_Contribution::deleteAddress(NULL, $id);

      // delete the log entries since we dont have triggers enabled as yet
      $logDAO = new CRM_Core_DAO_Log();
      $logDAO->entity_table = 'civicrm_contact';
      $logDAO->entity_id = $id;
      $logDAO->delete();

      // delete contact participants CRM-12155
      CRM_Event_BAO_Participant::deleteContactParticipant($id);

      // delete contact contributions CRM-12155
      CRM_Contribute_BAO_Contribution::deleteContactContribution($id);

      // do activity cleanup, CRM-5604
      CRM_Activity_BAO_Activity::cleanupActivity($id);

      // delete all notes related to contact
      CRM_Core_BAO_Note::cleanContactNotes($id);

      // delete cases related to contact
      $contactCases = CRM_Case_BAO_Case::retrieveCaseIdsByContactId($id);
      if (!empty($contactCases)) {
        foreach ($contactCases as $caseId) {
          //check if case is associate with other contact or not.
          $caseContactId = CRM_Case_BAO_Case::getCaseClients($caseId);
          if (count($caseContactId) <= 1) {
            CRM_Case_BAO_Case::deleteCase($caseId);
          }
        }
      }

      $contact->delete();
    }
    else {
      if (self::getUFId($id)) {
        return FALSE;
      }
      self::contactTrash($contact);
    }
    // currently we only clear employer cache.
    // we are now deleting inherited membership if any.
    if ($contact->contact_type == 'Organization') {
      $action = $restore ? CRM_Core_Action::ENABLE : CRM_Core_Action::DISABLE;
      $relationshipDtls = CRM_Contact_BAO_Relationship::getRelationship($id);
      if (!empty($relationshipDtls)) {
        foreach ($relationshipDtls as $rId => $details) {
          CRM_Contact_BAO_Relationship::disableEnableRelationship($rId, $action);
        }
      }
      CRM_Contact_BAO_Contact_Utils::clearAllEmployee($id);
    }

    //delete the contact id from recently view
    CRM_Utils_Recent::del(['contact_id' => $id]);
    self::updateContactCache($id, empty($restore));

    // delete any prevnext/dupe cache entry
    // These two calls are redundant in default deployments, but they're
    // meaningful if "prevnext" is memory-backed.
    Civi::service('prevnext')->deleteItem($id);
    CRM_Core_BAO_PrevNextCache::deleteItem($id);

    $transaction->commit();

    if ($skipUndelete) {
      CRM_Utils_Hook::post('delete', $contactType, $contact->id, $contact);
    }

    return TRUE;
  }

  /**
   * Action to update any caches relating to a recently update contact.
   *
   * I was going to call this from delete as well as from create to ensure the delete is being
   * done whenever a contact is set to is_deleted=1 BUT I found create is already over-aggressive in
   * that regard so adding it to delete seems to be enough to remove it from CRM_Contact_BAO_Contact_Permission
   * where the call involved a subquery that was locking the table.
   *
   * @param int $contactID
   * @param bool $isTrashed
   */
  public static function updateContactCache($contactID, $isTrashed = FALSE) {

    if ($isTrashed) {
      CRM_Contact_BAO_GroupContactCache::removeContact($contactID);
      // This has been moved to here from CRM_Contact_BAO_Contact_Permission as that was causing
      // a table-locking query. It still seems a bit inadequate as it assumes the acl users can't see deleted
      // but this should not cause any change as long as contacts are not being trashed outside the
      // main functions for that.
      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_acl_contact_cache WHERE contact_id = %1', [1 => [$contactID, 'Integer']]);
    }
    else {
      CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();
    }
  }

  /**
   * Delete the image of a contact.
   *
   * @param int $id
   *   Id of the contact.
   *
   * @return bool
   *   Was contact image deleted?
   */
  public static function deleteContactImage($id) {
    if (!$id) {
      return FALSE;
    }

    $contact = new self();
    $contact->id = $id;
    $contact->image_URL = 'null';
    $contact->save();

    return TRUE;
  }

  /**
   * Return proportional height and width of the image.
   *
   * @param int $imageWidth
   *   Width of image.
   *
   * @param int $imageHeight
   *   Height of image.
   *
   * @return array
   *   Thumb dimension of image
   */
  public static function getThumbSize($imageWidth, $imageHeight) {
    $thumbWidth = 100;
    if ($imageWidth && $imageHeight) {
      $imageRatio = $imageWidth / $imageHeight;
    }
    else {
      $imageRatio = 1;
    }
    if ($imageRatio > 1) {
      $imageThumbWidth = $thumbWidth;
      $imageThumbHeight = round($thumbWidth / $imageRatio);
    }
    else {
      $imageThumbHeight = $thumbWidth;
      $imageThumbWidth = round($thumbWidth * $imageRatio);
    }

    return [$imageThumbWidth, $imageThumbHeight];
  }

  /**
   * Validate type of contact image.
   *
   * @param array $params
   * @param string $imageIndex
   *   Index of image field.
   * @param string $statusMsg
   *   Status message to be set after operation.
   * @param string $opType
   *   Type of operation like fatal, bounce etc.
   *
   * @return bool
   *   true if valid image extension
   */
  public static function processImageParams(
    &$params,
    $imageIndex = 'image_URL',
    $statusMsg = NULL,
    $opType = 'status'
  ) {
    $mimeType = [
      'image/jpeg',
      'image/jpg',
      'image/png',
      'image/bmp',
      'image/p-jpeg',
      'image/gif',
      'image/x-png',
    ];

    if (in_array($params[$imageIndex]['type'], $mimeType)) {
      $photo = basename($params[$imageIndex]['name']);
      $params[$imageIndex] = CRM_Utils_System::url('civicrm/contact/imagefile', 'photo=' . $photo, TRUE, NULL, TRUE, TRUE);
      return TRUE;
    }
    else {
      unset($params[$imageIndex]);
      if (!$statusMsg) {
        $statusMsg = ts('Image could not be uploaded due to invalid type extension.');
      }
      if ($opType == 'status') {
        CRM_Core_Session::setStatus($statusMsg, ts('Error'), 'error');
      }
      // FIXME: additional support for fatal, bounce etc could be added.
      return FALSE;
    }
  }

  /**
   * Get contact type for a contact.
   *
   * @param int $id
   *   Id of the contact whose contact type is needed.
   *
   * @return string
   *   contact_type if $id found else null ""
   */
  public static function getContactType($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $id, 'contact_type');
  }

  /**
   * Get contact sub type for a contact.
   *
   * @param int $id
   *   Id of the contact whose contact sub type is needed.
   *
   * @param string $implodeDelimiter
   *
   * @return string
   *   contact_sub_type if $id found else null ""
   */
  public static function getContactSubType($id, $implodeDelimiter = NULL) {
    $subtype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $id, 'contact_sub_type');
    if (!$subtype) {
      return $implodeDelimiter ? NULL : [];
    }

    $subtype = CRM_Utils_Array::explodePadded($subtype);

    if ($implodeDelimiter) {
      $subtype = implode($implodeDelimiter, $subtype);
    }
    return $subtype;
  }

  /**
   * Get pair of contact-type and sub-type for a contact.
   *
   * @param int $id
   *   Id of the contact whose contact sub/contact type is needed.
   *
   * @return array
   */
  public static function getContactTypes($id) {
    $params = ['id' => $id];
    $details = [];
    $contact = CRM_Core_DAO::commonRetrieve('CRM_Contact_DAO_Contact',
      $params,
      $details,
      ['contact_type', 'contact_sub_type']
    );

    if ($contact) {
      $contactTypes = [];
      if ($contact->contact_sub_type) {
        $contactTypes = CRM_Utils_Array::explodePadded($contact->contact_sub_type);
      }
      array_unshift($contactTypes, $contact->contact_type);

      return $contactTypes;
    }
    else {
      throw new CRM_Core_Exception('Cannot proceed without a valid contact');
    }
  }

  /**
   * Combine all the importable fields from the lower levels object.
   *
   * The ordering is important, since currently we do not have a weight
   * scheme. Adding weight is super important
   *
   * @param int|string $contactType contact Type
   * @param bool $status
   *   Status is used to manipulate first title.
   * @param bool $showAll
   *   If true returns all fields (includes disabled fields).
   * @param bool $isProfile
   *   If its profile mode.
   * @param bool $checkPermission
   *   If false, do not include permissioning clause (for custom data).
   *
   * @param bool $withMultiCustomFields
   *
   * @return array
   *   array of importable Fields
   */
  public static function importableFields(
    $contactType = 'Individual',
    $status = FALSE,
    $showAll = FALSE,
    $isProfile = FALSE,
    $checkPermission = TRUE,
    $withMultiCustomFields = FALSE
  ) {
    if (empty($contactType)) {
      $contactType = 'All';
    }

    $cacheKeyString = "importableFields $contactType";
    $cacheKeyString .= $status ? '_1' : '_0';
    $cacheKeyString .= $showAll ? '_1' : '_0';
    $cacheKeyString .= $isProfile ? '_1' : '_0';
    $cacheKeyString .= $checkPermission ? '_1' : '_0';
    $cacheKeyString .= '_' . CRM_Core_Config::domainID() . '_';

    $fields = self::$_importableFields[$cacheKeyString] ?? Civi::cache('fields')->get($cacheKeyString);

    if (!$fields) {
      $fields = CRM_Contact_DAO_Contact::import();

      // get the fields thar are meant for contact types
      if (in_array($contactType, ['Individual', 'Household', 'Organization', 'All'])) {
        $fields = array_merge($fields, CRM_Core_OptionValue::getFields('', $contactType));
      }

      $locationFields = array_merge(CRM_Core_DAO_Address::import(),
        CRM_Core_DAO_Phone::import(),
        CRM_Core_DAO_Email::import(),
        CRM_Core_DAO_IM::import(TRUE),
        CRM_Core_DAO_OpenID::import()
      );

      $locationFields = array_merge($locationFields,
        CRM_Core_BAO_CustomField::getFieldsForImport('Address',
          FALSE,
          FALSE,
          FALSE,
          FALSE
        )
      );

      foreach ($locationFields as $key => $field) {
        $locationFields[$key]['hasLocationType'] = TRUE;
      }

      $fields = array_merge($fields, $locationFields);

      $fields = array_merge($fields, CRM_Contact_DAO_Contact::import());
      $fields = array_merge($fields, CRM_Core_DAO_Note::import());

      //website fields
      $fields = array_merge($fields, CRM_Core_DAO_Website::import());
      $fields['url']['hasWebsiteType'] = TRUE;

      if ($contactType != 'All') {
        $fields = array_merge($fields,
          CRM_Core_BAO_CustomField::getFieldsForImport($contactType,
            $showAll,
            TRUE,
            FALSE,
            FALSE,
            $withMultiCustomFields
          )
        );
        // Unset the fields which are not related to their contact type.
        foreach (CRM_Contact_DAO_Contact::import() as $name => $value) {
          if (!empty($value['contactType']) && $value['contactType'] !== $contactType) {
            unset($fields[$name]);
          }
        }
      }
      else {
        foreach (CRM_Contact_BAO_ContactType::basicTypes() as $type) {
          $fields = array_merge($fields,
            CRM_Core_BAO_CustomField::getFieldsForImport($type,
              $showAll,
              FALSE,
              FALSE,
              FALSE,
              $withMultiCustomFields
            )
          );
        }
      }

      if ($isProfile) {
        $fields = array_merge($fields, [
          'group' => [
            'title' => ts('Group(s)'),
            'name' => 'group',
          ],
          'tag' => [
            'title' => ts('Tag(s)'),
            'name' => 'tag',
          ],
          'note' => [
            'title' => ts('Note'),
            'name' => 'note',
          ],
          'communication_style_id' => [
            'title' => ts('Communication Style'),
            'name' => 'communication_style_id',
          ],
        ]);
      }

      //Sorting fields in alphabetical order(CRM-1507)
      $fields = CRM_Utils_Array::crmArraySortByField($fields, 'title');

      Civi::cache('fields')->set($cacheKeyString, $fields);
    }

    self::$_importableFields[$cacheKeyString] = $fields;

    if (!$isProfile) {
      if (!$status) {
        $fields = array_merge(['do_not_import' => ['title' => ts('- do not import -')]],
          self::$_importableFields[$cacheKeyString]
        );
      }
      else {
        $fields = array_merge(['' => ['title' => ts('- Contact Fields -')]],
          self::$_importableFields[$cacheKeyString]
        );
      }
    }
    return $fields;
  }

  /**
   * Combine all the exportable fields from the lower levels object.
   *
   * Currently we are using importable fields as exportable fields
   *
   * @param int|string $contactType contact Type
   * @param bool $status
   *   True while exporting primary contacts.
   * @param bool $export
   *   True when used during export.
   * @param bool $search
   *   True when used during search, might conflict with export param?.
   *
   * @param bool $withMultiRecord
   * @param bool $checkPermissions
   *
   * @return array
   *   array of exportable Fields
   */
  public static function &exportableFields($contactType = 'Individual', $status = FALSE, $export = FALSE, $search = FALSE, $withMultiRecord = FALSE, $checkPermissions = TRUE) {
    if (empty($contactType)) {
      $contactType = 'All';
    }

    $cacheKeyString = "exportableFields $contactType";
    $cacheKeyString .= $export ? '_1' : '_0';
    $cacheKeyString .= $status ? '_1' : '_0';
    $cacheKeyString .= $search ? '_1' : '_0';
    $cacheKeyString .= '_' . (bool) $checkPermissions;
    //CRM-14501 it turns out that the impact of permissioning here is sometimes inconsistent. The field that
    //calculates custom fields takes into account the logged in user & caches that for all users
    //as an interim fix we will cache the fields by contact
    $cacheKeyString .= '_' . CRM_Core_Session::getLoggedInContactID();

    if (!self::$_exportableFields || empty(self::$_exportableFields[$cacheKeyString])) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = [];
      }

      // check if we can retrieve from database cache
      $fields = Civi::cache('fields')->get($cacheKeyString);

      if (!$fields) {
        $fields = CRM_Contact_DAO_Contact::export();

        // The fields are meant for contact types.
        if (in_array($contactType, ['Individual', 'Household', 'Organization', 'All'])) {
          $fields = array_merge($fields, CRM_Core_OptionValue::getFields('', $contactType));
        }
        // add current employer for individuals
        $fields = array_merge($fields, [
          'current_employer' =>
            [
              'name' => 'organization_name',
              'title' => ts('Current Employer'),
              'type' => CRM_Utils_Type::T_STRING,
            ],
        ]);

        // This probably would be added anyway by appendPseudoConstantsToFields.
        $locationType = [
          'location_type' => [
            'name' => 'location_type',
            'where' => 'civicrm_location_type.name',
            'title' => ts('Location Type'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ];

        $IMProvider = [
          'im_provider' => [
            'name' => 'im_provider',
            'where' => 'civicrm_im.provider_id',
            'title' => ts('IM Provider'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ];

        $phoneFields = CRM_Core_DAO_Phone::export();
        // This adds phone_type to the exportable fields and make it available for export.
        // with testing the same can be done to the other entities.
        CRM_Core_DAO::appendPseudoConstantsToFields($phoneFields);
        $locationFields = array_merge($locationType,
          CRM_Core_DAO_Address::export(),
          $phoneFields,
          CRM_Core_DAO_Email::export(),
          $IMProvider,
          CRM_Core_DAO_IM::export(TRUE),
          CRM_Core_DAO_OpenID::export()
        );

        $locationFields = array_merge($locationFields,
          CRM_Core_BAO_CustomField::getFieldsForImport('Address')
        );

        foreach ($locationFields as $key => $field) {
          $locationFields[$key]['hasLocationType'] = TRUE;
        }

        $fields = array_merge($fields, $locationFields);

        //add world region
        $fields = array_merge($fields,
          CRM_Core_DAO_Worldregion::export()
        );

        $fields = array_merge($fields,
          CRM_Contact_DAO_Contact::export()
        );

        //website fields
        $fields = array_merge($fields, CRM_Core_DAO_Website::export());

        if ($contactType != 'All') {
          $fields = array_merge($fields,
            CRM_Core_BAO_CustomField::getFieldsForImport($contactType, $status, FALSE, $search, $checkPermissions, $withMultiRecord)
          );
        }
        else {
          foreach (CRM_Contact_BAO_ContactType::basicTypes() as $type) {
            $fields = array_merge($fields,
              CRM_Core_BAO_CustomField::getFieldsForImport($type, FALSE, FALSE, $search, $checkPermissions ? CRM_Core_Permission::VIEW : FALSE, $withMultiRecord)
            );
          }
        }
        //fix for CRM-791
        if ($export) {
          $fields = array_merge($fields, [
            'groups' => [
              'title' => ts('Group(s)'),
              'name' => 'groups',
            ],
            'tags' => [
              'title' => ts('Tag(s)'),
              'name' => 'tags',
            ],
            'notes' => [
              'title' => ts('Note(s)'),
              'name' => 'notes',
            ],
          ]);
        }
        else {
          $fields = array_merge($fields, [
            'group' => [
              'title' => ts('Group(s)'),
              'name' => 'group',
            ],
            'tag' => [
              'title' => ts('Tag(s)'),
              'name' => 'tag',
            ],
            'note' => [
              'title' => ts('Note(s)'),
              'name' => 'note',
            ],
          ]);
        }

        //Sorting fields in alphabetical order(CRM-1507)
        foreach ($fields as $k => $v) {
          $sortArray[$k] = $v['title'] ?? NULL;
        }

        $fields = array_merge($sortArray, $fields);
        //unset the field which are not related to their contact type.
        if ($contactType != 'All') {
          $commonValues = [
            'Individual' => [
              'household_name',
              'legal_name',
              'sic_code',
              'organization_name',
              'email_greeting_custom',
              'postal_greeting_custom',
              'addressee_custom',
            ],
            'Household' => [
              'first_name',
              'middle_name',
              'last_name',
              'formal_title',
              'job_title',
              'gender_id',
              'prefix_id',
              'suffix_id',
              'birth_date',
              'organization_name',
              'legal_name',
              'legal_identifier',
              'sic_code',
              'home_URL',
              'is_deceased',
              'deceased_date',
              'current_employer',
              'email_greeting_custom',
              'postal_greeting_custom',
              'addressee_custom',
              'prefix_id',
              'suffix_id',
            ],
            'Organization' => [
              'first_name',
              'middle_name',
              'last_name',
              'formal_title',
              'job_title',
              'gender_id',
              'prefix_id',
              'suffix_id',
              'birth_date',
              'household_name',
              'email_greeting_custom',
              'postal_greeting_custom',
              'prefix_id',
              'suffix_id',
              'gender_id',
              'addressee_custom',
              'is_deceased',
              'deceased_date',
              'current_employer',
            ],
          ];
          foreach ($commonValues[$contactType] as $value) {
            unset($fields[$value]);
          }
        }

        Civi::cache('fields')->set($cacheKeyString, $fields);
      }
      self::$_exportableFields[$cacheKeyString] = $fields;
    }

    if (!$status) {
      $fields = self::$_exportableFields[$cacheKeyString];
    }
    else {
      $fields = array_merge(['' => ['title' => ts('- Contact Fields -')]],
        self::$_exportableFields[$cacheKeyString]
      );
    }

    return $fields;
  }

  /**
   * Get the all contact details (Hierarchical).
   *
   * @param int $contactId
   *   Contact id.
   * @param array $fields
   *   Fields array.
   *
   * @return array
   *   Contact details
   */
  public static function getHierContactDetails($contactId, $fields) {
    $params = [['contact_id', '=', $contactId, 0, 0]];

    $returnProperties = self::makeHierReturnProperties($fields, $contactId);

    // We don't know the contents of return properties, but we need the lower
    // level ids of the contact so add a few fields.
    $returnProperties['first_name'] = 1;
    $returnProperties['organization_name'] = 1;
    $returnProperties['household_name'] = 1;
    $returnProperties['contact_type'] = 1;
    $returnProperties['contact_sub_type'] = 1;
    [$query] = CRM_Contact_BAO_Query::apiQuery($params, $returnProperties);
    return $query;
  }

  /**
   * Given a set of flat profile style field names, create a hierarchy.
   *
   * This is for the query to use, create the right sql.
   *
   * @param array $fields
   * @param int $contactId
   *   Contact id.
   *
   * @return array
   *   A hierarchical property tree if appropriate
   */
  public static function &makeHierReturnProperties($fields, $contactId = NULL) {
    $locationTypes = CRM_Core_BAO_Address::buildOptions('location_type_id', 'validate');

    $returnProperties = [];

    $multipleFields = ['website' => 'url'];
    foreach ($fields as $name => $dontCare) {
      if (str_contains($name, '-')) {
        [$fieldName, $id, $type] = CRM_Utils_System::explode('-', $name, 3);

        if (!in_array($fieldName, $multipleFields)) {
          if ($id == 'Primary') {
            $locationTypeName = 1;
          }
          else {
            $locationTypeName = $locationTypes[$id] ?? NULL;
            if (!$locationTypeName) {
              continue;
            }
          }

          if (empty($returnProperties['location'])) {
            $returnProperties['location'] = [];
          }
          if (empty($returnProperties['location'][$locationTypeName])) {
            $returnProperties['location'][$locationTypeName] = [];
            $returnProperties['location'][$locationTypeName]['location_type'] = $id;
          }
          if (in_array($fieldName, [
            'phone',
            'im',
            'email',
            'openid',
            'phone_ext',
          ])) {
            if ($type) {
              $returnProperties['location'][$locationTypeName][$fieldName . '-' . $type] = 1;
            }
            else {
              $returnProperties['location'][$locationTypeName][$fieldName] = 1;
            }
          }
          elseif (substr($fieldName, 0, 14) === 'address_custom') {
            $returnProperties['location'][$locationTypeName][substr($fieldName, 8)] = 1;
          }
          else {
            $returnProperties['location'][$locationTypeName][$fieldName] = 1;
          }
        }
        else {
          $returnProperties['website'][$id][$fieldName] = 1;
        }
      }
      else {
        $returnProperties[$name] = 1;
      }
    }

    return $returnProperties;
  }

  /**
   * Return the primary location type of a contact.
   *
   * $params int     $contactId contact_id
   * $params boolean $isPrimaryExist if true, return primary contact location type otherwise null
   * $params boolean $skipDefaultPrimary if true, return primary contact location type otherwise null
   *
   * @param int $contactId
   * @param bool $skipDefaultPrimary
   * @param string|null $block
   *
   * @return int|NULL
   *   $locationType location_type_id
   */
  public static function getPrimaryLocationType($contactId, $skipDefaultPrimary = FALSE, $block = NULL) {
    if ($block) {
      $entityBlock = ['contact_id' => $contactId];
      $blocks = CRM_Core_BAO_Location::getValues($entityBlock);
      foreach ($blocks[$block] as $key => $value) {
        if (!empty($value['is_primary'])) {
          $locationType = $value['location_type_id'] ?? NULL;
        }
      }
    }
    else {
      $query = "
SELECT
 IF ( civicrm_email.location_type_id IS NULL,
    IF ( civicrm_address.location_type_id IS NULL,
        IF ( civicrm_phone.location_type_id IS NULL,
           IF ( civicrm_im.location_type_id IS NULL,
               IF ( civicrm_openid.location_type_id IS NULL, null, civicrm_openid.location_type_id)
           ,civicrm_im.location_type_id)
        ,civicrm_phone.location_type_id)
     ,civicrm_address.location_type_id)
  ,civicrm_email.location_type_id)  as locationType
FROM civicrm_contact
     LEFT JOIN civicrm_email   ON ( civicrm_email.is_primary   = 1 AND civicrm_email.contact_id = civicrm_contact.id )
     LEFT JOIN civicrm_address ON ( civicrm_address.is_primary = 1 AND civicrm_address.contact_id = civicrm_contact.id)
     LEFT JOIN civicrm_phone   ON ( civicrm_phone.is_primary   = 1 AND civicrm_phone.contact_id = civicrm_contact.id)
     LEFT JOIN civicrm_im      ON ( civicrm_im.is_primary      = 1 AND civicrm_im.contact_id = civicrm_contact.id)
     LEFT JOIN civicrm_openid  ON ( civicrm_openid.is_primary  = 1 AND civicrm_openid.contact_id = civicrm_contact.id)
WHERE  civicrm_contact.id = %1 ";

      $params = [1 => [$contactId, 'Integer']];

      $dao = CRM_Core_DAO::executeQuery($query, $params);

      $locationType = NULL;
      if ($dao->fetch()) {
        $locationType = $dao->locationType;
      }
    }
    if (isset($locationType)) {
      return $locationType;
    }
    elseif ($skipDefaultPrimary) {
      // if there is no primary contact location then return null
      return NULL;
    }
    else {
      // if there is no primart contact location, then return default
      // location type of the system
      $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
      return $defaultLocationType->id;
    }
  }

  /**
   * Get the display name, primary email and location type of a contact.
   *
   * @param int $id
   *   Id of the contact.
   *
   * @return array
   *   Array of display_name, email if found, do_not_email or (null,null,null)
   */
  public static function getContactDetails($id) {
    // check if the contact type
    $contactType = self::getContactType($id);

    $nameFields = ($contactType == 'Individual') ? "civicrm_contact.first_name, civicrm_contact.last_name, civicrm_contact.display_name" : "civicrm_contact.display_name";

    $sql = "
SELECT $nameFields, civicrm_email.email, civicrm_contact.do_not_email, civicrm_email.on_hold, civicrm_contact.is_deceased
FROM   civicrm_contact LEFT JOIN civicrm_email ON (civicrm_contact.id = civicrm_email.contact_id)
WHERE  civicrm_contact.id = %1
ORDER BY civicrm_email.is_primary DESC";
    $params = [1 => [$id, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if ($dao->fetch()) {
      if ($contactType == 'Individual') {
        if ($dao->first_name || $dao->last_name) {
          $name = "{$dao->first_name} {$dao->last_name}";
        }
        else {
          $name = $dao->display_name;
        }
      }
      else {
        $name = $dao->display_name;
      }
      $email = $dao->email;
      $doNotEmail = (bool) $dao->do_not_email;
      $onHold = (bool) $dao->on_hold;
      $isDeceased = (bool) $dao->is_deceased;
      return [$name, $email, $doNotEmail, $onHold, $isDeceased];
    }
    return [NULL, NULL, NULL, NULL, NULL];
  }

  /**
   * Add/edit/register contacts through profile.
   *
   * @param array $params
   *   Array of profile fields to be edited/added.
   * @param array $fields
   *   Array of fields from UFGroup.
   * @param int $contactID
   *   Id of the contact to be edited/added.
   * @param int $addToGroupID
   *   Specifies the default group to which contact is added.
   * @param int $ufGroupId
   *   Uf group id (profile id).
   * @param string $ctype
   * @param bool $visibility
   *   Basically lets us know where this request is coming from.
   *                                if via a profile from web, we restrict what groups are changed
   *
   * @return int
   *   contact id created/edited
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function createProfileContact(
    &$params,
    $fields = [],
    $contactID = NULL,
    $addToGroupID = NULL,
    $ufGroupId = NULL,
    $ctype = NULL,
    $visibility = FALSE
  ) {
    // add ufGroupID to params array ( CRM-2012 )
    if ($ufGroupId) {
      $params['uf_group_id'] = $ufGroupId;
    }
    self::addBillingNameFieldsIfOtherwiseNotSet($params);

    // If a user has logged in, or accessed via a checksum
    // Then deliberately 'blanking' a value in the profile should remove it from their record
    $session = CRM_Core_Session::singleton();
    $params['updateBlankLocInfo'] = TRUE;
    if (($session->get('authSrc') & (CRM_Core_Permission::AUTH_SRC_CHECKSUM + CRM_Core_Permission::AUTH_SRC_LOGIN)) == 0) {
      $params['updateBlankLocInfo'] = FALSE;
    }

    if ($contactID) {
      $editHook = TRUE;
      CRM_Utils_Hook::pre('edit', 'Profile', $contactID, $params);
    }
    else {
      $editHook = FALSE;
      CRM_Utils_Hook::pre('create', 'Profile', NULL, $params);
    }

    [$data, $contactDetails] = self::formatProfileContactParams($params, $fields, $contactID, $ufGroupId, $ctype);

    // manage is_opt_out
    if (array_key_exists('is_opt_out', $fields) && array_key_exists('is_opt_out', $params)) {
      $wasOptOut = $contactDetails['is_opt_out'] ?? FALSE;
      $isOptOut = $params['is_opt_out'];
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

    $contact = self::create($data);

    // contact is null if the profile does not have any contact fields
    if ($contact) {
      $contactID = $contact->id;
    }

    if (empty($contactID)) {
      throw new CRM_Core_Exception('Cannot proceed without a valid contact id');
    }

    // Process group and tag
    // @todo Contact::create also calls addContactsToGroup/removeContactsToGroup
    //   Remove from here and use the existing functionality in Contact::create
    if (isset($params['group'])) {
      $method = 'Admin';
      // this for sure means we are coming in via profile since i added it to fix
      // removing contacts from user groups -- lobo
      if ($visibility) {
        $method = 'Web';
      }
      $groupParams = $params['group'] ?? [];
      $contactIds = [$contactID];
      $contactGroup = [];

      if ($contactID) {
        $contactGroupList = CRM_Contact_BAO_GroupContact::getContactGroup($contactID, 'Added',
          NULL, FALSE, $visibility
        );
        if (is_array($contactGroupList)) {
          foreach ($contactGroupList as $key) {
            $groupId = $key['group_id'];
            $contactGroup[$groupId] = $groupId;
          }
        }
      }
      // get the list of all the groups
      $allGroup = CRM_Contact_BAO_GroupContact::getGroupList(0, $visibility);

      // check which values has to be add/remove contact from group
      foreach ($allGroup as $key => $varValue) {
        if (!empty($groupParams[$key]) && !array_key_exists($key, $contactGroup)) {
          // add contact to group
          CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $key, $method);
        }
        elseif (empty($groupParams[$key]) && array_key_exists($key, $contactGroup)) {
          // remove contact from group
          CRM_Contact_BAO_GroupContact::removeContactsFromGroup($contactIds, $key, $method);
        }
      }
    }

    if (!empty($fields['tag']) && array_key_exists('tag', $params)) {
      // Convert comma separated form values from select2 v3
      $tags = is_array($params['tag']) ? $params['tag'] : array_fill_keys(array_filter(explode(',', $params['tag'])), 1);
      CRM_Core_BAO_EntityTag::create($tags, 'civicrm_contact', $contactID);
    }

    // to add profile in default group
    // @todo merge this with code above which also calls addContactsToGroup
    if (is_array($addToGroupID)) {
      $contactIds = [$contactID];
      foreach ($addToGroupID as $groupId) {
        CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId);
      }
    }
    elseif ($addToGroupID) {
      $contactIds = [$contactID];
      CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $addToGroupID);
    }

    if ($editHook) {
      CRM_Utils_Hook::post('edit', 'Profile', $contactID, $params);
    }
    else {
      CRM_Utils_Hook::post('create', 'Profile', $contactID, $params);
    }
    return (int) $contactID;
  }

  /**
   * Format profile contact parameters.
   *
   * @param array $params
   * @param array $fields
   * @param int|null $contactID
   * @param int|null $ufGroupId
   * @param string|null $ctype
   * @param bool $skipCustom
   *
   * @return array
   */
  public static function formatProfileContactParams(
    &$params,
    $fields,
    $contactID = NULL,
    $ufGroupId = NULL,
    $ctype = NULL,
    $skipCustom = FALSE
  ) {

    $data = $contactDetails = [];

    // get the contact details (hier)
    if ($contactID) {
      $details = self::getHierContactDetails($contactID, $fields);

      $contactDetails = $details[$contactID];
      $data['contact_type'] = $contactDetails['contact_type'] ?? NULL;
      $data['contact_sub_type'] = $contactDetails['contact_sub_type'] ?? NULL;
    }
    else {
      //we should get contact type only if contact
      if ($ufGroupId) {
        $data['contact_type'] = CRM_Core_BAO_UFField::getProfileType($ufGroupId, TRUE, FALSE, TRUE);

        //special case to handle profile with only contact fields
        if ($data['contact_type'] == 'Contact') {
          $data['contact_type'] = 'Individual';
        }
        elseif (CRM_Contact_BAO_ContactType::isaSubType($data['contact_type'])) {
          $data['contact_type'] = CRM_Contact_BAO_ContactType::getBasicType($data['contact_type']);
        }
      }
      elseif ($ctype) {
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
      $primaryLocationType = self::getPrimaryLocationType($contactID);
    }
    else {
      $defaultLocation = CRM_Core_BAO_LocationType::getDefault();
      $defaultLocationId = $defaultLocation->id;
    }

    $billingLocationTypeId = CRM_Core_BAO_LocationType::getBilling();

    $blocks = ['email', 'phone', 'im', 'openid'];

    $multiplFields = ['url'];
    // prevent overwritten of formatted array, reset all block from
    // params if it is not in valid format (since import pass valid format)
    foreach ($blocks as $blk) {
      if (array_key_exists($blk, $params) &&
        !is_array($params[$blk])
      ) {
        unset($params[$blk]);
      }
    }

    $primaryPhoneLoc = NULL;
    $session = CRM_Core_Session::singleton();
    foreach ($params as $key => $value) {
      [$fieldName, $locTypeId, $typeId] = CRM_Utils_System::explode('-', $key, 3);

      if ($locTypeId == 'Primary') {
        if ($contactID) {
          if (in_array($fieldName, $blocks)) {
            $locTypeId = self::getPrimaryLocationType($contactID, FALSE, $fieldName);
          }
          else {
            $locTypeId = self::getPrimaryLocationType($contactID, FALSE, 'address');
          }
          $primaryLocationType = $locTypeId;
        }
        else {
          $locTypeId = $defaultLocationId;
        }
      }

      if (is_numeric($locTypeId) &&
        !in_array($fieldName, $multiplFields) &&
        substr($fieldName, 0, 7) != 'custom_'
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

        $blockName = self::getLocationEntityForKey($fieldName);

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

        if (in_array($fieldName, ['phone'])) {
          if ($typeId) {
            $data['phone'][$loc]['phone_type_id'] = $typeId;
          }
          else {
            $data['phone'][$loc]['phone_type_id'] = '';
          }
          $data['phone'][$loc]['phone'] = $value;

          //special case to handle primary phone with different phone types
          // in this case we make first phone type as primary
          if (isset($data['phone'][$loc]['is_primary']) && !$primaryPhoneLoc) {
            $primaryPhoneLoc = $loc;
          }

          if ($loc != $primaryPhoneLoc) {
            unset($data['phone'][$loc]['is_primary']);
          }
        }
        elseif ($fieldName == 'email') {
          // This bit of code ensures is_primary is set.
          // It probably dates back to when the BAO
          // could not be relied upon to manage
          // that at least one email had is_primary
          // & would ideally be removed.
          $data['email'][$loc]['email'] = $value;
          if (empty($contactID)) {
            $hasPrimary = FALSE;
            foreach ($data['email'] ?? [] as $email) {
              if (!empty($email['is_primary'])) {
                $hasPrimary = TRUE;
              }
            }
            if (!$hasPrimary) {
              $data['email'][$loc]['is_primary'] = 1;
            }
          }
        }
        elseif ($fieldName == 'im') {
          if (isset($params[$key . '-provider_id'])) {
            $data['im'][$loc]['provider_id'] = $params[$key . '-provider_id'];
          }
          if (str_contains($key, '-provider_id')) {
            $data['im'][$loc]['provider_id'] = $params[$key];
          }
          else {
            $data['im'][$loc]['name'] = $value;
          }
        }
        elseif ($fieldName == 'openid') {
          $data['openid'][$loc]['openid'] = $value;
        }
        else {
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
          elseif ($fieldName === 'country') {
            // CRM-3393
            if (is_numeric($value) && ((int ) $value) >= 1000
            ) {
              $data['address'][$loc]['country_id'] = $value;
            }
            elseif (empty($value)) {
              $data['address'][$loc]['country_id'] = '';
            }
            else {
              $data['address'][$loc]['country'] = $value;
            }
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
      }
      else {
        if (substr($key, 0, 4) === 'url-') {
          $websiteField = explode('-', $key);
          $data['website'][$websiteField[1]]['website_type_id'] = $websiteField[1];
          $data['website'][$websiteField[1]]['url'] = $value;
        }
        elseif (in_array($key, self::$_greetingTypes, TRUE)) {
          //save email/postal greeting and addressee values if any, CRM-4575
          $data[$key . '_id'] = $value;
        }
        elseif (!$skipCustom && ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key))) {
          // for autocomplete transfer hidden value instead of label
          if ($params[$key] && isset($params[$key . '_id'])) {
            $value = $params[$key . '_id'];
          }

          // we need to append time with date
          if ($params[$key] && isset($params[$key . '_time'])) {
            $value .= ' ' . $params[$key . '_time'];
          }

          // if auth source is not checksum / login && $value is blank, do not proceed - CRM-10128
          if (($session->get('authSrc') & (CRM_Core_Permission::AUTH_SRC_CHECKSUM + CRM_Core_Permission::AUTH_SRC_LOGIN)) == 0 &&
            ($value == '' || !isset($value) || (is_array($value) && empty($value)))
          ) {
            continue;
          }

          $valueId = NULL;
          if (!empty($params['customRecordValues'])) {
            if (is_array($params['customRecordValues']) && !empty($params['customRecordValues'])) {
              foreach ($params['customRecordValues'] as $recId => $customFields) {
                if (is_array($customFields) && !empty($customFields)) {
                  foreach ($customFields as $customFieldName) {
                    if ($customFieldName == $key) {
                      $valueId = $recId;
                      break;
                    }
                  }
                }
              }
            }
          }

          //CRM-13596 - check for contact_sub_type_hidden first
          if (array_key_exists('contact_sub_type_hidden', $params)) {
            $type = $params['contact_sub_type_hidden'];
          }
          else {
            $type = $data['contact_type'];
            if (!empty($data['contact_sub_type'])) {
              $type = CRM_Utils_Array::explodePadded($data['contact_sub_type']);
            }
          }

          CRM_Core_BAO_CustomField::formatCustomField($customFieldId,
            $data['custom'],
            $value,
            $type,
            $valueId,
            $contactID,
            FALSE,
            FALSE
          );
        }
        elseif ($key === 'edit') {
          continue;
        }
        else {
          if ($key === 'location') {
            foreach ($value as $locationTypeId => $field) {
              foreach ($field as $block => $val) {
                if ($block === 'address' && array_key_exists('address_name', $val)) {
                  $value[$locationTypeId][$block]['name'] = $value[$locationTypeId][$block]['address_name'];
                }
              }
            }
          }
          if ($key === 'phone' && isset($params['phone_ext'])) {
            $data[$key] = $value;
            foreach ($value as $cnt => $phoneBlock) {
              if ($params[$key][$cnt]['location_type_id'] == $params['phone_ext'][$cnt]['location_type_id']) {
                $data[$key][$cnt]['phone_ext'] = CRM_Utils_Array::retrieveValueRecursive($params['phone_ext'][$cnt], 'phone_ext');
              }
            }
          }
          elseif (in_array($key, ['nick_name', 'job_title', 'middle_name', 'birth_date', 'gender_id', 'current_employer', 'prefix_id', 'suffix_id'])
            && ($value == '' || !isset($value)) &&
            ($session->get('authSrc') & (CRM_Core_Permission::AUTH_SRC_CHECKSUM + CRM_Core_Permission::AUTH_SRC_LOGIN)) == 0 ||
            ($key === 'current_employer' && empty($params['current_employer']))) {
            // CRM-10128: if auth source is not checksum / login && $value is blank, do not fill $data with empty value
            // to avoid update with empty values
            continue;
          }
          else {
            $data[$key] = $value;
          }
        }
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
   * Find the get contact details.
   *
   * This function does not respect ACLs for now, which might need to be rectified at some
   * stage based on how its used.
   *
   * @param string $mail
   *   Primary email address of the contact.
   * @param string $ctype
   *   Contact type.
   *
   * @return object|null
   *   $dao contact details
   */
  public static function matchContactOnEmail($mail, $ctype = NULL) {
    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $mail = $strtolower(trim($mail));
    $query = "
SELECT     civicrm_contact.id as contact_id,
           civicrm_contact.hash as hash,
           civicrm_contact.contact_type as contact_type,
           civicrm_contact.contact_sub_type as contact_sub_type
FROM       civicrm_contact
INNER JOIN civicrm_email    ON ( civicrm_contact.id = civicrm_email.contact_id )";

    if (Civi::settings()->get('uniq_email_per_site')) {
      // try to find a match within a site (multisite).
      $groups = CRM_Core_BAO_Domain::getChildGroupIds();
      if (!empty($groups)) {
        $query .= "
INNER JOIN civicrm_group_contact gc ON
(civicrm_contact.id = gc.contact_id AND gc.status = 'Added' AND gc.group_id IN (" . implode(',', $groups) . "))";
      }
    }

    $query .= "
WHERE      civicrm_email.email = %1 AND civicrm_contact.is_deleted=0";
    $p = [1 => [$mail, 'String']];

    if ($ctype) {
      $query .= " AND civicrm_contact.contact_type = %3";
      $p[3] = [$ctype, 'String'];
    }

    $query .= " ORDER BY civicrm_email.is_primary DESC";

    $dao = CRM_Core_DAO::executeQuery($query, $p);

    if ($dao->fetch()) {
      return $dao;
    }
    return NULL;
  }

  /**
   * Find the contact details associated with an OpenID.
   *
   * @param string $openId
   *   OpenId of the contact.
   * @param string $ctype
   *   Contact type.
   *
   * @return object|null
   *   $dao contact details
   */
  public static function matchContactOnOpenId($openId, $ctype = NULL) {
    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $openId = $strtolower(trim($openId));
    $query = "
SELECT     civicrm_contact.id as contact_id,
           civicrm_contact.hash as hash,
           civicrm_contact.contact_type as contact_type,
           civicrm_contact.contact_sub_type as contact_sub_type
FROM       civicrm_contact
INNER JOIN civicrm_openid    ON ( civicrm_contact.id = civicrm_openid.contact_id )
WHERE      civicrm_openid.openid = %1";
    $p = [1 => [$openId, 'String']];

    if ($ctype) {
      $query .= " AND civicrm_contact.contact_type = %3";
      $p[3] = [$ctype, 'String'];
    }

    $query .= " ORDER BY civicrm_openid.is_primary DESC";

    $dao = CRM_Core_DAO::executeQuery($query, $p);

    if ($dao->fetch()) {
      return $dao;
    }
    return NULL;
  }

  /**
   * Get primary email of the contact.
   *
   * @param int $contactID
   *   Contact id.
   * @param bool $polite
   *   Whether to only pull an email if it's okay to send to it--that is, if it
   *   is not on_hold and the contact is not do_not_email.
   *
   * @return string
   *   Email address if present else null
   */
  public static function getPrimaryEmail($contactID, $polite = FALSE) {
    // fetch the primary email
    $query = "
   SELECT civicrm_email.email as email
     FROM civicrm_contact
LEFT JOIN civicrm_email    ON ( civicrm_contact.id = civicrm_email.contact_id )
    WHERE civicrm_email.is_primary = 1
      AND civicrm_contact.id = %1";
    if ($polite) {
      $query .= '
      AND civicrm_contact.do_not_email = 0
      AND civicrm_email.on_hold = 0';
    }
    $p = [1 => [$contactID, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $p);

    $email = NULL;
    if ($dao->fetch()) {
      $email = $dao->email;
    }
    return $email;
  }

  /**
   * Fetch the object and store the values in the values array.
   *
   * @deprecated avoid this function, no planned removed at this stage as there
   * are still core callers.
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   *
   * @return CRM_Contact_BAO_Contact|null
   *   The found object or null
   */
  public static function getValues($params, &$values) {
    $contact = new CRM_Contact_BAO_Contact();

    $contact->copyValues($params);

    if ($contact->find(TRUE)) {

      CRM_Core_DAO::storeValues($contact, $values);

      $privacy = [];
      foreach (self::$_commPrefs as $name) {
        if (isset($contact->$name)) {
          $privacy[$name] = $contact->$name;
        }
      }

      if (!empty($privacy)) {
        $values['privacy'] = $privacy;
      }

      // communication Prefferance
      $preffComm = $comm = [];
      $comm = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        ($contact->preferred_communication_method ?? '')
      );
      foreach ($comm as $value) {
        $preffComm[$value] = 1;
      }
      $temp = ['preferred_communication_method' => $contact->preferred_communication_method];

      $names = [
        'preferred_communication_method' => [
          'newName' => 'preferred_communication_method_display',
          'groupName' => 'preferred_communication_method',
        ],
      ];

      // @todo This can be figured out from metadata & we can avoid the uncached query.
      CRM_Core_OptionGroup::lookupValues($temp, $names, FALSE);

      $values['preferred_communication_method'] = $preffComm;
      $values['preferred_communication_method_display'] = $temp['preferred_communication_method_display'] ?? NULL;

      $values['preferred_language'] = empty($contact->preferred_language) ? NULL : CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', 'preferred_language', $contact->preferred_language);

      // Calculating Year difference
      if ($contact->birth_date) {
        $birthDate = CRM_Utils_Date::customFormat($contact->birth_date, '%Y%m%d');
        if ($birthDate < date('Ymd')) {
          $deceasedDate = NULL;
          if (!empty($contact->is_deceased) && !empty($contact->deceased_date)) {
            $deceasedDate = $contact->deceased_date;
          }
          $age = CRM_Utils_Date::calculateAge($birthDate, $deceasedDate);
          $values['age']['y'] = $age['years'] ?? NULL;
          $values['age']['m'] = $age['months'] ?? NULL;
        }
      }

      $contact->contact_id = $contact->id;

      return $contact;
    }
    return NULL;
  }

  /**
   * Provides counts for the contact summary tabs.
   *
   * @param string $type
   *   Type of record to count.
   * @param int $contactId
   *   Input contact id.
   * @param string|null $tableName
   *   Deprecated - do not use
   *
   * @return int|false
   *   total number in database
   */
  public static function getCountComponent(string $type, int $contactId, ?string $tableName = NULL) {
    if ($tableName) {
      // TODO: Fix LineItemEditor extension to not use this function, then enable warning
      // CRM_Core_Error::deprecatedWarning('Deprecated argument $tableName passed to ' . __CLASS__ . '::' . __FUNCTION__);
    }
    switch ($type) {
      case 'tag':
        return CRM_Core_BAO_EntityTag::getContactTags($contactId, TRUE);

      case 'rel':
        $result = CRM_Contact_BAO_Relationship::getRelationship($contactId,
          CRM_Contact_BAO_Relationship::CURRENT,
          0, 1, 0,
          NULL, NULL,
          TRUE
        );
        return $result;

      case 'group':
        return CRM_Contact_BAO_GroupContact::getContactGroup($contactId, "Added", NULL, TRUE, FALSE, FALSE, TRUE, NULL, TRUE);

      case 'log':
        if (CRM_Core_BAO_Log::useLoggingReport()) {
          return FALSE;
        }
        return CRM_Core_BAO_Log::getContactLogCount($contactId);

      case 'note':
        return CRM_Core_BAO_Note::getContactNoteCount($contactId);

      case 'contribution':
        if (CRM_Core_Component::isEnabled('CiviContribute')) {
          return CRM_Contribute_BAO_Contribution::contributionCount($contactId);
        }
        return FALSE;

      case 'membership':
        if (CRM_Core_Component::isEnabled('CiviMember')) {
          return CRM_Member_BAO_Membership::getContactMembershipCount((int) $contactId, TRUE);
        }
        return FALSE;

      case 'participant':
        return CRM_Event_BAO_Participant::getContactParticipantCount($contactId);

      case 'pledge':
        return CRM_Pledge_BAO_Pledge::getContactPledgeCount($contactId);

      case 'case':
        return CRM_Case_BAO_Case::caseCount($contactId);

      case 'activity':
        $excludeCaseActivities = (CRM_Core_Component::isEnabled('CiviCase') && !\Civi::settings()->get('civicaseShowCaseActivities'));
        $activityApi = \Civi\Api4\Activity::get(TRUE)
          ->selectRowCount()
          ->addJoin('ActivityContact AS activity_contact', 'INNER')
          ->addWhere('activity_contact.contact_id', '=', $contactId)
          ->addWhere('is_test', '=', FALSE)
          ->addGroupBy('id');
        if ($excludeCaseActivities) {
          $activityApi->addWhere('case_id', 'IS EMPTY');
        }
        return $activityApi->execute()->count();

      case 'mailing':
        $params = ['contact_id' => $contactId];
        return CRM_Mailing_BAO_Mailing::getContactMailingsCount($params);

      default:
        if (!$tableName) {
          $custom = explode('_', $type);
          $tableName = CRM_Core_BAO_CustomGroup::getGroup(['id' => $custom[1]])['table_name'];
        }
        $queryString = "SELECT count(id) FROM $tableName WHERE entity_id = $contactId";
        return (int) CRM_Core_DAO::singleValueQuery($queryString);
    }
  }

  /**
   * Update contact greetings if an update has resulted in a custom field change.
   *
   * @param array $updatedFields
   *   Array of fields that have been updated e.g array('first_name', 'prefix_id', 'custom_2');
   * @param array $contactParams
   *   Parameters known about the contact. At minimum array('contact_id' => x).
   *   Fields in this array will take precedence over DB fields (so far only
   *   in the case of greeting id fields).
   */
  public static function updateGreetingsOnTokenFieldChange($updatedFields, $contactParams) {
    $contactID = $contactParams['contact_id'];
    CRM_Contact_BAO_Contact::ensureGreetingParamsAreSet($contactParams);
    $tokens = CRM_Contact_BAO_Contact_Utils::getTokensRequiredForContactGreetings($contactParams);
    if (!empty($tokens['all']['contact'])) {
      $affectedTokens = array_intersect_key($updatedFields[$contactID], array_flip($tokens['all']['contact']));
      if (!empty($affectedTokens)) {
        // @todo this is still reloading the whole contact -fix to be more selective & use pre-loaded.
        $contact = new CRM_Contact_BAO_Contact();
        $contact->id = $contactID;
        $contact->find(TRUE);
        CRM_Contact_BAO_Contact::processGreetings($contact);
      }
    }
  }

  /**
   * Process greetings and cache.
   *
   * @param \CRM_Contact_DAO_Contact $contact
   *   Contact object after save.
   */
  public static function processGreetings(CRM_Contact_DAO_Contact $contact): void {

    $greetings = array_filter([
      'email_greeting_display' => self::getTemplateForGreeting('email_greeting', $contact),
      'postal_greeting_display' => self::getTemplateForGreeting('postal_greeting', $contact),
      'addressee_display' => self::getTemplateForGreeting('addressee', $contact),
    ]);
    if (empty($greetings)) {
      return;
    }
    // A DAO fetch here is more efficient than looking up
    // values in the token processor - this may be substantially improved by
    // https://github.com/civicrm/civicrm-core/pull/24294 and
    // https://github.com/civicrm/civicrm-core/pull/24156 and could be re-tested
    // in future but tests also 'expect' it to be populated.
    if ($contact->_query !== FALSE) {
      $contact->find(TRUE);
    }
    // We can't use the DAO store method as it filters out NULL keys.
    // Leaving NULL keys in is important as the token processor will
    // do DB lookups to find the data if the keys are not set.
    // We could just about skip this & just cast to an array - except create
    // adds in `phone` and `email`
    // in a weird & likely obsolete way....
    $contactArray = array_intersect_key((array) $contact, $contact->fields());
    // blech
    $contactArray = array_map(function($v) {
      return $v === 'null' ? NULL : $v;
    }, $contactArray);
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'smarty' => TRUE,
      'class' => __CLASS__,
      'schema' => ['contactId'],
    ]);
    $tokenProcessor->addRow(['contactId' => $contact->id, 'contact' => (array) $contactArray]);
    foreach ($greetings as $greetingKey => $greetingString) {
      $tokenProcessor->addMessage($greetingKey, $greetingString, 'text/plain');
    }

    $tokenProcessor->evaluate();
    $row = $tokenProcessor->getRow(0);
    foreach ($greetings as $greetingKey => $greetingString) {
      $parsedGreeting = CRM_Core_DAO::escapeString(CRM_Utils_String::stripSpaces($row->render($greetingKey)));
      // Check to see if the parsed greeting already matches what is stored in the database. If it is different add in update Query
      if ($contactArray[$greetingKey] !== $parsedGreeting) {
        $updateQueryString[] = " $greetingKey = '$parsedGreeting'";
      }
    }

    if (!empty($updateQueryString)) {
      $updateQueryString = implode(',', $updateQueryString);
      CRM_Core_DAO::executeQuery("UPDATE civicrm_contact SET $updateQueryString WHERE id = {$contact->id}");
    }
  }

  /**
   * Retrieve loc block ids w/ given condition.
   *
   * @param int $contactId
   *   Contact id.
   * @param array $criteria
   *   Key => value pair which should be.
   *                              fulfill by return record ids.
   * @param string $condOperator
   *   Operator use for grouping multiple conditions.
   *
   * @return array
   *   loc block ids which fulfill condition.
   */
  public static function getLocBlockIds($contactId, $criteria = [], $condOperator = 'AND') {
    $locBlockIds = [];
    if (!$contactId) {
      return $locBlockIds;
    }

    foreach (['Email', 'OpenID', 'Phone', 'Address', 'IM'] as $block) {
      $name = strtolower($block);
      $className = "CRM_Core_DAO_$block";
      $blockDAO = new $className();

      // build the condition.
      if (is_array($criteria)) {
        $fields = $blockDAO->fields();
        $conditions = [];
        foreach ($criteria as $field => $value) {
          if (array_key_exists($field, $fields)) {
            $cond = "( $field = $value )";
            // value might be zero or null.
            if (!$value || strtolower($value) === 'null') {
              $cond = "( $field = 0 OR $field IS NULL )";
            }
            $conditions[] = $cond;
          }
        }
        if (!empty($conditions)) {
          $blockDAO->whereAdd(implode(" $condOperator ", $conditions));
        }
      }

      $blockDAO->contact_id = $contactId;
      $blockDAO->find();
      while ($blockDAO->fetch()) {
        $locBlockIds[$name][] = $blockDAO->id;
      }
    }

    return $locBlockIds;
  }

  /**
   * Build context menu items.
   *
   * @param int $contactId
   *
   * @return array
   *   Array of context menu for logged in user.
   */
  public static function contextMenu($contactId = NULL) {
    $menu = [
      'view' => [
        'title' => ts('View Contact'),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        'ref' => 'view-contact',
        'class' => 'no-popup',
        'key' => 'view',
        'permissions' => ['view all contacts'],
      ],
      'add' => [
        'title' => ts('Edit Contact'),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        'ref' => 'edit-contact',
        'class' => 'no-popup',
        'key' => 'add',
        'permissions' => ['edit all contacts'],
      ],
      'delete' => [
        'title' => ts('Delete Contact'),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        'ref' => 'delete-contact',
        'key' => 'delete',
        'permissions' => ['access deleted contacts', 'delete contacts'],
      ],
      'contribution' => [
        'title' => ts('Add Contribution'),
        'weight' => 5,
        'ref' => 'new-contribution',
        'key' => 'contribution',
        'tab' => 'contribute',
        'component' => 'CiviContribute',
        'href' => CRM_Utils_System::url('civicrm/contact/view/contribution',
          'reset=1&action=add&context=contribution'
        ),
        'permissions' => [
          'access CiviContribute',
          'edit contributions',
        ],
      ],
      'participant' => [
        'title' => ts('Register for Event'),
        'weight' => 10,
        'ref' => 'new-participant',
        'key' => 'participant',
        'tab' => 'participant',
        'component' => 'CiviEvent',
        'href' => CRM_Utils_System::url('civicrm/contact/view/participant', 'reset=1&action=add&context=participant'),
        'permissions' => [
          'access CiviEvent',
          'edit event participants',
        ],
      ],
      'activity' => [
        'title' => ts('Record Activity'),
        'weight' => 35,
        'ref' => 'new-activity',
        'key' => 'activity',
        'permissions' => ['edit all contacts'],
      ],
      'pledge' => [
        'title' => ts('Add Pledge'),
        'weight' => 15,
        'ref' => 'new-pledge',
        'key' => 'pledge',
        'tab' => 'pledge',
        'href' => CRM_Utils_System::url('civicrm/contact/view/pledge',
          'reset=1&action=add&context=pledge'
        ),
        'component' => 'CiviPledge',
        'permissions' => [
          'access CiviPledge',
          'edit pledges',
        ],
      ],
      'membership' => [
        'title' => ts('Add Membership'),
        'weight' => 20,
        'ref' => 'new-membership',
        'key' => 'membership',
        'tab' => 'member',
        'component' => 'CiviMember',
        'href' => CRM_Utils_System::url('civicrm/contact/view/membership',
          'reset=1&action=add&context=membership'
        ),
        'permissions' => [
          'access CiviMember',
          'edit memberships',
        ],
      ],
      'case' => [
        'title' => ts('Add Case'),
        'weight' => 25,
        'ref' => 'new-case',
        'key' => 'case',
        'tab' => 'case',
        'component' => 'CiviCase',
        'href' => CRM_Utils_System::url('civicrm/case/add', 'reset=1&action=add&context=case'),
        'permissions' => ['add cases'],
      ],
      'rel' => [
        'title' => ts('Add Relationship'),
        'weight' => 30,
        'ref' => 'new-relationship',
        'key' => 'rel',
        'tab' => 'rel',
        'href' => CRM_Utils_System::url('civicrm/contact/view/rel',
          'reset=1&action=add'
        ),
        'permissions' => ['edit all contacts'],
      ],
      'note' => [
        'title' => ts('Add Note'),
        'weight' => 40,
        'ref' => 'new-note',
        'key' => 'note',
        'tab' => 'note',
        'class' => 'medium-popup',
        'href' => CRM_Utils_System::url('civicrm/note',
          'reset=1&action=add&entity_table=civicrm_contact&entity_id=' . $contactId
        ),
        'permissions' => ['edit all contacts'],
      ],
      'email' => [
        'title' => ts('Send an Email'),
        'weight' => 45,
        'ref' => 'new-email',
        'key' => 'email',
        'permissions' => ['view all contacts'],
      ],
      'group' => [
        'title' => ts('Add to Group'),
        'weight' => 50,
        'ref' => 'group-add-contact',
        'key' => 'group',
        'tab' => 'group',
        'permissions' => ['edit groups'],
      ],
      'tag' => [
        'title' => ts('Tag Contact'),
        'weight' => 55,
        'ref' => 'tag-contact',
        'key' => 'tag',
        'tab' => 'tag',
        'permissions' => ['edit all contacts'],
      ],
    ];

    $menu['otherActions'] = [
      'print' => [
        'title' => ts('Print Summary'),
        'description' => ts('Printer-friendly view of this page.'),
        'weight' => 5,
        'ref' => 'crm-contact-print',
        'key' => 'print',
        'tab' => 'print',
        'href' => CRM_Utils_System::url('civicrm/contact/view/print',
          'reset=1&print=1'
        ),
        'class' => 'print',
        'icon' => 'crm-i fa-print',
      ],
      'vcard' => [
        'title' => ts('vCard'),
        'description' => ts('vCard record for this contact.'),
        'weight' => 10,
        'ref' => 'crm-contact-vcard',
        'key' => 'vcard',
        'tab' => 'vcard',
        'href' => CRM_Utils_System::url('civicrm/contact/view/vcard',
          "reset=1"
        ),
        'class' => 'vcard',
        'icon' => 'crm-i fa-list-alt',
      ],
    ];

    if (CRM_Core_Permission::check('access Contact Dashboard')) {
      $menu['otherActions']['dashboard'] = [
        'title' => ts('Contact Dashboard'),
        'description' => ts('Contact Dashboard'),
        'weight' => 15,
        'ref' => 'crm-contact-dashboard',
        'key' => 'dashboard',
        'tab' => 'dashboard',
        'class' => 'dashboard',
        // NOTE: As an alternative you can also build url on CMS specific way
        //  as CRM_Core_Config::singleton()->userSystem->getUserRecordUrl($contactId)
        'href' => CRM_Utils_System::url('civicrm/user', "reset=1&id=$contactId"),
        'icon' => 'crm-i fa-tachometer',
      ];
    }

    if (CRM_Core_Permission::check('delete contacts')) {
      $menu['otherActions']['delete'] = [
        'title' => ts('Delete'),
        'description' => ts('Delete Contact'),
        'weight' => 90,
        'ref' => 'crm-contact-delete',
        'key' => 'delete',
        'tab' => 'delete',
        'class' => 'delete',
        'href' => CRM_Utils_System::url('civicrm/contact/view/delete', "reset=1&delete=1&id=$contactId"),
        'icon' => 'crm-i fa-trash',
      ];
    }

    $uid = CRM_Core_BAO_UFMatch::getUFId($contactId);
    if ($uid) {
      $menu['otherActions']['user-record'] = [
        'title' => ts('User Record'),
        'description' => ts('User Record'),
        'weight' => 20,
        'ref' => 'crm-contact-user-record',
        'key' => 'user-record',
        'tab' => 'user-record',
        'class' => 'user-record',
        'href' => CRM_Core_Config::singleton()->userSystem->getUserRecordUrl($contactId),
        'icon' => 'crm-i fa-user',
      ];
    }
    elseif (CRM_Core_Config::singleton()->userSystem->checkPermissionAddUser()) {
      $menu['otherActions']['user-add'] = [
        'title' => ts('Create User Record'),
        'description' => ts('Create User Record'),
        'weight' => 25,
        'ref' => 'crm-contact-user-add',
        'key' => 'user-add',
        'tab' => 'user-add',
        'class' => 'user-add',
        'href' => CRM_Utils_System::url('civicrm/contact/view/useradd', 'reset=1&action=add&cid=' . $contactId),
        'icon' => 'crm-i fa-user-plus',
      ];
    }

    CRM_Utils_Hook::summaryActions($menu, $contactId);
    //1. check for component is active.
    //2. check for user permissions.
    //3. check for acls.
    //3. edit and view contact are directly accessible to user.

    $aclPermissionedTasks = [
      'view-contact',
      'edit-contact',
      'new-activity',
      'new-email',
      'group-add-contact',
      'tag-contact',
      'delete-contact',
    ];
    $corePermission = CRM_Core_Permission::getPermission();

    $contextMenu = [];
    foreach ($menu as $key => $values) {
      if ($key !== 'otherActions') {

        // user does not have necessary permissions.
        if (!self::checkUserMenuPermissions($aclPermissionedTasks, $corePermission, $values)) {
          continue;
        }
        // build directly accessible action menu.
        if (in_array($values['ref'], ['view-contact', 'edit-contact'])) {
          $contextMenu['primaryActions'][$key] = [
            'title' => $values['title'],
            'ref' => $values['ref'] ?? $key,
            'class' => $values['class'] ?? NULL,
            'key' => $values['key'] ?? $key,
            'weight' => $values['weight'],
          ];
          continue;
        }

        // finally get menu item for -more- action widget.
        while (!empty($contextMenu['moreActions'][$values['weight']])) {
          // Quick & dirty way of handling 2 items with the same weight
          // without clobbering one.
          $values['weight']++;
        }
        $contextMenu['moreActions'][$values['weight']] = [
          'title' => $values['title'],
          'ref' => $values['ref'] ?? $key,
          'href' => $values['href'] ?? NULL,
          'tab' => $values['tab'] ?? NULL,
          'class' => $values['class'] ?? NULL,
          'key' => $values['key'] ?? $key,
          'weight' => $values['weight'],
        ];
      }
      else {
        foreach ($values as $value) {
          // user does not have necessary permissions.
          if (!self::checkUserMenuPermissions($aclPermissionedTasks, $corePermission, $value)) {
            continue;
          }

          // finally get menu item for -more- action widget.
          while (!empty($contextMenu['otherActions'][$value['weight']])) {
            // Quick & dirty way of handling 2 items with the same weight
            // without clobbering one.
            $value['weight']++;
          }
          $contextMenu['otherActions'][$value['weight']] = [
            'title' => $value['title'],
            'ref' => $value['ref'] ?? $key,
            'href' => $value['href'] ?? NULL,
            'tab' => $value['tab'] ?? NULL,
            'class' => $value['class'] ?? NULL,
            'icon' => $value['icon'] ?? NULL,
            'key' => $value['key'] ?? $key,
            'weight' => $value['weight'],
          ];
        }
      }
    }

    ksort($contextMenu['moreActions']);
    ksort($contextMenu['otherActions']);

    return $contextMenu;
  }

  /**
   * Check if user has permissions to access items in action menu.
   *
   * @param array $aclPermissionedTasks
   *   Array containing ACL related tasks.
   * @param string $corePermission
   *   The permission of the user (edit or view or null).
   * @param array $menuOptions
   *   Array containing params of the menu (title, href, etc).
   *
   * @return bool
   *   TRUE if user has all permissions, FALSE if otherwise.
   */
  public static function checkUserMenuPermissions($aclPermissionedTasks, $corePermission, $menuOptions) {
    $componentName = $menuOptions['component'] ?? NULL;

    // if component action - make sure component is enable.
    if ($componentName && !CRM_Core_Component::isEnabled($componentName)) {
      return FALSE;
    }

    // make sure user has all required permissions.
    $hasAllPermissions = FALSE;

    $permissions = $menuOptions['permissions'] ?? NULL;
    if (!is_array($permissions) || empty($permissions)) {
      $hasAllPermissions = TRUE;
    }

    // iterate for required permissions in given permissions array.
    if (!$hasAllPermissions) {
      $hasPermissions = 0;
      foreach ($permissions as $permission) {
        if (CRM_Core_Permission::check($permission)) {
          $hasPermissions++;
        }
      }

      if (count($permissions) == $hasPermissions) {
        $hasAllPermissions = TRUE;
      }

      // if still user does not have required permissions, check acl.
      if (!$hasAllPermissions && $menuOptions['ref'] !== 'delete-contact') {
        if (in_array($menuOptions['ref'], $aclPermissionedTasks) &&
          $corePermission == CRM_Core_Permission::EDIT
        ) {
          $hasAllPermissions = TRUE;
        }
        elseif (in_array($menuOptions['ref'], ['new-email'])) {
          // grant permissions for these tasks.
          $hasAllPermissions = TRUE;
        }
      }
    }

    return $hasAllPermissions;
  }

  /**
   * Retrieve display name of contact that address is shared.
   *
   * This is based on $masterAddressId or $contactId .
   *
   * @param int $masterAddressId
   *   Master id.
   * @param int $contactId
   *   Contact id. (deprecated - do not use)
   *
   * @return string|null
   *   the found display name or null.
   */
  public static function getMasterDisplayName($masterAddressId = NULL, $contactId = NULL) {
    $masterDisplayName = NULL;
    if (!$masterAddressId) {
      return $masterDisplayName;
    }

    $sql = "
   SELECT display_name from civicrm_contact
LEFT JOIN civicrm_address ON ( civicrm_address.contact_id = civicrm_contact.id )
    WHERE civicrm_address.id = " . $masterAddressId;

    $masterDisplayName = CRM_Core_DAO::singleValueQuery($sql);
    return $masterDisplayName;
  }

  /**
   * Get the creation/modification times for a contact.
   *
   * @param int $contactId
   *
   * @return array
   *   Dates - ('created_date' => $, 'modified_date' => $)
   */
  public static function getTimestamps($contactId) {
    $timestamps = CRM_Core_DAO::executeQuery(
      'SELECT created_date, modified_date
      FROM civicrm_contact
      WHERE id = %1',
      [
        1 => [$contactId, 'Integer'],
      ]
    );
    if ($timestamps->fetch()) {
      return [
        'created_date' => $timestamps->created_date,
        'modified_date' => $timestamps->modified_date,
      ];
    }
    else {
      return NULL;
    }
  }

  /**
   * Get a list of triggers for the contact table.
   *
   * @param array $info
   * @param string|null $tableName
   *
   * @see http://issues.civicrm.org/jira/browse/CRM-10554
   *
   * @see hook_civicrm_triggerInfo
   * @see CRM_Core_DAO::triggerRebuild
   */
  public static function triggerInfo(&$info, $tableName = NULL) {

    // Modifications to these records should update the contact timestamps.
    \Civi\Core\SqlTrigger\TimestampTriggers::create('civicrm_contact', 'Contact')
      ->setRelations([
        ['table' => 'civicrm_address', 'column' => 'contact_id'],
        ['table' => 'civicrm_email', 'column' => 'contact_id'],
        ['table' => 'civicrm_im', 'column' => 'contact_id'],
        ['table' => 'civicrm_phone', 'column' => 'contact_id'],
        ['table' => 'civicrm_website', 'column' => 'contact_id'],
      ])
      ->alterTriggerInfo($info, $tableName);

    // Update phone table to populate phone_numeric field
    if (!$tableName || $tableName == 'civicrm_phone') {
      // Define stored sql function needed for phones
      $sqlTriggers = Civi::service('sql_triggers');
      $sqlTriggers->enqueueQuery(self::DROP_STRIP_FUNCTION_43);
      $sqlTriggers->enqueueQuery(self::CREATE_STRIP_FUNCTION_43);
      $info[] = [
        'table' => ['civicrm_phone'],
        'when' => 'BEFORE',
        'event' => ['INSERT', 'UPDATE'],
        'sql' => "\nSET NEW.phone_numeric = civicrm_strip_non_numeric(NEW.phone);\n",
      ];
    }
  }

  /**
   * Check if contact is being used in civicrm_domain based on $contactId.
   *
   * @param int $contactId
   *   Contact id.
   *
   * @return bool
   *   true if present else false.
   */
  public static function checkDomainContact($contactId) {
    if (!$contactId) {
      return FALSE;
    }
    $domainId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', $contactId, 'id', 'contact_id');

    if ($domainId) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Legacy option getter
   *
   * @deprecated
   * @inheritDoc
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    switch ($fieldName) {
      case 'contact_type':
        if ($context == 'search') {
          // CRM-15495 - EntityRef filters and basic search forms expect this format
          // FIXME: Search builder does not
          return CRM_Contact_BAO_ContactType::getSelectElements();
        }
        break;

      // The contact api supports some related entities so we'll honor that by fetching their options
      case 'group_id':
      case 'group':
        return CRM_Contact_BAO_GroupContact::buildOptions('group_id', $context, $props);

      case 'tag_id':
      case 'tag':
        $props['entity_table'] = 'civicrm_contact';
        return CRM_Core_BAO_EntityTag::buildOptions('tag_id', $context, $props);

      case 'state_province_id':
      case 'state_province':
      case 'state_province_name':
      case 'country_id':
      case 'country':
      case 'county_id':
      case 'worldregion':
      case 'worldregion_id':
        return CRM_Core_BAO_Address::buildOptions($fieldName, 'get', $props);

    }
    return parent::buildOptions($fieldName, $context, $props);
  }

  /**
   * Pseudoconstant condition_provider for contact_sub_type field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterContactSubType(string $fieldName, CRM_Utils_SQL_Select $conditions, $params) {
    if (!empty($params['values']['contact_type'])) {
      $conditions->where('parent_id = (SELECT id FROM civicrm_contact_type WHERE name = @contactSubType)', ['contactSubType' => $params['values']['contact_type']]);
    }
  }

  /**
   * Event fired after modifying any entity.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function on_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    // Handle deleting a related entity with is_primary
    $hasPrimary = ['Address', 'Email', 'IM', 'OpenID', 'Phone'];
    if (
      $event->action === 'delete' && $event->id &&
      in_array($event->entity, $hasPrimary) &&
      !empty($event->object->is_primary) &&
      !empty($event->object->contact_id)
    ) {
      $daoClass = CRM_Core_DAO_AllCoreTables::getDAONameForEntity($event->entity);
      $dao = new $daoClass();
      $dao->contact_id = $event->object->contact_id;
      $dao->is_primary = 1;
      // Pick another record to be primary (if one isn't already)
      if (!$dao->find(TRUE)) {
        $dao->is_primary = 0;
        if ($dao->find(TRUE)) {
          $baoClass = CRM_Core_DAO_AllCoreTables::getBAOClassName($daoClass);
          $baoClass::writeRecord(['id' => $dao->id, 'is_primary' => 1]);
        }
      }
    }
  }

  /**
   * Get the template string for the given greeting.
   *
   * @param string $greetingType
   * @param CRM_Contact_DAO_Contact $contact
   *
   * @return string
   */
  private static function getTemplateForGreeting(string $greetingType, CRM_Contact_DAO_Contact $contact): string {
    $customFieldName = $greetingType . '_custom';
    if (!CRM_Utils_System::isNull($contact->$customFieldName)) {
      return $contact->$customFieldName;
    }
    $idField = $greetingType . '_id';
    if (!is_numeric($contact->$idField)) {
      return '';
    }
    $filter = [
      'contact_type' => $contact->contact_type,
      'greeting_type' => $greetingType,
    ];
    return CRM_Core_PseudoConstant::greeting($filter)[$contact->$idField] ?? '';
  }

  /**
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    // We always return an array with these keys, even if they are empty,
    // because this tells the query builder that we have considered these fields for acls
    $clauses = [
      'id' => (array) CRM_Contact_BAO_Contact_Permission::cacheSubquery(),
      'is_deleted' => CRM_Core_Permission::check('access deleted contacts') ? [] : ['!= 1'],
    ];
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
    return $clauses;
  }

  /**
   * Get any existing duplicate contacts based on the input parameters.
   *
   * @param array $input
   *   Input parameters to be matched.
   * @param string $contactType
   * @param string $rule
   *  - Supervised
   *  - Unsupervised
   * @param $excludedContactIDs
   *   An array of ids not to be included in the results.
   * @param bool $checkPermissions
   * @param int $ruleGroupID
   *   ID of the rule group to be used if an override is desirable.
   * @param array $contextParams
   *   The context if relevant, eg. ['event_id' => X]
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getDuplicateContacts(array $input, string $contactType, string $rule = 'Unsupervised', $excludedContactIDs = [], $checkPermissions = TRUE, $ruleGroupID = NULL, $contextParams = []): array {
    $dedupeParams = CRM_Dedupe_Finder::formatParams($input, $contactType);
    $dedupeParams['check_permission'] = $checkPermissions;
    $dedupeParams['contact_type'] = $contactType;
    $dedupeParams['rule'] = $rule;
    $dedupeParams['rule_group_id'] = $ruleGroupID;
    $dedupeParams['excluded_contact_ids'] = $excludedContactIDs;
    return self::findDuplicates($dedupeParams, $contextParams ?: []);
  }

  /**
   * @param array $dedupeParams
   * @param array $contextParams
   * @return array
   * @throws CRM_Core_Exception
   */
  public static function findDuplicates(array $dedupeParams, array $contextParams = []): array {
    $dedupeResults = [
      'ids' => [],
      'handled' => FALSE,
    ];
    $checkPermission = $dedupeParams['check_permission'] ?? TRUE;
    // This may no longer be required - see https://github.com/civicrm/civicrm-core/pull/13176
    $dedupeParams = array_filter($dedupeParams);
    if (empty($dedupeParams)) {
      // If $params is empty there is zero reason to proceed.
      return [];
    }
    if (empty($dedupeParams['rule_group_id'])) {
      $dedupeParams['rule_group_id'] = DedupeRuleGroup::get(FALSE)
        ->addWhere('contact_type', '=', $dedupeParams['contact_type'])
        ->addWhere('used', '=', $dedupeParams['rule'])
        ->addSelect('id')
        ->execute()->first()['id'];
    }
    $nonMatchFields = ['contact_type', 'rule', 'excluded_contact_ids', 'rule_group_id', 'check_permission'];
    $matchParams = $dedupeParams['match_params'] ?? array_diff_key($dedupeParams, array_fill_keys($nonMatchFields, TRUE));
    // Although dedupe_params is a += that is not because it might have additional data but
    // rather because the legacy array was less nested (ie everything in match_params was at the top level).
    $dedupeParams += [
      'contact_type' => NULL,
      'rule' => NULL,
      'excluded_contact_ids' => [],
      'check_permission' => (bool) $checkPermission,
      'match_params' => $matchParams,
    ];
    $dedupeParams += $dedupeParams['match_params'];
    CRM_Utils_Hook::findDuplicates($dedupeParams, $dedupeResults, $contextParams);
    return $dedupeResults['ids'] ?? [];
  }

  /**
   * Get the first duplicate contacts based on the input parameters.
   *
   * @param array $input
   *   Input parameters to be matched.
   * @param string $contactType
   * @param string $rule
   *  - Supervised
   *  - Unsupervised
   * @param $excludedContactIDs
   *   An array of ids not to be included in the results.
   * @param bool $checkPermissions
   * @param int $ruleGroupID
   *   ID of the rule group to be used if an override is desirable.
   * @param array $contextParams
   *   The context if relevant, eg. ['event_id' => X]
   *
   * @return int|null
   */
  public static function getFirstDuplicateContact($input, $contactType, $rule = 'Unsupervised', $excludedContactIDs = [], $checkPermissions = TRUE, $ruleGroupID = NULL, $contextParams = []) {
    $ids = self::getDuplicateContacts($input, $contactType, $rule, $excludedContactIDs, $checkPermissions, $ruleGroupID, $contextParams);
    if (empty($ids)) {
      return NULL;
    }
    return $ids[0];
  }

  /**
   * Check if a field is associated with an entity that has a location type.
   *
   * (ie. is an address, phone, email etc field).
   *
   * @param string $fieldTitle
   *   Title of the field (not the name - create a new function for that if required).
   *
   * @return bool
   */
  public static function isFieldHasLocationType($fieldTitle) {
    foreach (CRM_Contact_BAO_Contact::importableFields() as $key => $field) {
      if ($field['title'] === $fieldTitle) {
        return $field['hasLocationType'] ?? NULL;
      }
    }
    return FALSE;
  }

  /**
   * @param array $appendProfiles
   *   Name of profile(s) to append to each link.
   *
   * @return array|false
   */
  public static function getEntityRefCreateLinks($appendProfiles = []) {
    // You'd think that "create contacts" would be the permission to check,
    // But new contact popups are profile forms and those use their own permissions.
    if (!CRM_Core_Permission::check([['profile create', 'profile listings and forms']])) {
      return FALSE;
    }
    $profiles = [];
    foreach (CRM_Contact_BAO_ContactType::basicTypes() as $contactType) {
      $profiles[] = 'new_' . strtolower($contactType);
    }
    $retrieved = civicrm_api3('uf_group', 'get', [
      'name' => ['IN' => array_merge($profiles, (array) $appendProfiles)],
      'is_active' => 1,
    ]);
    $links = $append = [];
    if (!empty($retrieved['values'])) {
      $icons = [
        'individual' => 'fa-user',
        'organization' => 'fa-building',
        'household' => 'fa-home',
      ];
      foreach ($retrieved['values'] as $id => $profile) {
        if (in_array($profile['name'], $profiles)) {
          $links[] = [
            'label' => $profile['title'],
            'url' => CRM_Utils_System::url('civicrm/profile/create', "reset=1&context=dialog&gid=$id",
              NULL, NULL, FALSE, FALSE, TRUE),
            'type' => ucfirst(str_replace('new_', '', $profile['name'])),
            'icon' => $icons[str_replace('new_', '', $profile['name'])] ?? NULL,
          ];
        }
        else {
          $append[] = $id;
        }
      }
      foreach ($append as $id) {
        foreach ($links as &$link) {
          $link['url'] .= ",$id";
        }
      }
    }
    return $links;
  }

  /**
   * @return array
   */
  public static function getEntityRefFilters() {
    return [
      ['key' => 'contact_type', 'value' => ts('Contact Type')],
      ['key' => 'email', 'value' => ts('Email'), 'entity' => 'Email', 'type' => 'text'],
      ['key' => 'group', 'value' => ts('Group'), 'entity' => 'GroupContact'],
      ['key' => 'tag', 'value' => ts('Tag'), 'entity' => 'EntityTag'],
      ['key' => 'city', 'value' => ts('City'), 'type' => 'text', 'entity' => 'Address'],
      ['key' => 'postal_code', 'value' => ts('Postal Code'), 'type' => 'text', 'entity' => 'Address'],
      ['key' => 'state_province', 'value' => ts('State/Province'), 'entity' => 'Address'],
      ['key' => 'country', 'value' => ts('Country'), 'entity' => 'Address'],
      ['key' => 'first_name', 'value' => ts('First Name'), 'type' => 'text', 'condition' => ['contact_type' => 'Individual']],
      ['key' => 'last_name', 'value' => ts('Last Name'), 'type' => 'text', 'condition' => ['contact_type' => 'Individual']],
      ['key' => 'nick_name', 'value' => ts('Nick Name'), 'type' => 'text', 'condition' => ['contact_type' => 'Individual']],
      ['key' => 'organization_name', 'value' => ts('Employer name'), 'type' => 'text', 'condition' => ['contact_type' => 'Individual']],
      ['key' => 'gender_id', 'value' => ts('Gender'), 'condition' => ['contact_type' => 'Individual']],
      ['key' => 'is_deceased', 'value' => ts('Deceased'), 'condition' => ['contact_type' => 'Individual']],
      ['key' => 'external_identifier', 'value' => ts('External ID'), 'type' => 'text'],
      ['key' => 'source', 'value' => ts('Contact Source'), 'type' => 'text'],
    ];
  }

  /**
   * Check contact access.
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $userID = $e->getUserID();

    switch ($e->getActionName()) {
      case 'create':
        $e->setAuthorized(CRM_Core_Permission::check('add contacts', $userID));
        return;

      case 'get':
        $actionType = CRM_Core_Permission::VIEW;
        break;

      case 'delete':
        $actionType = CRM_Core_Permission::DELETE;
        break;

      default:
        $actionType = CRM_Core_Permission::EDIT;
        break;
    }

    $e->setAuthorized(CRM_Contact_BAO_Contact_Permission::allow($record['id'], $actionType, $userID));
  }

  /**
   * Get icon for a particular contact.
   *
   * Example: `CRM_Contact_BAO_Contact::getIcon('Contact', 123)`
   *
   * @param string $entityName
   *   Always "Contact".
   * @param int|null $entityId
   *   Id of the contact.
   * @throws CRM_Core_Exception
   */
  public static function getEntityIcon(string $entityName, ?int $entityId = NULL): ?string {
    $default = parent::getEntityIcon($entityName);
    if (!$entityId) {
      return $default;
    }
    $contactTypes = CRM_Contact_BAO_ContactType::getAllContactTypes();
    $subTypes = CRM_Utils_Array::explodePadded(CRM_Core_DAO::getFieldValue(parent::class, $entityId, 'contact_sub_type'));
    foreach ((array) $subTypes as $subType) {
      if (!empty($contactTypes[$subType]['icon'])) {
        return $contactTypes[$subType]['icon'];
      }
    }
    // If no sub-type icon, lookup contact type
    $contactType = CRM_Core_DAO::getFieldValue(parent::class, $entityId, 'contact_type');
    return $contactTypes[$contactType]['icon'] ?? $default;
  }

}
