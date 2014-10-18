<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_BAO_Domain extends CRM_Core_DAO_Domain {

  /**
   * Cache for the current domain object
   */
  static $_domain = NULL;

  /**
   * Cache for a domain's location array
   */
  private $_location = NULL;

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_DAO_Domain object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_Domain', $params, $defaults);
  }

  /**
   * Get the domain BAO
   *
   * @param null $reset
   *
   * @return null|object CRM_Core_BAO_Domain
   * @access public
   * @static
   */
  static function &getDomain($reset = null) {
    static $domain = NULL;
    if (!$domain || $reset) {
      $domain = new CRM_Core_BAO_Domain();
      $domain->id = CRM_Core_Config::domainID();
      if (!$domain->find(TRUE)) {
        CRM_Core_Error::fatal();
      }
    }
    return $domain;
  }

 /**
  * Change active domain (ie. to perform a temporary action) such as changing
  * config for all domains
  *
  * Switching around the global domain variable is very risky business. This
  * is ONLY used as a hack to allow CRM_Core_BAO_Setting::setItems to manipulate
  * the civicrm_domain.config_backend in multiple domains. When/if config_backend
  * goes away, this hack should be removed.
  *
  * @param integer $domainID id for domain you want to set as current
  * @deprecated
  * @see http://issues.civicrm.org/jira/browse/CRM-11204
  */
  static function setDomain($domainID){
    CRM_Core_Config::domainID($domainID);
    self::getDomain($domainID);
    CRM_Core_Config::singleton(TRUE, TRUE);
  }

  /**
   * Reset domain to default (ie. as loaded from settings). This is the
   * counterpart to CRM_Core_BAO_Domain::setDomain.
   *
   * @internal param int $domainID id for domain you want to set as current
   * @deprecated
   * @see CRM_Core_BAO_Domain::setDomain
   */
  static function resetDomain(){
    CRM_Core_Config::domainID(null, true);
    self::getDomain(null, true);
    CRM_Core_Config::singleton(TRUE, TRUE);
  }

  /**
   * @param bool $skipUsingCache
   *
   * @return null|string
   */
  static function version( $skipUsingCache = false ) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain',
      CRM_Core_Config::domainID(),
                                       'version',
                                       'id',
                                       $skipUsingCache
    );
  }

  /**
   * Get the location values of a domain
   *
   * @param NULL
   *
   * @return array        Location::getValues
   * @access public
   */
  function &getLocationValues() {
    if ($this->_location == NULL) {
      $domain = self::getDomain(null);
      $params = array(
        'contact_id' => $domain->contact_id
      );
      $this->_location = CRM_Core_BAO_Location::getValues($params, TRUE);

      if (empty($this->_location)) {
        $this->_location = NULL;
      }
    }
    return $this->_location;
  }

  /**
   * Save the values of a domain
   *
   * @param $params
   * @param $id
   *
   * @return domain array
   * @access public
   */
  static function edit(&$params, &$id) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->id = $id;
    $domain->copyValues($params);
    $domain->save();
    return $domain;
  }

  /**
   * Create a new domain
   *
   * @param $params
   *
   * @return domain array
   * @access public
   */
  static function create($params) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->copyValues($params);
    $domain->save();
    return $domain;
  }

  /**
   * @return bool
   */
  static function multipleDomains() {
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
   *
   * @return array name & email for domain
   * @throws Exception
   */
  static function getNameAndEmail($skipFatal = FALSE) {
    $fromEmailAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
    if (!empty($fromEmailAddress)) {
      foreach ($fromEmailAddress as $key => $value) {
        $email     = CRM_Utils_Mail::pluckEmailFromHeader($value);
        $fromArray = explode('"', $value);
        $fromName  = CRM_Utils_Array::value(1, $fromArray);
        break;
      }
      return array($fromName, $email);
    }
    elseif ($skipFatal) {
      return array('', '');
    }

    $url = CRM_Utils_System::url('civicrm/admin/domain',
      'action=update&reset=1'
    );
    $status = ts("There is no valid default from email address configured for the domain. You can configure here <a href='%1'>Configure From Email Address.</a>", array(1 => $url));

    CRM_Core_Error::fatal($status);
  }

  /**
   * @param $contactID
   *
   * @return bool|null|object|string
   */
  static function addContactToDomainGroup($contactID) {
    $groupID = self::getGroupId();

    if ($groupID) {
      $contactIDs = array($contactID);
      CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs, $groupID);

      return $groupID;
    }
    return FALSE;
  }

  /**
   * @return bool|null|object|string
   */
  static function getGroupId() {
    static $groupID = NULL;

    if ($groupID) {
      return $groupID;
    }

    $domainGroupID = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME,
      'domain_group_id'
    );
    $multisite = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME,
      'is_enabled'
    );

    if ($domainGroupID) {
      $groupID = $domainGroupID;
    }
    elseif ($multisite) {
      // create a group with that of domain name
      $title = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain',
        CRM_Core_Config::domainID(), 'name'
      );
      $groupID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group',
        $title, 'id', 'title', true
      );
      if (empty($groupID) && !empty($title)) {
        $groupParams = array(
          'title' => $title,
          'is_active' => 1,
          'no_parent' => 1,
        );
        $group = CRM_Contact_BAO_Group::create($groupParams);
        $groupID = $group->id;
      }
    }
    return $groupID ? $groupID : FALSE;
  }

  /**
   * @param $groupId
   *
   * @return bool
   */
  static function isDomainGroup($groupId) {
    $domainGroupID = self::getGroupId();
    return $domainGroupID == $groupId ? TRUE : FALSE;
  }

  /**
   * @return array
   */
  static function getChildGroupIds() {
    $domainGroupID = self::getGroupId();
    $childGrps = array();

    if ($domainGroupID) {
      $childGrps = CRM_Contact_BAO_GroupNesting::getChildGroupIds($domainGroupID);
      $childGrps[] = $domainGroupID;
    }
    return $childGrps;
  }

  // function to retrieve a list of contact-ids that belongs to current domain/site.
  /**
   * @return array
   */
  static function getContactList() {
    $siteGroups = CRM_Core_BAO_Domain::getChildGroupIds();
    $siteContacts = array();

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
}

