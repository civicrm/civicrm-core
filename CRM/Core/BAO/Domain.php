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
    Civi::$statics[__CLASS__]['current'] = NULL;
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_DAO_Domain
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_Domain', $params, $defaults);
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
   * @param string|number $min
   * @return bool
   */
  public static function isDBVersionAtLeast($min) {
    return version_compare(self::version(), $min, '>=');
  }

  /**
   * Get the location values of a domain.
   *
   * @return CRM_Core_BAO_Location[]|NULL
   */
  public function getLocationValues() {
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
   * @return CRM_Core_DAO_Domain
   */
  public static function edit($params, $id) {
    $params['id'] = $id;
    return self::writeRecord($params);
  }

  /**
   * Create or update domain.
   *
   * @param array $params
   * @return CRM_Core_DAO_Domain
   */
  public static function create($params) {
    return self::writeRecord($params);
  }

  /**
   * @return bool
   */
  public static function multipleDomains() {
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
   * @param bool $returnString
   *
   * @return array
   *   name & email for domain
   *
   * @throws \CRM_Core_Exception
   */
  public static function getNameAndEmail($skipFatal = FALSE, $returnString = FALSE) {
    $fromEmailAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
    if (!empty($fromEmailAddress)) {
      if ($returnString) {
        // Return a string like: "Demonstrators Anonymous" <info@example.org>
        return $fromEmailAddress;
      }
      foreach ($fromEmailAddress as $key => $value) {
        $email = CRM_Utils_Mail::pluckEmailFromHeader($value);
        $fromArray = explode('"', $value);
        $fromName = $fromArray[1] ?? NULL;
        break;
      }
      return [$fromName, $email];
    }

    if ($skipFatal) {
      return [NULL, NULL];
    }

    $url = CRM_Utils_System::url('civicrm/admin/options/from_email_address',
      'reset=1'
    );
    $status = ts("There is no valid default from email address configured for the domain. You can configure here <a href='%1'>Configure From Email Address.</a>", [1 => $url]);

    throw new CRM_Core_Exception($status);
  }

  /**
   * @param int $contactID
   *
   * @return bool|null|object|string
   *
   * @throws \CRM_Core_Exception
   */
  public static function addContactToDomainGroup($contactID) {
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
    $multisite = Civi::settings()->get('is_enabled');

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
    return $groupID ? $groupID : FALSE;
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
   * @throws \CiviCRM_API3_Exception
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
      list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);
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
    return "do-not-reply@$emailDomain";
  }

}
