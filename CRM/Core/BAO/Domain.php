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
   * Cache for the current domain object.
   * @var object
   */
  public static $_domain = NULL;

  /**
   * Cache for a domain's location array
   * @var array
   */
  private $_location = NULL;

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
   * Get the domain BAO.
   *
   * @param bool $reset
   *
   * @return \CRM_Core_BAO_Domain
   * @throws \CRM_Core_Exception
   */
  public static function getDomain($reset = NULL) {
    static $domain = NULL;
    if (!$domain || $reset) {
      $domain = new CRM_Core_BAO_Domain();
      $domain->id = CRM_Core_Config::domainID();
      if (!$domain->find(TRUE)) {
        throw new CRM_Core_Exception('No domain in DB');
      }
    }
    return $domain;
  }

  /**
   * @param bool $skipUsingCache
   *
   * @return null|string
   */
  public static function version($skipUsingCache = FALSE) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain',
      CRM_Core_Config::domainID(),
      'version',
      'id',
      $skipUsingCache
    );
  }

  /**
   * Get the location values of a domain.
   *
   * @return array
   *   Location::getValues
   */
  public function &getLocationValues() {
    if ($this->_location == NULL) {
      $domain = self::getDomain(NULL);
      $params = [
        'contact_id' => $domain->contact_id,
      ];
      $this->_location = CRM_Core_BAO_Location::getValues($params, TRUE);

      if (empty($this->_location)) {
        $this->_location = NULL;
      }
    }
    return $this->_location;
  }

  /**
   * Save the values of a domain.
   *
   * @param array $params
   * @param int $id
   *
   * @return array
   *   domain
   */
  public static function edit(&$params, &$id) {
    CRM_Utils_Hook::pre('edit', 'Domain', CRM_Utils_Array::value('id', $params), $params);
    $domain = new CRM_Core_DAO_Domain();
    $domain->id = $id;
    $domain->copyValues($params);
    $domain->save();
    CRM_Utils_Hook::post('edit', 'Domain', $domain->id, $domain);
    return $domain;
  }

  /**
   * Create a new domain.
   *
   * @param array $params
   *
   * @return array
   *   domain
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Domain', CRM_Utils_Array::value('id', $params), $params);
    $domain = new CRM_Core_DAO_Domain();
    $domain->copyValues($params);
    $domain->save();
    CRM_Utils_Hook::post($hook, 'Domain', $domain->id, $domain);
    return $domain;
  }

  /**
   * @return bool
   */
  public static function multipleDomains() {
    $session = CRM_Core_Session::singleton();

    $numberDomains = $session->get('numberDomains');
    if (!$numberDomains) {
      $query = "SELECT count(*) from civicrm_domain";
      $numberDomains = CRM_Core_DAO::singleValueQuery($query);
      $session->set('numberDomains', $numberDomains);
    }
    return $numberDomains > 1 ? TRUE : FALSE;
  }

  /**
   * @param bool $skipFatal
   * @param bool $returnString
   * @return array
   *   name & email for domain
   * @throws Exception
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
        $fromName = CRM_Utils_Array::value(1, $fromArray);
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

    CRM_Core_Error::fatal($status);
  }

  /**
   * @param int $contactID
   *
   * @return bool|null|object|string
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
      $title = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain',
        CRM_Core_Config::domainID(), 'name'
      );
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
   */
  public static function isDomainGroup($groupId) {
    $domainGroupID = self::getGroupId();
    return $domainGroupID == $groupId ? TRUE : FALSE;
  }

  /**
   * @return array
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

    $userID = CRM_Core_Session::singleton()->getLoggedInContactID();
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
