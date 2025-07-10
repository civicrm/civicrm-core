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
 *
 */
class CRM_Core_BAO_Domain extends CRM_Core_DAO_Domain {

  /**
   * Cache for a domain's location array
   * @var array
   */
  private $_location = NULL;

  /**
   * Flushes the cache set by getDomain.
   *
   * @see CRM_Core_BAO_Domain::getDomain()
   * @param CRM_Core_DAO_Domain $domain
   */
  public static function onPostSave($domain) {
    // We want to clear out any cached tokens.
    // Editing a domain is so rare we can risk being heavy handed.
    Civi::cache('metadata')->clear();
    Civi::$statics[__CLASS__]['current'] = NULL;
  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Get the current domain.
   *
   * @return \CRM_Core_BAO_Domain
   * @throws \CRM_Core_Exception
   */
  public static function getDomain() {
    $domain = Civi::$statics[__CLASS__]['current'] ?? NULL;
    if (!$domain) {
      $domain = new CRM_Core_BAO_Domain();
      $domain->id = CRM_Core_Config::domainID();
      if (!$domain->find(TRUE)) {
        throw new CRM_Core_Exception('No domain in DB');
      }
      Civi::$statics[__CLASS__]['current'] = $domain;
    }
    return $domain;
  }

  /**
   * @param bool $skipUsingCache
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  public static function version($skipUsingCache = FALSE) {
    if ($skipUsingCache) {
      Civi::$statics[__CLASS__]['current'] = NULL;
    }

    return self::getDomain()->version;
  }

  /**
   * Is a database update required to apply latest schema changes.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public static function isDBUpdateRequired() {
    $dbVersion = self::version();
    $codeVersion = CRM_Utils_System::version();
    return version_compare($dbVersion, $codeVersion) < 0;
  }

  /**
   * Checks that the current DB schema is at least $min version
   *
   * @param string|int $min
   * @return bool
   */
  public static function isDBVersionAtLeast($min) {
    return version_compare(self::version(), $min, '>=');
  }

  /**
   * @return string
   */
  protected static function getMissingDomainFromEmailMessage(): string {
    $url = CRM_Utils_System::url('civicrm/admin/options/site_email_address',
      'reset=1'
    );
    $status = ts("There is no valid default email address configured for the site. <a href='%1'>Configure Site From Email Addresses.</a>", [1 => $url]);
    return $status;
  }

  /**
   * Get the location values of a domain.
   *
   * @return CRM_Core_BAO_Location[]|NULL
   *
   * @deprecated since 6.3 will be removed around 6.13.
   */
  public function getLocationValues() {
    CRM_Core_Error::deprecatedFunctionWarning('use the api');
    if ($this->_location == NULL) {
      $params = [
        'contact_id' => $this->contact_id,
      ];
      $this->_location = CRM_Core_BAO_Location::getValues($params, TRUE);

      if (empty($this->_location)) {
        $this->_location = NULL;
      }
    }
    return $this->_location;
  }

  /**
   * Update a domain.
   *
   * @param array $params
   * @param int $id
   *
   * @deprecated
   * @return CRM_Core_DAO_Domain
   * @throws \CRM_Core_Exception
   */
  public static function edit($params, $id): CRM_Core_DAO_Domain {
    $params['id'] = $id;
    return self::writeRecord($params);
  }

  /**
   * Create or update domain.
   *
   * @deprecated
   * @param array $params
   * @return CRM_Core_DAO_Domain
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * @deprecated
   * @return bool
   */
  public static function multipleDomains() {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    $session = CRM_Core_Session::singleton();

    $numberDomains = $session->get('numberDomains');
    if (!$numberDomains) {
      $query = 'SELECT count(*) from civicrm_domain';
      $numberDomains = CRM_Core_DAO::singleValueQuery($query);
      $session->set('numberDomains', $numberDomains);
    }
    return $numberDomains > 1;
  }

  /**
   * @param bool $skipFatal
   * @param bool $returnFormatted
   *   Deprecated param. Use `getFromEmail()` instead.
   *
   * @return array
   *   name & email for domain
   *
   * @throws \CRM_Core_Exception
   */
  public static function getNameAndEmail($skipFatal = FALSE, $returnFormatted = FALSE): array {
    $fromEmailAddress = \Civi\Api4\SiteEmailAddress::get(FALSE)
      ->addSelect('display_name', 'email')
      ->addWhere('domain_id', '=', 'current_domain')
      ->addWhere('is_default', '=', TRUE)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->first();
    if (!empty($fromEmailAddress)) {
      if ($returnFormatted) {
        CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_BAO_Domain::getFromEmail', 'CRM_Core_BAO_Domain::getNameAndEmail with $returnFormatted = TRUE');
        return [CRM_Utils_Mail::formatFromAddress($fromEmailAddress)];
      }
      return [$fromEmailAddress['display_name'], $fromEmailAddress['email']];
    }

    if ($skipFatal) {
      return [NULL, NULL];
    }

    $status = self::getMissingDomainFromEmailMessage();

    throw new CRM_Core_Exception($status);
  }

  /**
   * Get the domain email in a format suitable for using as the from address.
   *
   * @return string
   *   E.g. '"Demonstrators Anonymous" <info@example.org>'
   * @throws \CRM_Core_Exception
   */
  public static function getFromEmail(): string {
    $fromAddress = self::getNameAndEmail();
    return CRM_Utils_Mail::formatFromAddress(['display_name' => $fromAddress[0], 'email' => $fromAddress[1]]);
  }

  /**
   * @param int $contactID
   * @return bool|int
   * @deprecated
   * @throws \CRM_Core_Exception
   */
  public static function addContactToDomainGroup($contactID) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Contact_BAO_GroupContact::addContactsToGroup');
    $groupID = self::getGroupId();

    if ($groupID) {
      $contactIDs = [$contactID];
      CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs, $groupID);

      return $groupID;
    }
    return FALSE;
  }

  /**
   * @return bool|null|object|string
   *
   * @throws \CRM_Core_Exception
   */
  public static function getGroupId() {
    static $groupID = NULL;

    if ($groupID) {
      return $groupID;
    }

    $domainGroupID = Civi::settings()->get('domain_group_id');
    $multisite = Civi::settings()->get('multisite_is_enabled');

    if ($domainGroupID) {
      $groupID = $domainGroupID;
    }
    elseif ($multisite) {
      // create a group with that of domain name
      $title = self::getDomain()->name;
      $groupID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group',
        $title, 'id', 'title', TRUE
      );
    }
    return $groupID ?: FALSE;
  }

  /**
   * @param int $groupId
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public static function isDomainGroup($groupId) {
    $domainGroupID = self::getGroupId();
    return $domainGroupID == (bool) $groupId;
  }

  /**
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function getChildGroupIds() {
    $domainGroupID = self::getGroupId();
    $childGrps = [];

    if ($domainGroupID) {
      $childGrps = CRM_Contact_BAO_GroupNesting::getChildGroupIds($domainGroupID);
      $childGrps[] = $domainGroupID;
    }
    return $childGrps;
  }

  /**
   * Retrieve a list of contact-ids that belongs to current domain/site.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function getContactList() {
    $siteGroups = CRM_Core_BAO_Domain::getChildGroupIds();
    $siteContacts = [];

    if (!empty($siteGroups)) {
      $query = "
 SELECT      cc.id
 FROM        civicrm_contact cc
 INNER JOIN  civicrm_group_contact gc ON
           (gc.contact_id = cc.id AND gc.status = 'Added' AND gc.group_id IN (" . implode(',', $siteGroups) . "))";

      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $siteContacts[] = $dao->id;
      }
    }
    return $siteContacts;
  }

  /**
   * CRM-20308 & CRM-19657
   * Return domain information / user information for the usage in receipts
   * Try default from address then fall back to using logged in user details
   *
   * @throws \CRM_Core_Exception
   */
  public static function getDefaultReceiptFrom() {
    $domain = civicrm_api3('domain', 'getsingle', ['id' => CRM_Core_Config::domainID()]);
    if (!empty($domain['from_email'])) {
      return [$domain['from_name'], $domain['from_email']];
    }
    if (!empty($domain['domain_email'])) {
      return [$domain['name'], $domain['domain_email']];
    }
    $userName = '';
    $userEmail = '';

    if (!Civi::settings()->get('allow_mail_from_logged_in_contact')) {
      return [$userName, $userEmail];
    }

    $userID = CRM_Core_Session::getLoggedInContactID();
    if (!empty($userID)) {
      [$userName, $userEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);
    }
    // If still empty fall back to the logged in user details.
    // return empty values no matter what.
    return [$userName, $userEmail];
  }

  /**
   * Get address to be used for system from addresses when a reply is not expected.
   */
  public static function getNoReplyEmailAddress() {
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();
    $noReplyAddress = Civi::settings()->get('no_reply_email_address');

    return $noReplyAddress ?: "do-not-reply@$emailDomain";
  }

}
