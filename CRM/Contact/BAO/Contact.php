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
class CRM_Contact_BAO_Contact extends CRM_Contact_DAO_Contact {

  /**
   * SQL function used to format the phone_numeric field via trigger.
   * @see self::triggerInfo()
   *
   * Note that this is also used by the 4.3 upgrade script.
   * @see CRM_Upgrade_Incremental_php_FourThree
   */
  const DROP_STRIP_FUNCTION_43 = "DROP FUNCTION IF EXISTS civicrm_strip_non_numeric";
  const CREATE_STRIP_FUNCTION_43 = "
    CREATE FUNCTION civicrm_strip_non_numeric(input VARCHAR(255) CHARACTER SET utf8)
      RETURNS VARCHAR(255) CHARACTER SET utf8
      DETERMINISTIC
      NO SQL
    BEGIN
      DECLARE output   VARCHAR(255) CHARACTER SET utf8 DEFAULT '';
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
  static $_commPrefs = array(
    'do_not_phone',
    'do_not_email',
    'do_not_mail',
    'do_not_sms',
    'do_not_trade',
  );

  /**
   * Types of greetings.
   *
   * @var array
   */
  static $_greetingTypes = array(
    'addressee',
    'email_greeting',
    'postal_greeting',
  );

  /**
   * Static field for all the contact information that we can potentially import.
   *
   * @var array
   */
  static $_importableFields = array();

  /**
   * Static field for all the contact information that we can potentially export.
   *
   * @var array
   */
  static $_exportableFields = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

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
   * @return CRM_Contact_BAO_Contact|CRM_Core_Error|NULL
   *   Created or updated contact object or error object.
   *   (error objects are being phased out in favour of exceptions)
   */
  public static function add(&$params) {
    $contact = new CRM_Contact_DAO_Contact();

    if (empty($params)) {
      return NULL;
    }

    // Fix for validate contact sub type CRM-5143.
    if (isset($params['contact_sub_type'])) {
      if (empty($params['contact_sub_type'])) {
        $params['contact_sub_type'] = 'null';
      }
      else {
        if (!CRM_Contact_BAO_ContactType::isExtendsContactType($params['contact_sub_type'],
          $params['contact_type'], TRUE
        )
        ) {
          // we'll need to fix tests to handle this
          // CRM-7925
          CRM_Core_Error::fatal(ts('The Contact Sub Type does not match the Contact type for this record'));
        }
        $params['contact_sub_type'] = CRM_Utils_Array::implodePadded($params['contact_sub_type']);
      }
    }
    else {
      // Reset the value.
      // CRM-101XX.
      $params['contact_sub_type'] = 'null';
    }

    if (isset($params['preferred_communication_method']) && is_array($params['preferred_communication_method'])) {
      CRM_Utils_Array::formatArrayKeys($params['preferred_communication_method']);
      $contact->preferred_communication_method = CRM_Utils_Array::implodePadded($params['preferred_communication_method']);
      unset($params['preferred_communication_method']);
    }

    $defaults = ['source' => CRM_Utils_Array::value('contact_source', $params)];
    if ($params['contact_type'] === 'Organization' && isset($params['organization_name'])) {
      $defaults['display_name'] = $params['organization_name'];
      $defaults['sort_name'] = $params['organization_name'];
    }
    if ($params['contact_type'] === 'Household' && isset($params['household_name'])) {
      $defaults['display_name'] = $params['household_name'];
      $defaults['sort_name'] = $params['household_name'];
    }
    $params = array_merge($defaults, $params);

    $allNull = $contact->copyValues($params);

    $contact->id = CRM_Utils_Array::value('contact_id', $params);

    if ($contact->contact_type == 'Individual') {
      $allNull = FALSE;
      // @todo allow the lines below to be overridden by input or hooks & add tests,
      // as has been done for households and organizations.
      // Format individual fields.
      CRM_Contact_BAO_Individual::format($params, $contact);
    }

    if (strlen($contact->display_name) > 128) {
      $contact->display_name = substr($contact->display_name, 0, 128);
    }
    if (strlen($contact->sort_name) > 128) {
      $contact->sort_name = substr($contact->sort_name, 0, 128);
    }

    $privacy = CRM_Utils_Array::value('privacy', $params);
    if ($privacy &&
      is_array($privacy) &&
      !empty($privacy)
    ) {
      $allNull = FALSE;
      foreach (self::$_commPrefs as $name) {
        $contact->$name = CRM_Utils_Array::value($name, $privacy, FALSE);
      }
    }

    // Since hash was required, make sure we have a 0 value for it (CRM-1063).
    // @todo - does this mean we can remove this block?
    // Fixed in 1.5 by making hash optional, only do this in create mode, not update.
    if ((!array_key_exists('hash', $contact) || !$contact->hash) && !$contact->id) {
      $allNull = FALSE;
      $contact->hash = md5(uniqid(rand(), TRUE));
    }

    // Even if we don't need $employerId, it's important to call getFieldValue() before
    // the contact is saved because we want the existing value to be cached.
    // createCurrentEmployerRelationship() needs the old value not the updated one. CRM-10788
    $employerId = empty($contact->id) ? NULL : CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contact->id, 'employer_id');

    if (!$allNull) {
      $contact->save();

      CRM_Core_BAO_Log::register($contact->id,
        'civicrm_contact',
        $contact->id
      );
    }

    if ($contact->contact_type == 'Individual' && (isset($params['current_employer']) || isset($params['employer_id']))) {
      // Create current employer.
      $newEmployer = !empty($params['employer_id']) ? $params['employer_id'] : CRM_Utils_Array::value('current_employer', $params);

      $newContact = FALSE;
      if (empty($params['contact_id'])) {
        $newContact = TRUE;
      }
      if ($newEmployer) {
        CRM_Contact_BAO_Contact_Utils::createCurrentEmployerRelationship($contact->id, $newEmployer, $employerId, $newContact);
      }
      else {
        if ($employerId) {
          CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($contact->id, $employerId);
        }
      }
    }

    // Update cached employer name.
    if ($contact->contact_type == 'Organization') {
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
   * @todo explain this parameter
   *
   * @throws Exception
   * @return CRM_Contact_BAO_Contact|CRM_Core_Error
   *   Created or updated contribution object. We are deprecating returning an error in
   *   favour of exceptions
   */
  public static function &create(&$params, $fixAddress = TRUE, $invokeHooks = TRUE, $skipDelete = FALSE) {
    $contact = NULL;
    if (empty($params['contact_type']) && empty($params['contact_id'])) {
      return $contact;
    }

    $isEdit = TRUE;
    if ($invokeHooks) {
      if (!empty($params['contact_id'])) {
        CRM_Utils_Hook::pre('edit', $params['contact_type'], $params['contact_id'], $params);
      }
      else {
        CRM_Utils_Hook::pre('create', $params['contact_type'], NULL, $params);
        $isEdit = FALSE;
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
    if (!$contact) {
      // Not dying here is stupid, since we get into weird situation and into a bug that
      // is impossible to figure out for the user or for us
      // CRM-7925
      CRM_Core_Error::fatal();
    }

    $params['contact_id'] = $contact->id;

    if (Civi::settings()->get('is_enabled')) {
      // Enabling multisite causes the contact to be added to the domain group.
      $domainGroupID = CRM_Core_BAO_Domain::getGroupId();
      if (!empty($domainGroupID)) {
        if (!empty($params['group']) && is_array($params['group'])) {
          $params['group'][$domainGroupID] = 1;
        }
        else {
          $params['group'] = array($domainGroupID => 1);
        }
      }
    }

    if (array_key_exists('group', $params)) {
      $contactIds = array($params['contact_id']);
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

          $noteParams = array(
            'entity_id' => $contact->id,
            'entity_table' => 'civicrm_contact',
            'note' => $note['note'],
            'subject' => CRM_Utils_Array::value('subject', $note),
            'contact_id' => $contactId,
          );
          CRM_Core_BAO_Note::add($noteParams, CRM_Core_DAO::$_nullArray);
        }
      }
      else {
        $contactId = $contact->id;
        //if logged in user, overwrite contactId
        if ($userID) {
          $contactId = $userID;
        }

        $noteParams = array(
          'entity_id' => $contact->id,
          'entity_table' => 'civicrm_contact',
          'note' => $params['note'],
          'subject' => CRM_Utils_Array::value('subject', $params),
          'contact_id' => $contactId,
        );
        CRM_Core_BAO_Note::add($noteParams, CRM_Core_DAO::$_nullArray);
      }
    }

    // update the UF user_unique_id if that has changed
    CRM_Core_BAO_UFMatch::updateUFName($contact->id);

    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contact', $contact->id);
    }

    // make a civicrm_subscription_history entry only on contact create (CRM-777)
    if (empty($params['contact_id'])) {
      $subscriptionParams = array(
        'contact_id' => $contact->id,
        'status' => 'Added',
        'method' => 'Admin',
      );
      CRM_Contact_BAO_SubscriptionHistory::create($subscriptionParams);
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

    return $contact;
  }

  /**
   * Ensure greeting parameters are set.
   *
   * By always populating greetings here we can be sure they are set if required & avoid a call later.
   * (ie. knowing we have definitely tried disambiguates between NULL & not loaded.)
   *
   * @param array $params
   */
  public static function ensureGreetingParamsAreSet(&$params) {
    $allGreetingParams = array('addressee' => 'addressee_id', 'postal_greeting' => 'postal_greeting_id', 'email_greeting' => 'email_greeting_id');
    $missingGreetingParams = array();

    foreach ($allGreetingParams as $greetingIndex => $greetingParam) {
      if (empty($params[$greetingParam])) {
        $missingGreetingParams[$greetingIndex] = $greetingParam;
      }
    }

    if (!empty($params['contact_id']) && !empty($missingGreetingParams)) {
      $savedGreetings = civicrm_api3('Contact', 'getsingle', array(
        'id' => $params['contact_id'],
        'return' => array_keys($missingGreetingParams))
      );

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
   * @return array
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

      return $includeTypeInReturnParameters ? array(
        $displayName,
        $image,
        $dao->contact_type,
        $dao->contact_sub_type,
        $imageUrl,
      ) : array($displayName, $image, $imageUrl);
    }
    return NULL;
  }

  /**
   * Add billing fields to the params if appropriate.
   *
   * If we have ANY name fields then we want to ignore all the billing name fields. However, if we
   * don't then we should set the name fields to the filling fields AND add the preserveDBName
   * parameter (which will tell the BAO only to set those fields if none already exist.
   *
   * We specifically don't want to set first name from billing and last name form an on-page field. Mixing &
   * matching is best done by hipsters.
   *
   * @param array $params
   */
  public static function addBillingNameFieldsIfOtherwiseNotSet(&$params) {
    $nameFields = array('first_name', 'middle_name', 'last_name', 'nick_name', 'prefix_id', 'suffix_id');
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
   * @param int|NULL $countryID
   *
   * @return int|null
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
      $stateProvinceList = CRM_Core_BAO_Address::buildOptions('state_province_id', NULL, array('country_id' => Civi::settings()->get('defaultContactCountry')));
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
   * Create last viewed link to recently updated contact.
   *
   * @param array $crudLinkSpec
   *  - action: int, CRM_Core_Action::UPDATE or CRM_Core_Action::VIEW [default: VIEW]
   *  - entity_table: string, eg "civicrm_contact"
   *  - entity_id: int
   *
   * @return array|NULL
   *   NULL if unavailable, or
   *   [path: string, query: string, title: string]
   * @see CRM_Utils_System::createDefaultCrudLink
   */
  public function createDefaultCrudLink($crudLinkSpec) {
    switch ($crudLinkSpec['action']) {
      case CRM_Core_Action::VIEW:
        $result = array(
          'title' => $this->display_name,
          'path' => 'civicrm/contact/view',
          'query' => array(
            'reset' => 1,
            'cid' => $this->id,
          ),
        );
        return $result;

      case CRM_Core_Action::UPDATE:
        $result = array(
          'title' => $this->display_name,
          'path' => 'civicrm/contact/add',
          'query' => array(
            'reset' => 1,
            'action' => 'update',
            'cid' => $this->id,
          ),
        );
        return $result;
    }
    return NULL;
  }

  /**
   * Get the values for pseudoconstants for name->value and reverse.
   *
   * @deprecated
   *
   * This is called specifically from the contact import parser & should be moved there
   * as it is not truly a generic function.
   *
   * @param array $defaults
   *   (reference) the default values, some of which need to be resolved.
   * @param bool $reverse
   *   Always true as this function is only called from one place..
   */
  public static function resolveDefaults(&$defaults, $reverse = FALSE) {
    // Hack for birth_date.
    if (!empty($defaults['birth_date'])) {
      if (is_array($defaults['birth_date'])) {
        $defaults['birth_date'] = CRM_Utils_Date::format($defaults['birth_date'], '-');
      }
    }

    CRM_Utils_Array::lookupValue($defaults, 'prefix', CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id'), $reverse);
    CRM_Utils_Array::lookupValue($defaults, 'suffix', CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id'), $reverse);
    CRM_Utils_Array::lookupValue($defaults, 'gender', CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'), $reverse);
    CRM_Utils_Array::lookupValue($defaults, 'communication_style', CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'communication_style_id'), $reverse);

    //lookup value of email/postal greeting, addressee, CRM-4575
    foreach (self::$_greetingTypes as $greeting) {
      $filterCondition = array(
        'contact_type' => CRM_Utils_Array::value('contact_type', $defaults),
        'greeting_type' => $greeting,
      );
      CRM_Utils_Array::lookupValue($defaults, $greeting,
        CRM_Core_PseudoConstant::greeting($filterCondition), $reverse
      );
    }

    $blocks = array('address', 'im', 'phone');
    foreach ($blocks as $name) {
      if (!array_key_exists($name, $defaults) || !is_array($defaults[$name])) {
        continue;
      }
      foreach ($defaults[$name] as $count => & $values) {

        //get location type id.
        CRM_Utils_Array::lookupValue($values, 'location_type', CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'), $reverse);

        if ($name == 'address') {
          // FIXME: lookupValue doesn't work for vcard_name
          if (!empty($values['location_type_id'])) {
            $vcardNames = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', array('labelColumn' => 'vcard_name'));
            $values['vcard_name'] = $vcardNames[$values['location_type_id']];
          }

          if (!CRM_Utils_Array::lookupValue($values,
              'country',
              CRM_Core_PseudoConstant::country(),
              $reverse
            ) &&
            $reverse
          ) {
            CRM_Utils_Array::lookupValue($values,
              'country',
              CRM_Core_PseudoConstant::countryIsoCode(),
              $reverse
            );
          }
          $stateProvinceID = self::resolveStateProvinceID($values, CRM_Utils_Array::value('country_id', $values));
          if ($stateProvinceID) {
            $values['state_province_id'] = $stateProvinceID;
          }

          if (!empty($values['state_province_id'])) {
            $countyList = CRM_Core_PseudoConstant::countyForState($values['state_province_id']);
          }
          else {
            $countyList = CRM_Core_PseudoConstant::county();
          }
          CRM_Utils_Array::lookupValue($values,
            'county',
            $countyList,
            $reverse
          );
        }

        if ($name == 'im') {
          CRM_Utils_Array::lookupValue($values,
            'provider',
            CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id'),
            $reverse
          );
        }

        if ($name == 'phone') {
          CRM_Utils_Array::lookupValue($values,
            'phone_type',
            CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id'),
            $reverse
          );
        }

        // Kill the reference.
        unset($values);
      }
    }
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the name / value pairs.
   *                        in a hierarchical manner
   * @param bool $microformat
   *   For location in microformat.
   *
   * @return CRM_Contact_BAO_Contact
   */
  public static function &retrieve(&$params, &$defaults, $microformat = FALSE) {
    if (array_key_exists('contact_id', $params)) {
      $params['id'] = $params['contact_id'];
    }
    elseif (array_key_exists('id', $params)) {
      $params['contact_id'] = $params['id'];
    }

    $contact = self::getValues($params, $defaults);

    unset($params['id']);

    //get the block information for this contact
    $entityBlock = array('contact_id' => $params['contact_id']);
    $blocks = CRM_Core_BAO_Location::getValues($entityBlock, $microformat);
    $defaults = array_merge($defaults, $blocks);
    foreach ($blocks as $block => $value) {
      $contact->$block = $value;
    }

    if (!isset($params['noNotes'])) {
      $contact->notes = CRM_Core_BAO_Note::getValues($params, $defaults);
    }

    if (!isset($params['noRelationships'])) {
      $contact->relationship = CRM_Contact_BAO_Relationship::getValues($params, $defaults);
    }

    if (!isset($params['noGroups'])) {
      $contact->groupContact = CRM_Contact_BAO_GroupContact::getValues($params, $defaults);
    }

    if (!isset($params['noWebsite'])) {
      $contact->website = CRM_Core_BAO_Website::getValues($params, $defaults);
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
   */
  public static function deleteContact($id, $restore = FALSE, $skipUndelete = FALSE, $checkPermissions = TRUE) {

    if (!$id) {
      return FALSE;
    }
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
    if ($skipUndelete && CRM_Financial_BAO_FinancialItem::checkContactPresent(array($id), $error)) {
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

    if ($restore) {
      return self::contactTrashRestore($contact, TRUE);
    }

    // start a new transaction
    $transaction = new CRM_Core_Transaction();

    if ($skipUndelete) {
      CRM_Utils_Hook::pre('delete', $contactType, $id, CRM_Core_DAO::$_nullArray);

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
      self::contactTrashRestore($contact);
    }

    //delete the contact id from recently view
    CRM_Utils_Recent::delContact($id);
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
      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_acl_contact_cache WHERE contact_id = %1', array(1 => array($contactID, 'Integer')));
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

    return array($imageThumbWidth, $imageThumbHeight);
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
    $mimeType = array(
      'image/jpeg',
      'image/jpg',
      'image/png',
      'image/bmp',
      'image/p-jpeg',
      'image/gif',
      'image/x-png',
    );

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
   * Extract contact id from url for deleting contact image.
   */
  public static function processImage() {

    $action = CRM_Utils_Request::retrieve('action', 'String');
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive');
    // retrieve contact id in case of Profile context
    $id = CRM_Utils_Request::retrieve('id', 'Positive');
    $cid = $cid ? $cid : $id;
    if ($action & CRM_Core_Action::DELETE) {
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean')) {
        CRM_Contact_BAO_Contact::deleteContactImage($cid);
        CRM_Core_Session::setStatus(ts('Contact image deleted successfully'), ts('Image Deleted'), 'success');
        $session = CRM_Core_Session::singleton();
        $toUrl = $session->popUserContext();
        CRM_Utils_System::redirect($toUrl);
      }
    }
  }

  /**
   * Function to set is_delete true or restore deleted contact.
   *
   * @param CRM_Contact_DAO_Contact $contact
   *   Contact DAO object.
   * @param bool $restore
   *   True to set the is_delete = 1 else false to restore deleted contact,
   *   i.e. is_delete = 0
   *
   * @return bool
   */
  public static function contactTrashRestore($contact, $restore = FALSE) {
    $updateParams = array(
      'id' => $contact->id,
      'is_deleted' => $restore ? 0 : 1,
    );

    CRM_Utils_Hook::pre('update', $contact->contact_type, $contact->id, $updateParams);

    $params = array(1 => array($contact->id, 'Integer'));
    if (!$restore) {
      $query = "DELETE FROM civicrm_uf_match WHERE contact_id = %1";
      CRM_Core_DAO::executeQuery($query, $params);
    }

    $contact->copyValues($updateParams);
    $contact->save();
    CRM_Core_BAO_Log::register($contact->id, 'civicrm_contact', $contact->id);

    CRM_Utils_Hook::post('update', $contact->contact_type, $contact->id, $contact);

    return TRUE;
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
      return $implodeDelimiter ? NULL : array();
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
    $params = array('id' => $id);
    $details = array();
    $contact = CRM_Core_DAO::commonRetrieve('CRM_Contact_DAO_Contact',
      $params,
      $details,
      array('contact_type', 'contact_sub_type')
    );

    if ($contact) {
      $contactTypes = array();
      if ($contact->contact_sub_type) {
        $contactTypes = CRM_Utils_Array::explodePadded($contact->contact_sub_type);
      }
      array_unshift($contactTypes, $contact->contact_type);

      return $contactTypes;
    }
    else {
      CRM_Core_Error::fatal();
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

    $fields = CRM_Utils_Array::value($cacheKeyString, self::$_importableFields);

    if (!$fields) {
      // check if we can retrieve from database cache
      $fields = CRM_Core_BAO_Cache::getItem('contact fields', $cacheKeyString);
    }

    if (!$fields) {
      $fields = CRM_Contact_DAO_Contact::import();

      // get the fields thar are meant for contact types
      if (in_array($contactType, array(
        'Individual',
        'Household',
        'Organization',
        'All',
      ))) {
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
        //unset the fields, which are not related to their
        //contact type.
        $commonValues = array(
          'Individual' => array(
            'household_name',
            'legal_name',
            'sic_code',
            'organization_name',
          ),
          'Household' => array(
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
          ),
          'Organization' => array(
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
            'is_deceased',
            'deceased_date',
          ),
        );
        foreach ($commonValues[$contactType] as $value) {
          unset($fields[$value]);
        }
      }
      else {
        foreach (array('Individual', 'Household', 'Organization') as $type) {
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
        $fields = array_merge($fields, array(
          'group' => array(
            'title' => ts('Group(s)'),
            'name' => 'group',
          ),
          'tag' => array(
            'title' => ts('Tag(s)'),
            'name' => 'tag',
          ),
          'note' => array(
            'title' => ts('Note'),
            'name' => 'note',
          ),
          'communication_style_id' => array(
            'title' => ts('Communication Style'),
            'name' => 'communication_style_id',
          ),
        ));
      }

      //Sorting fields in alphabetical order(CRM-1507)
      $fields = CRM_Utils_Array::crmArraySortByField($fields, 'title');

      CRM_Core_BAO_Cache::setItem($fields, 'contact fields', $cacheKeyString);
    }

    self::$_importableFields[$cacheKeyString] = $fields;

    if (!$isProfile) {
      if (!$status) {
        $fields = array_merge(array('do_not_import' => array('title' => ts('- do not import -'))),
          self::$_importableFields[$cacheKeyString]
        );
      }
      else {
        $fields = array_merge(array('' => array('title' => ts('- Contact Fields -'))),
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
    //CRM-14501 it turns out that the impact of permissioning here is sometimes inconsistent. The field that
    //calculates custom fields takes into account the logged in user & caches that for all users
    //as an interim fix we will cache the fields by contact
    $cacheKeyString .= '_' . CRM_Core_Session::getLoggedInContactID();

    if (!self::$_exportableFields || !CRM_Utils_Array::value($cacheKeyString, self::$_exportableFields)) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = array();
      }

      // check if we can retrieve from database cache
      $fields = CRM_Core_BAO_Cache::getItem('contact fields', $cacheKeyString);

      if (!$fields) {
        $fields = CRM_Contact_DAO_Contact::export();

        // The fields are meant for contact types.
        if (in_array($contactType, array(
          'Individual',
          'Household',
          'Organization',
          'All',
          )
        )) {
          $fields = array_merge($fields, CRM_Core_OptionValue::getFields('', $contactType));
        }
        // add current employer for individuals
        $fields = array_merge($fields, array(
          'current_employer' =>
            array(
              'name' => 'organization_name',
              'title' => ts('Current Employer'),
            ),
        ));

        $locationType = array(
          'location_type' => array(
            'name' => 'location_type',
            'where' => 'civicrm_location_type.name',
            'title' => ts('Location Type'),
          ),
        );

        $IMProvider = array(
          'im_provider' => array(
            'name' => 'im_provider',
            'where' => 'civicrm_im.provider_id',
            'title' => ts('IM Provider'),
          ),
        );

        $locationFields = array_merge($locationType,
          CRM_Core_DAO_Address::export(),
          CRM_Core_DAO_Phone::export(),
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
          foreach (array(
                     'Individual',
                     'Household',
                     'Organization',
                   ) as $type) {
            $fields = array_merge($fields,
              CRM_Core_BAO_CustomField::getFieldsForImport($type, FALSE, FALSE, $search, $checkPermissions, $withMultiRecord)
            );
          }
        }
        $fields['current_employer_id']['title'] = ts('Current Employer ID');
        //fix for CRM-791
        if ($export) {
          $fields = array_merge($fields, array(
            'groups' => array(
              'title' => ts('Group(s)'),
              'name' => 'groups',
            ),
            'tags' => array(
              'title' => ts('Tag(s)'),
              'name' => 'tags',
            ),
            'notes' => array(
              'title' => ts('Note(s)'),
              'name' => 'notes',
            ),
          ));
        }
        else {
          $fields = array_merge($fields, array(
            'group' => array(
              'title' => ts('Group(s)'),
              'name' => 'group',
            ),
            'tag' => array(
              'title' => ts('Tag(s)'),
              'name' => 'tag',
            ),
            'note' => array(
              'title' => ts('Note(s)'),
              'name' => 'note',
            ),
          ));
        }

        //Sorting fields in alphabetical order(CRM-1507)
        foreach ($fields as $k => $v) {
          $sortArray[$k] = CRM_Utils_Array::value('title', $v);
        }

        $fields = array_merge($sortArray, $fields);
        //unset the field which are not related to their contact type.
        if ($contactType != 'All') {
          $commonValues = array(
            'Individual' => array(
              'household_name',
              'legal_name',
              'sic_code',
              'organization_name',
              'email_greeting_custom',
              'postal_greeting_custom',
              'addressee_custom',
            ),
            'Household' => array(
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
            ),
            'Organization' => array(
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
            ),
          );
          foreach ($commonValues[$contactType] as $value) {
            unset($fields[$value]);
          }
        }

        CRM_Core_BAO_Cache::setItem($fields, 'contact fields', $cacheKeyString);
      }
      self::$_exportableFields[$cacheKeyString] = $fields;
    }

    if (!$status) {
      $fields = self::$_exportableFields[$cacheKeyString];
    }
    else {
      $fields = array_merge(array('' => array('title' => ts('- Contact Fields -'))),
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
  public static function getHierContactDetails($contactId, &$fields) {
    $params = array(array('contact_id', '=', $contactId, 0, 0));
    $options = array();

    $returnProperties = self::makeHierReturnProperties($fields, $contactId);

    // We don't know the contents of return properties, but we need the lower
    // level ids of the contact so add a few fields.
    $returnProperties['first_name'] = 1;
    $returnProperties['organization_name'] = 1;
    $returnProperties['household_name'] = 1;
    $returnProperties['contact_type'] = 1;
    $returnProperties['contact_sub_type'] = 1;
    return list($query, $options) = CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, $options);
  }

  /**
   * Given a set of flat profile style field names, create a hierarchy.
   *
   * This is for the query to use, create the right sql.
   *
   * @param $fields
   * @param int $contactId
   *   Contact id.
   *
   * @return array
   *   A hierarchical property tree if appropriate
   */
  public static function &makeHierReturnProperties($fields, $contactId = NULL) {
    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

    $returnProperties = array();

    $multipleFields = array('website' => 'url');
    foreach ($fields as $name => $dontCare) {
      if (strpos($name, '-') !== FALSE) {
        list($fieldName, $id, $type) = CRM_Utils_System::explode('-', $name, 3);

        if (!in_array($fieldName, $multipleFields)) {
          if ($id == 'Primary') {
            $locationTypeName = 1;
          }
          else {
            $locationTypeName = CRM_Utils_Array::value($id, $locationTypes);
            if (!$locationTypeName) {
              continue;
            }
          }

          if (empty($returnProperties['location'])) {
            $returnProperties['location'] = array();
          }
          if (empty($returnProperties['location'][$locationTypeName])) {
            $returnProperties['location'][$locationTypeName] = array();
            $returnProperties['location'][$locationTypeName]['location_type'] = $id;
          }
          if (in_array($fieldName, array(
            'phone',
            'im',
            'email',
            'openid',
            'phone_ext',
          ))) {
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
   * $params boolean $skipDefaultPriamry if true, return primary contact location type otherwise null
   *
   * @param int $contactId
   * @param bool $skipDefaultPriamry
   * @param null $block
   *
   * @return int
   *   $locationType location_type_id
   */
  public static function getPrimaryLocationType($contactId, $skipDefaultPriamry = FALSE, $block = NULL) {
    if ($block) {
      $entityBlock = array('contact_id' => $contactId);
      $blocks = CRM_Core_BAO_Location::getValues($entityBlock);
      foreach ($blocks[$block] as $key => $value) {
        if (!empty($value['is_primary'])) {
          $locationType = CRM_Utils_Array::value('location_type_id', $value);
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

      $params = array(1 => array($contactId, 'Integer'));

      $dao = CRM_Core_DAO::executeQuery($query, $params);

      $locationType = NULL;
      if ($dao->fetch()) {
        $locationType = $dao->locationType;
      }
    }
    if (isset($locationType)) {
      return $locationType;
    }
    elseif ($skipDefaultPriamry) {
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
    $params = array(1 => array($id, 'Integer'));
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
      $doNotEmail = $dao->do_not_email ? TRUE : FALSE;
      $onHold = $dao->on_hold ? TRUE : FALSE;
      $isDeceased = $dao->is_deceased ? TRUE : FALSE;
      return array($name, $email, $doNotEmail, $onHold, $isDeceased);
    }
    return array(NULL, NULL, NULL, NULL, NULL);
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
   */
  public static function createProfileContact(
    &$params,
    &$fields,
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

    list($data, $contactDetails) = self::formatProfileContactParams($params, $fields, $contactID, $ufGroupId, $ctype);

    // manage is_opt_out
    if (array_key_exists('is_opt_out', $fields) && array_key_exists('is_opt_out', $params)) {
      $wasOptOut = CRM_Utils_Array::value('is_opt_out', $contactDetails, FALSE);
      $isOptOut = CRM_Utils_Array::value('is_opt_out', $params, FALSE);
      $data['is_opt_out'] = $isOptOut;
      // on change, create new civicrm_subscription_history entry
      if (($wasOptOut != $isOptOut) && !empty($contactDetails['contact_id'])) {
        $shParams = array(
          'contact_id' => $contactDetails['contact_id'],
          'status' => $isOptOut ? 'Removed' : 'Added',
          'method' => 'Web',
        );
        CRM_Contact_BAO_SubscriptionHistory::create($shParams);
      }
    }

    $contact = self::create($data);

    // contact is null if the profile does not have any contact fields
    if ($contact) {
      $contactID = $contact->id;
    }

    if (empty($contactID)) {
      CRM_Core_Error::fatal('Cannot proceed without a valid contact id');
    }

    // Process group and tag
    if (!empty($fields['group'])) {
      $method = 'Admin';
      // this for sure means we are coming in via profile since i added it to fix
      // removing contacts from user groups -- lobo
      if ($visibility) {
        $method = 'Web';
      }
      CRM_Contact_BAO_GroupContact::create($params['group'], $contactID, $visibility, $method);
    }

    if (!empty($fields['tag']) && array_key_exists('tag', $params)) {
      // Convert comma separated form values from select2 v3
      $tags = is_array($params['tag']) ? $params['tag'] : array_fill_keys(array_filter(explode(',', $params['tag'])), 1);
      CRM_Core_BAO_EntityTag::create($tags, 'civicrm_contact', $contactID);
    }

    //to add profile in default group
    if (is_array($addToGroupID)) {
      $contactIds = array($contactID);
      foreach ($addToGroupID as $groupId) {
        CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId);
      }
    }
    elseif ($addToGroupID) {
      $contactIds = array($contactID);
      CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $addToGroupID);
    }

    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();

    if ($editHook) {
      CRM_Utils_Hook::post('edit', 'Profile', $contactID, $params);
    }
    else {
      CRM_Utils_Hook::post('create', 'Profile', $contactID, $params);
    }
    return $contactID;
  }

  /**
   * Format profile contact parameters.
   *
   * @param array $params
   * @param $fields
   * @param int $contactID
   * @param int $ufGroupId
   * @param null $ctype
   * @param bool $skipCustom
   *
   * @return array
   */
  public static function formatProfileContactParams(
    &$params,
    &$fields,
    $contactID = NULL,
    $ufGroupId = NULL,
    $ctype = NULL,
    $skipCustom = FALSE
  ) {

    $data = $contactDetails = array();

    // get the contact details (hier)
    if ($contactID) {
      list($details, $options) = self::getHierContactDetails($contactID, $fields);

      $contactDetails = $details[$contactID];
      $data['contact_type'] = CRM_Utils_Array::value('contact_type', $contactDetails);
      $data['contact_sub_type'] = CRM_Utils_Array::value('contact_sub_type', $contactDetails);
    }
    else {
      //we should get contact type only if contact
      if ($ufGroupId) {
        $data['contact_type'] = CRM_Core_BAO_UFField::getProfileType($ufGroupId);

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
      $data['organization_name'] = CRM_Utils_Array::value('organization_name', $contactDetails);
    }
    elseif ($ctype == 'Household') {
      $data['household_name'] = CRM_Utils_Array::value('household_name', $contactDetails);
    }

    $locationType = array();
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

    $blocks = array('email', 'phone', 'im', 'openid');

    $multiplFields = array('url');
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
      list($fieldName, $locTypeId, $typeId) = CRM_Utils_System::explode('-', $key, 3);

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

        if (in_array($fieldName, array('phone'))) {
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
          $data['email'][$loc]['email'] = $value;
          if (empty($contactID)) {
            $data['email'][$loc]['is_primary'] = 1;
          }
        }
        elseif ($fieldName == 'im') {
          if (isset($params[$key . '-provider_id'])) {
            $data['im'][$loc]['provider_id'] = $params[$key . '-provider_id'];
          }
          if (strpos($key, '-provider_id') !== FALSE) {
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
            ($value == '' || !isset($value))
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
        elseif ($key == 'edit') {
          continue;
        }
        else {
          if ($key == 'location') {
            foreach ($value as $locationTypeId => $field) {
              foreach ($field as $block => $val) {
                if ($block == 'address' && array_key_exists('address_name', $val)) {
                  $value[$locationTypeId][$block]['name'] = $value[$locationTypeId][$block]['address_name'];
                }
              }
            }
          }
          if ($key == 'phone' && isset($params['phone_ext'])) {
            $data[$key] = $value;
            foreach ($value as $cnt => $phoneBlock) {
              if ($params[$key][$cnt]['location_type_id'] == $params['phone_ext'][$cnt]['location_type_id']) {
                $data[$key][$cnt]['phone_ext'] = CRM_Utils_Array::retrieveValueRecursive($params['phone_ext'][$cnt], 'phone_ext');
              }
            }
          }
          elseif (in_array($key,
              array(
                'nick_name',
                'job_title',
                'middle_name',
                'birth_date',
                'gender_id',
                'current_employer',
                'prefix_id',
                'suffix_id',
              )) &&
              ($value == '' || !isset($value)) &&
              ($session->get('authSrc') & (CRM_Core_Permission::AUTH_SRC_CHECKSUM + CRM_Core_Permission::AUTH_SRC_LOGIN)) == 0 ||
              ($key == 'current_employer' && empty($params['current_employer']))) {
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

    return array($data, $contactDetails);
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
    $p = array(1 => array($mail, 'String'));

    if ($ctype) {
      $query .= " AND civicrm_contact.contact_type = %3";
      $p[3] = array($ctype, 'String');
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
    $p = array(1 => array($openId, 'String'));

    if ($ctype) {
      $query .= " AND civicrm_contact.contact_type = %3";
      $p[3] = array($ctype, 'String');
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
   *
   * @return string
   *   Email address if present else null
   */
  public static function getPrimaryEmail($contactID) {
    // fetch the primary email
    $query = "
   SELECT civicrm_email.email as email
     FROM civicrm_contact
LEFT JOIN civicrm_email    ON ( civicrm_contact.id = civicrm_email.contact_id )
    WHERE civicrm_email.is_primary = 1
      AND civicrm_contact.id = %1";
    $p = array(1 => array($contactID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $p);

    $email = NULL;
    if ($dao->fetch()) {
      $email = $dao->email;
    }
    return $email;
  }

  /**
   * Function to get primary OpenID of the contact.
   *
   * @param int $contactID
   *   Contact id.
   *
   * @return string
   *   >openid   OpenID if present else null
   */
  public static function getPrimaryOpenId($contactID) {
    // fetch the primary OpenID
    $query = "
SELECT    civicrm_openid.openid as openid
FROM      civicrm_contact
LEFT JOIN civicrm_openid ON ( civicrm_contact.id = civicrm_openid.contact_id )
WHERE     civicrm_contact.id = %1
AND       civicrm_openid.is_primary = 1";
    $p = array(1 => array($contactID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $p);

    $openId = NULL;
    if ($dao->fetch()) {
      $openId = $dao->openid;
    }
    return $openId;
  }

  /**
   * Fetch the object and store the values in the values array.
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   *
   * @return CRM_Contact_BAO_Contact|null
   *   The found object or null
   */
  public static function getValues(&$params, &$values) {
    $contact = new CRM_Contact_BAO_Contact();

    $contact->copyValues($params);

    if ($contact->find(TRUE)) {

      CRM_Core_DAO::storeValues($contact, $values);

      $privacy = array();
      foreach (self::$_commPrefs as $name) {
        if (isset($contact->$name)) {
          $privacy[$name] = $contact->$name;
        }
      }

      if (!empty($privacy)) {
        $values['privacy'] = $privacy;
      }

      // communication Prefferance
      $preffComm = $comm = array();
      $comm = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        $contact->preferred_communication_method
      );
      foreach ($comm as $value) {
        $preffComm[$value] = 1;
      }
      $temp = array('preferred_communication_method' => $contact->preferred_communication_method);

      $names = array(
        'preferred_communication_method' => array(
          'newName' => 'preferred_communication_method_display',
          'groupName' => 'preferred_communication_method',
        ),
      );

      // @todo This can be figured out from metadata & we can avoid the uncached query.
      CRM_Core_OptionGroup::lookupValues($temp, $names, FALSE);

      $values['preferred_communication_method'] = $preffComm;
      $values['preferred_communication_method_display'] = CRM_Utils_Array::value('preferred_communication_method_display', $temp);

      if ($contact->preferred_mail_format) {
        $preferredMailingFormat = CRM_Core_SelectValues::pmf();
        $values['preferred_mail_format'] = $preferredMailingFormat[$contact->preferred_mail_format];
      }

      // get preferred languages
      if (!empty($contact->preferred_language)) {
        $values['preferred_language'] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', 'preferred_language', $contact->preferred_language);
      }

      // Calculating Year difference
      if ($contact->birth_date) {
        $birthDate = CRM_Utils_Date::customFormat($contact->birth_date, '%Y%m%d');
        if ($birthDate < date('Ymd')) {
          $age = CRM_Utils_Date::calculateAge($birthDate);
          $values['age']['y'] = CRM_Utils_Array::value('years', $age);
          $values['age']['m'] = CRM_Utils_Array::value('months', $age);
        }
      }

      $contact->contact_id = $contact->id;

      return $contact;
    }
    return NULL;
  }

  /**
   * Given the component name and returns the count of participation of contact.
   *
   * @param string $component
   *   Input component name.
   * @param int $contactId
   *   Input contact id.
   * @param string $tableName
   *   Optional tableName if component is custom group.
   *
   * @return int
   *   total number in database
   */
  public static function getCountComponent($component, $contactId, $tableName = NULL) {
    $object = NULL;
    switch ($component) {
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

        return CRM_Contact_BAO_GroupContact::getContactGroup($contactId, "Added", NULL, TRUE);

      case 'log':
        if (CRM_Core_BAO_Log::useLoggingReport()) {
          return FALSE;
        }
        return CRM_Core_BAO_Log::getContactLogCount($contactId);

      case 'note':
        return CRM_Core_BAO_Note::getContactNoteCount($contactId);

      case 'contribution':
        return CRM_Contribute_BAO_Contribution::contributionCount($contactId);

      case 'membership':
        return CRM_Member_BAO_Membership::getContactMembershipCount($contactId, TRUE);

      case 'participant':
        return CRM_Event_BAO_Participant::getContactParticipantCount($contactId);

      case 'pledge':
        return CRM_Pledge_BAO_Pledge::getContactPledgeCount($contactId);

      case 'case':
        return CRM_Case_BAO_Case::caseCount($contactId);

      case 'grant':
        return CRM_Grant_BAO_Grant::getContactGrantCount($contactId);

      case 'activity':
        $input = array(
          'contact_id' => $contactId,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'activity',
        );
        return CRM_Activity_BAO_Activity::getActivitiesCount($input);

      case 'mailing':
        $params = array('contact_id' => $contactId);
        return CRM_Mailing_BAO_Mailing::getContactMailingsCount($params);

      default:
        $custom = explode('_', $component);
        if ($custom['0'] = 'custom') {
          if (!$tableName) {
            $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $custom['1'], 'table_name');
          }
          $queryString = "SELECT count(id) FROM {$tableName} WHERE entity_id = {$contactId}";
          return CRM_Core_DAO::singleValueQuery($queryString);
        }
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
        CRM_Contact_BAO_Contact::processGreetings($contact);
      }
    }
  }

  /**
   * Process greetings and cache.
   *
   * @param object $contact
   *   Contact object after save.
   */
  public static function processGreetings(&$contact) {

    //@todo this function does a lot of unnecessary loading.
    // ensureGreetingParamsAreSet now makes sure that the contact is
    // loaded and using updateGreetingsOnTokenFieldChange
    // allows us the possibility of only doing an update if required.

    // The contact object has not always required the
    // fields that are required to calculate greetings
    // so we need to retrieve it again.
    if ($contact->_query !== FALSE) {
      $contact->find(TRUE);
    }

    // store object values to an array
    $contactDetails = array();
    CRM_Core_DAO::storeValues($contact, $contactDetails);
    $contactDetails = array(array($contact->id => $contactDetails));

    $emailGreetingString = $postalGreetingString = $addresseeString = NULL;
    $updateQueryString = array();

    //cache email and postal greeting to greeting display
    if ($contact->email_greeting_custom != 'null' && $contact->email_greeting_custom) {
      $emailGreetingString = $contact->email_greeting_custom;
    }
    elseif ($contact->email_greeting_id != 'null' && $contact->email_greeting_id) {
      // the filter value for Individual contact type is set to 1
      $filter = array(
        'contact_type' => $contact->contact_type,
        'greeting_type' => 'email_greeting',
      );

      $emailGreeting = CRM_Core_PseudoConstant::greeting($filter);
      $emailGreetingString = $emailGreeting[$contact->email_greeting_id];
      $updateQueryString[] = " email_greeting_custom = NULL ";
    }
    else {
      if ($contact->email_greeting_custom) {
        $updateQueryString[] = " email_greeting_display = NULL ";
      }
    }

    if ($emailGreetingString) {
      CRM_Contact_BAO_Contact_Utils::processGreetingTemplate($emailGreetingString,
        $contactDetails,
        $contact->id,
        'CRM_Contact_BAO_Contact'
      );
      $emailGreetingString = CRM_Core_DAO::escapeString(CRM_Utils_String::stripSpaces($emailGreetingString));
      $updateQueryString[] = " email_greeting_display = '{$emailGreetingString}'";
    }

    //postal greetings
    if ($contact->postal_greeting_custom != 'null' && $contact->postal_greeting_custom) {
      $postalGreetingString = $contact->postal_greeting_custom;
    }
    elseif ($contact->postal_greeting_id != 'null' && $contact->postal_greeting_id) {
      $filter = array(
        'contact_type' => $contact->contact_type,
        'greeting_type' => 'postal_greeting',
      );
      $postalGreeting = CRM_Core_PseudoConstant::greeting($filter);
      $postalGreetingString = $postalGreeting[$contact->postal_greeting_id];
      $updateQueryString[] = " postal_greeting_custom = NULL ";
    }
    else {
      if ($contact->postal_greeting_custom) {
        $updateQueryString[] = " postal_greeting_display = NULL ";
      }
    }

    if ($postalGreetingString) {
      CRM_Contact_BAO_Contact_Utils::processGreetingTemplate($postalGreetingString,
        $contactDetails,
        $contact->id,
        'CRM_Contact_BAO_Contact'
      );
      $postalGreetingString = CRM_Core_DAO::escapeString(CRM_Utils_String::stripSpaces($postalGreetingString));
      $updateQueryString[] = " postal_greeting_display = '{$postalGreetingString}'";
    }

    // addressee
    if ($contact->addressee_custom != 'null' && $contact->addressee_custom) {
      $addresseeString = $contact->addressee_custom;
    }
    elseif ($contact->addressee_id != 'null' && $contact->addressee_id) {
      $filter = array(
        'contact_type' => $contact->contact_type,
        'greeting_type' => 'addressee',
      );

      $addressee = CRM_Core_PseudoConstant::greeting($filter);
      $addresseeString = $addressee[$contact->addressee_id];
      $updateQueryString[] = " addressee_custom = NULL ";
    }
    else {
      if ($contact->addressee_custom) {
        $updateQueryString[] = " addressee_display = NULL ";
      }
    }

    if ($addresseeString) {
      CRM_Contact_BAO_Contact_Utils::processGreetingTemplate($addresseeString,
        $contactDetails,
        $contact->id,
        'CRM_Contact_BAO_Contact'
      );
      $addresseeString = CRM_Core_DAO::escapeString(CRM_Utils_String::stripSpaces($addresseeString));
      $updateQueryString[] = " addressee_display = '{$addresseeString}'";
    }

    if (!empty($updateQueryString)) {
      $updateQueryString = implode(',', $updateQueryString);
      $queryString = "UPDATE civicrm_contact SET {$updateQueryString} WHERE id = {$contact->id}";
      CRM_Core_DAO::executeQuery($queryString);
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
  public static function getLocBlockIds($contactId, $criteria = array(), $condOperator = 'AND') {
    $locBlockIds = array();
    if (!$contactId) {
      return $locBlockIds;
    }

    foreach (array('Email', 'OpenID', 'Phone', 'Address', 'IM') as $block) {
      $name = strtolower($block);
      $className = "CRM_Core_DAO_$block";
      $blockDAO = new $className();

      // build the condition.
      if (is_array($criteria)) {
        $fields = $blockDAO->fields();
        $conditions = array();
        foreach ($criteria as $field => $value) {
          if (array_key_exists($field, $fields)) {
            $cond = "( $field = $value )";
            // value might be zero or null.
            if (!$value || strtolower($value) == 'null') {
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
      $blockDAO->free();
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
    $menu = array(
      'view' => array(
        'title' => ts('View Contact'),
        'weight' => 0,
        'ref' => 'view-contact',
        'class' => 'no-popup',
        'key' => 'view',
        'permissions' => array('view all contacts'),
      ),
      'add' => array(
        'title' => ts('Edit Contact'),
        'weight' => 0,
        'ref' => 'edit-contact',
        'class' => 'no-popup',
        'key' => 'add',
        'permissions' => array('edit all contacts'),
      ),
      'delete' => array(
        'title' => ts('Delete Contact'),
        'weight' => 0,
        'ref' => 'delete-contact',
        'key' => 'delete',
        'permissions' => array('access deleted contacts', 'delete contacts'),
      ),
      'contribution' => array(
        'title' => ts('Add Contribution'),
        'weight' => 5,
        'ref' => 'new-contribution',
        'key' => 'contribution',
        'tab' => 'contribute',
        'component' => 'CiviContribute',
        'href' => CRM_Utils_System::url('civicrm/contact/view/contribution',
          'reset=1&action=add&context=contribution'
        ),
        'permissions' => array(
          'access CiviContribute',
          'edit contributions',
        ),
      ),
      'participant' => array(
        'title' => ts('Register for Event'),
        'weight' => 10,
        'ref' => 'new-participant',
        'key' => 'participant',
        'tab' => 'participant',
        'component' => 'CiviEvent',
        'href' => CRM_Utils_System::url('civicrm/contact/view/participant', 'reset=1&action=add&context=participant'),
        'permissions' => array(
          'access CiviEvent',
          'edit event participants',
        ),
      ),
      'activity' => array(
        'title' => ts('Record Activity'),
        'weight' => 35,
        'ref' => 'new-activity',
        'key' => 'activity',
        'permissions' => array('edit all contacts'),
      ),
      'pledge' => array(
        'title' => ts('Add Pledge'),
        'weight' => 15,
        'ref' => 'new-pledge',
        'key' => 'pledge',
        'tab' => 'pledge',
        'href' => CRM_Utils_System::url('civicrm/contact/view/pledge',
          'reset=1&action=add&context=pledge'
        ),
        'component' => 'CiviPledge',
        'permissions' => array(
          'access CiviPledge',
          'edit pledges',
        ),
      ),
      'membership' => array(
        'title' => ts('Add Membership'),
        'weight' => 20,
        'ref' => 'new-membership',
        'key' => 'membership',
        'tab' => 'member',
        'component' => 'CiviMember',
        'href' => CRM_Utils_System::url('civicrm/contact/view/membership',
          'reset=1&action=add&context=membership'
        ),
        'permissions' => array(
          'access CiviMember',
          'edit memberships',
        ),
      ),
      'case' => array(
        'title' => ts('Add Case'),
        'weight' => 25,
        'ref' => 'new-case',
        'key' => 'case',
        'tab' => 'case',
        'component' => 'CiviCase',
        'href' => CRM_Utils_System::url('civicrm/case/add', 'reset=1&action=add&context=case'),
        'permissions' => array('add cases'),
      ),
      'grant' => array(
        'title' => ts('Add Grant'),
        'weight' => 26,
        'ref' => 'new-grant',
        'key' => 'grant',
        'tab' => 'grant',
        'component' => 'CiviGrant',
        'href' => CRM_Utils_System::url('civicrm/contact/view/grant',
          'reset=1&action=add&context=grant'
        ),
        'permissions' => array('edit grants'),
      ),
      'rel' => array(
        'title' => ts('Add Relationship'),
        'weight' => 30,
        'ref' => 'new-relationship',
        'key' => 'rel',
        'tab' => 'rel',
        'href' => CRM_Utils_System::url('civicrm/contact/view/rel',
          'reset=1&action=add'
        ),
        'permissions' => array('edit all contacts'),
      ),
      'note' => array(
        'title' => ts('Add Note'),
        'weight' => 40,
        'ref' => 'new-note',
        'key' => 'note',
        'tab' => 'note',
        'class' => 'medium-popup',
        'href' => CRM_Utils_System::url('civicrm/contact/view/note',
          'reset=1&action=add'
        ),
        'permissions' => array('edit all contacts'),
      ),
      'email' => array(
        'title' => ts('Send an Email'),
        'weight' => 45,
        'ref' => 'new-email',
        'key' => 'email',
        'permissions' => array('view all contacts'),
      ),
      'group' => array(
        'title' => ts('Add to Group'),
        'weight' => 50,
        'ref' => 'group-add-contact',
        'key' => 'group',
        'tab' => 'group',
        'permissions' => array('edit groups'),
      ),
      'tag' => array(
        'title' => ts('Tag Contact'),
        'weight' => 55,
        'ref' => 'tag-contact',
        'key' => 'tag',
        'tab' => 'tag',
        'permissions' => array('edit all contacts'),
      ),
    );

    $menu['otherActions'] = array(
      'print' => array(
        'title' => ts('Print Summary'),
        'description' => ts('Printer-friendly view of this page.'),
        'weight' => 5,
        'ref' => 'crm-contact-print',
        'key' => 'print',
        'tab' => 'print',
        'href' => CRM_Utils_System::url('civicrm/contact/view/print',
          "reset=1&print=1"
        ),
        'class' => 'print',
        'icon' => 'crm-i fa-print',
      ),
      'vcard' => array(
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
      ),
    );

    if (CRM_Core_Permission::check('access Contact Dashboard')) {
      $menu['otherActions']['dashboard'] = array(
        'title' => ts('Contact Dashboard'),
        'description' => ts('Contact Dashboard'),
        'weight' => 15,
        'ref' => 'crm-contact-dashboard',
        'key' => 'dashboard',
        'tab' => 'dashboard',
        'class' => 'dashboard',
        // NOTE: As an alternative you can also build url on CMS specific way
        //  as CRM_Core_Config::singleton()->userSystem->getUserRecordUrl($contactId)
        'href' => CRM_Utils_System::url('civicrm/user', "reset=1&id={$contactId}"),
        'icon' => 'crm-i fa-tachometer',
      );
    }

    $uid = CRM_Core_BAO_UFMatch::getUFId($contactId);
    if ($uid) {
      $menu['otherActions']['user-record'] = array(
        'title' => ts('User Record'),
        'description' => ts('User Record'),
        'weight' => 20,
        'ref' => 'crm-contact-user-record',
        'key' => 'user-record',
        'tab' => 'user-record',
        'class' => 'user-record',
        'href' => CRM_Core_Config::singleton()->userSystem->getUserRecordUrl($contactId),
        'icon' => 'crm-i fa-user',
      );
    }
    elseif (CRM_Core_Config::singleton()->userSystem->checkPermissionAddUser()) {
      $menu['otherActions']['user-add'] = array(
        'title' => ts('Create User Record'),
        'description' => ts('Create User Record'),
        'weight' => 25,
        'ref' => 'crm-contact-user-add',
        'key' => 'user-add',
        'tab' => 'user-add',
        'class' => 'user-add',
        'href' => CRM_Utils_System::url('civicrm/contact/view/useradd', 'reset=1&action=add&cid=' . $contactId),
        'icon' => 'crm-i fa-user-plus',
      );
    }

    CRM_Utils_Hook::summaryActions($menu, $contactId);
    //1. check for component is active.
    //2. check for user permissions.
    //3. check for acls.
    //3. edit and view contact are directly accessible to user.

    $aclPermissionedTasks = array(
      'view-contact',
      'edit-contact',
      'new-activity',
      'new-email',
      'group-add-contact',
      'tag-contact',
      'delete-contact',
    );
    $corePermission = CRM_Core_Permission::getPermission();

    $contextMenu = array();
    foreach ($menu as $key => $values) {
      if ($key != 'otherActions') {

        // user does not have necessary permissions.
        if (!self::checkUserMenuPermissions($aclPermissionedTasks, $corePermission, $values)) {
          continue;
        }
        // build directly accessible action menu.
        if (in_array($values['ref'], array(
          'view-contact',
          'edit-contact',
        ))) {
          $contextMenu['primaryActions'][$key] = array(
            'title' => $values['title'],
            'ref' => $values['ref'],
            'class' => CRM_Utils_Array::value('class', $values),
            'key' => $values['key'],
          );
          continue;
        }

        // finally get menu item for -more- action widget.
        $contextMenu['moreActions'][$values['weight']] = array(
          'title' => $values['title'],
          'ref' => $values['ref'],
          'href' => CRM_Utils_Array::value('href', $values),
          'tab' => CRM_Utils_Array::value('tab', $values),
          'class' => CRM_Utils_Array::value('class', $values),
          'key' => $values['key'],
        );
      }
      else {
        foreach ($values as $value) {
          // user does not have necessary permissions.
          if (!self::checkUserMenuPermissions($aclPermissionedTasks, $corePermission, $value)) {
            continue;
          }

          // finally get menu item for -more- action widget.
          $contextMenu['otherActions'][$value['weight']] = array(
            'title' => $value['title'],
            'ref' => $value['ref'],
            'href' => CRM_Utils_Array::value('href', $value),
            'tab' => CRM_Utils_Array::value('tab', $value),
            'class' => CRM_Utils_Array::value('class', $value),
            'icon' => CRM_Utils_Array::value('icon', $value),
            'key' => $value['key'],
          );
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
    $componentName = CRM_Utils_Array::value('component', $menuOptions);

    // if component action - make sure component is enable.
    if ($componentName && !in_array($componentName, CRM_Core_Config::singleton()->enableComponents)) {
      return FALSE;
    }

    // make sure user has all required permissions.
    $hasAllPermissions = FALSE;

    $permissions = CRM_Utils_Array::value('permissions', $menuOptions);
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
      if (!$hasAllPermissions && $menuOptions['ref'] != 'delete-contact') {
        if (in_array($menuOptions['ref'], $aclPermissionedTasks) &&
            $corePermission == CRM_Core_Permission::EDIT
            ) {
          $hasAllPermissions = TRUE;
        }
        elseif (in_array($menuOptions['ref'], array(
          'new-email',
        ))) {
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
      array(
        1 => array($contactId, 'Integer'),
      )
    );
    if ($timestamps->fetch()) {
      return array(
        'created_date' => $timestamps->created_date,
        'modified_date' => $timestamps->modified_date,
      );
    }
    else {
      return NULL;
    }
  }

  /**
   * Get a list of triggers for the contact table.
   *
   * @see hook_civicrm_triggerInfo
   * @see CRM_Core_DAO::triggerRebuild
   * @see http://issues.civicrm.org/jira/browse/CRM-10554
   *
   * @param $info
   * @param null $tableName
   */
  public static function triggerInfo(&$info, $tableName = NULL) {
    //during upgrade, first check for valid version and then create triggers
    //i.e the columns created_date and modified_date are introduced in 4.3.alpha1 so dont create triggers for older version
    if (CRM_Core_Config::isUpgradeMode()) {
      $currentVer = CRM_Core_BAO_Domain::version(TRUE);
      //if current version is less than 4.3.alpha1 dont create below triggers
      if (version_compare($currentVer, '4.3.alpha1') < 0) {
        return;
      }
    }

    // Modifications to these records should update the contact timestamps.
    \Civi\Core\SqlTrigger\TimestampTriggers::create('civicrm_contact', 'Contact')
      ->setRelations(array(
          array('table' => 'civicrm_address', 'column' => 'contact_id'),
          array('table' => 'civicrm_email', 'column' => 'contact_id'),
          array('table' => 'civicrm_im', 'column' => 'contact_id'),
          array('table' => 'civicrm_phone', 'column' => 'contact_id'),
          array('table' => 'civicrm_website', 'column' => 'contact_id'),
        )
      )
      ->alterTriggerInfo($info, $tableName);

    // Update phone table to populate phone_numeric field
    if (!$tableName || $tableName == 'civicrm_phone') {
      // Define stored sql function needed for phones
      $sqlTriggers = Civi::service('sql_triggers');
      $sqlTriggers->enqueueQuery(self::DROP_STRIP_FUNCTION_43);
      $sqlTriggers->enqueueQuery(self::CREATE_STRIP_FUNCTION_43);
      $info[] = array(
        'table' => array('civicrm_phone'),
        'when' => 'BEFORE',
        'event' => array('INSERT', 'UPDATE'),
        'sql' => "\nSET NEW.phone_numeric = civicrm_strip_non_numeric(NEW.phone);\n",
      );
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
   * Get options for a given contact field.
   *
   * @see CRM_Core_DAO::buildOptions
   *
   * TODO: Should we always assume chainselect? What fn should be responsible for controlling that flow?
   * TODO: In context of chainselect, what to return if e.g. a country has no states?
   *
   * @param string $fieldName
   * @param string $context
   * @see CRM_Core_DAO::buildOptionsContext
   * @param array $props
   *   whatever is known about this dao object.
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    $params = array();
    // Special logic for fields whose options depend on context or properties
    switch ($fieldName) {
      case 'contact_sub_type':
        if (!empty($props['contact_type'])) {
          $params['condition'] = "parent_id = (SELECT id FROM civicrm_contact_type WHERE name='{$props['contact_type']}')";
        }
        break;

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
    return CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, $params, $context);
  }

  /**
   * Delete a contact-related object that has an 'is_primary' field.
   *
   * Ensures that is_primary gets assigned to another object if available
   * Also calls pre/post hooks
   *
   * @param string $type
   * @param int $id
   * @return bool
   */
  public static function deleteObjectWithPrimary($type, $id) {
    if (!$id || !is_numeric($id)) {
      return FALSE;
    }
    $daoName = "CRM_Core_DAO_$type";
    $obj = new $daoName();
    $obj->id = $id;
    $obj->find();
    $hookParams = [];
    if ($obj->fetch()) {
      CRM_Utils_Hook::pre('delete', $type, $id, $hookParams);
      $contactId = $obj->contact_id;
      $obj->delete();
    }
    else {
      return FALSE;
    }
    // is_primary is only relavent if this field belongs to a contact
    if ($contactId) {
      $dao = new $daoName();
      $dao->contact_id = $contactId;
      $dao->is_primary = 1;
      // Pick another record to be primary (if one isn't already)
      if (!$dao->find(TRUE)) {
        $dao->is_primary = 0;
        $dao->find();
        if ($dao->fetch()) {
          $dao->is_primary = 1;
          $dao->save();
        }
      }
    }
    CRM_Utils_Hook::post('delete', $type, $id, $obj);
    $obj->free();
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function addSelectWhereClause() {
    // We always return an array with these keys, even if they are empty,
    // because this tells the query builder that we have considered these fields for acls
    $clauses = array(
      'id' => (array) CRM_Contact_BAO_Contact_Permission::cacheSubquery(),
      'is_deleted' => CRM_Core_Permission::check('access deleted contacts') ? array() : array('!= 1'),
    );
    CRM_Utils_Hook::selectWhereClause($this, $clauses);
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
   */
  public static function getDuplicateContacts($input, $contactType, $rule = 'Unsupervised', $excludedContactIDs = [], $checkPermissions = TRUE, $ruleGroupID = NULL, $contextParams = []) {
    $dedupeParams = CRM_Dedupe_Finder::formatParams($input, $contactType);
    $dedupeParams['check_permission'] = $checkPermissions;
    $dedupeParams['contact_type'] = $contactType;
    $dedupeParams['rule'] = $rule;
    $dedupeParams['rule_group_id'] = $ruleGroupID;
    $dedupeParams['excluded_contact_ids'] = $excludedContactIDs;
    $dedupeResults['ids'] = [];
    $dedupeResults['handled'] = FALSE;
    CRM_Utils_Hook::findDuplicates($dedupeParams, $dedupeResults, $contextParams);
    if (!$dedupeResults['handled']) {
      $dedupeResults['ids'] = CRM_Dedupe_Finder::dupesByParams($dedupeParams, $contactType, $rule, $excludedContactIDs, $ruleGroupID);
    }
    return $dedupeResults['ids'];
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
   * @return int|NULL
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
        return CRM_Utils_Array::value('hasLocationType', $field);
      }
    }
    return FALSE;
  }

  /**
   * @param array $appendProfiles
   *   Name of profile(s) to append to each link.
   *
   * @return array
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
          $links[] = array(
            'label' => $profile['title'],
            'url' => CRM_Utils_System::url('civicrm/profile/create', "reset=1&context=dialog&gid=$id",
              NULL, NULL, FALSE, FALSE, TRUE),
            'type' => ucfirst(str_replace('new_', '', $profile['name'])),
            'icon' => CRM_Utils_Array::value(str_replace('new_', '', $profile['name']), $icons),
          );
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
      ['key' => 'group', 'value' => ts('Group'), 'entity' => 'GroupContact'],
      ['key' => 'tag', 'value' => ts('Tag'), 'entity' => 'EntityTag'],
      ['key' => 'state_province', 'value' => ts('State/Province'), 'entity' => 'Address'],
      ['key' => 'country', 'value' => ts('Country'), 'entity' => 'Address'],
      ['key' => 'gender_id', 'value' => ts('Gender'), 'condition' => ['contact_type' => 'Individual']],
      ['key' => 'is_deceased', 'value' => ts('Deceased'), 'condition' => ['contact_type' => 'Individual']],
      ['key' => 'contact_id', 'value' => ts('Contact ID'), 'type' => 'text'],
      ['key' => 'external_identifier', 'value' => ts('External ID'), 'type' => 'text'],
      ['key' => 'source', 'value' => ts('Contact Source'), 'type' => 'text'],
    ];
  }

}
