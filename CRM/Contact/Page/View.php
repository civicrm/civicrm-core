<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Main page for viewing contact.
 *
 */
class CRM_Contact_Page_View extends CRM_Core_Page {

  /**
   * the id of the object being viewed (note/relationship etc)
   *
   * @int
   * @access protected
   */
  protected $_id;

  /**
   * the contact id of the contact being viewed
   *
   * @int
   * @access protected
   */
  protected $_contactId;

  /**
   * The action that we are performing
   *
   * @string
   * @access protected
   */
  protected $_action;

  /**
   * The permission we have on this contact
   *
   * @string
   * @access protected
   */
  protected $_permission;

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    // process url params
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->assign('id', $this->_id);

    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
    //validate the qfKey
    if (!CRM_Utils_Rule::qfKey($qfKey)) {
      $qfKey = NULL;
    }
    $this->assign('searchKey', $qfKey);

    // retrieve the group contact id, so that we can get contact id
    $gcid = CRM_Utils_Request::retrieve('gcid', 'Positive', $this);

    if (!$gcid) {
      $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    }
    else {
      $this->_contactId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_GroupContact', $gcid, 'contact_id');
    }

    if (!$this->_contactId) {
      CRM_Core_Error::statusBounce(
        ts('We could not find a contact id.'),
        CRM_Utils_System::url('civicrm/dashboard', 'reset=1')
      );
    }

    // ensure that the id does exist
    if ( CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', $this->_contactId, 'id' ) != $this->_contactId ) {
      CRM_Core_Error::statusBounce(
        ts('A Contact with that ID does not exist: %1', array(1 => $this->_contactId)),
        CRM_Utils_System::url('civicrm/dashboard', 'reset=1')
      );
    }

    $this->assign('contactId', $this->_contactId);

    // see if we can get prev/next positions from qfKey
    $navContacts = array(
      'prevContactID' => NULL,
      'prevContactName' => NULL,
      'nextContactID' => NULL,
      'nextContactName' => NULL,
      'nextPrevError' => 0,
    );
    if ($qfKey) {
      $pos = CRM_Core_BAO_PrevNextCache::getPositions("civicrm search $qfKey",
        $this->_contactId,
        $this->_contactId
      );
      $found = FALSE;

      if (isset($pos['prev'])) {
        $navContacts['prevContactID'] = $pos['prev']['id1'];
        $navContacts['prevContactName'] = $pos['prev']['data'];
        $found = TRUE;
      }

      if (isset($pos['next'])) {
        $navContacts['nextContactID'] = $pos['next']['id1'];
        $navContacts['nextContactName'] = $pos['next']['data'];
        $found = TRUE;
      }

      if (!$found) {
        // seems like we did not find any contacts
        // maybe due to bug CRM-9096
        // however we should account for 1 contact results (which dont have prev next)
        if (!$pos['foundEntry']) {
          $navContacts['nextPrevError'] = 1;
        }
      }
    }
    $this->assign($navContacts);

    $path = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->_contactId);
    CRM_Utils_System::appendBreadCrumb(array(array('title' => ts('View Contact'), 'url' => $path,)));

    if ($image_URL = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'image_URL')) {
      //CRM-7265 --time being fix.
      $config = CRM_Core_Config::singleton();
      $image_URL = str_replace('https://', 'http://', $image_URL);
      if (CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'enableSSL')) {
        $image_URL = str_replace('http://', 'https://', $image_URL);
      }

      list($imageWidth, $imageHeight) = getimagesize($image_URL);
      list($imageThumbWidth, $imageThumbHeight) = CRM_Contact_BAO_Contact::getThumbSize($imageWidth, $imageHeight);
      $this->assign("imageWidth", $imageWidth);
      $this->assign("imageHeight", $imageHeight);
      $this->assign("imageThumbWidth", $imageThumbWidth);
      $this->assign("imageThumbHeight", $imageThumbHeight);
      $this->assign("imageURL", $image_URL);
    }

    // also store in session for future use
    $session = CRM_Core_Session::singleton();
    $session->set('view.id', $this->_contactId);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);

    // check logged in user permission
    self::checkUserPermission($this);

    list($displayName, $contactImage,
      $contactType, $contactSubtype, $contactImageUrl
    ) = self::getContactDetails($this->_contactId);
    $this->assign('displayName', $displayName);

    $this->set('contactType', $contactType);
    $this->set('contactSubtype', $contactSubtype);

    // add to recently viewed block
    $isDeleted = (bool) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'is_deleted');

    $recentOther = array(
      'imageUrl' => $contactImageUrl,
      'subtype' => $contactSubtype,
      'isDeleted' => $isDeleted,
    );

    if (($session->get('userID') == $this->_contactId) ||
      CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)
    ) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/add', "reset=1&action=update&cid={$this->_contactId}");
    }

    if (($session->get('userID') != $this->_contactId) && CRM_Core_Permission::check('delete contacts')
      && !$isDeleted
    ) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/delete', "reset=1&delete=1&cid={$this->_contactId}");
    }

    CRM_Utils_Recent::add($displayName,
      CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->_contactId}"),
      $this->_contactId,
      $contactType,
      $this->_contactId,
      $displayName,
      $recentOther
    );
    $this->assign('isDeleted', $isDeleted);

    // set page title
    $title = self::setTitle($this->_contactId, $isDeleted);
    $this->assign('title', $title);
    
    // Check if this is default domain contact CRM-10482
    if (CRM_Contact_BAO_Contact::checkDomainContact($this->_contactId)) {
      $this->assign('domainContact', TRUE);
    } else {
      $this->assign('domainContact', FALSE);
    }

    // Add links for actions menu
    self::addUrls($this, $this->_contactId);

    if ($contactType == 'Organization' &&
      CRM_Core_Permission::check('administer Multiple Organizations') &&
      CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME,
        'is_enabled'
      )
    ) {
      //check is any relationship between the organization and groups
      $groupOrg = CRM_Contact_BAO_GroupOrganization::hasGroupAssociated($this->_contactId);
      if ($groupOrg) {
        $groupOrganizationUrl = CRM_Utils_System::url('civicrm/group',
          "reset=1&oid={$this->_contactId}"
        );
        $this->assign('groupOrganizationUrl', $groupOrganizationUrl);
      }
    }
  }

  /**
   * Get meta details of the contact.
   *
   * @return array contact fields in fixed order
   * @access public
   */
  static function getContactDetails($contactId) {
    return list($displayName,
      $contactImage,
      $contactType,
      $contactSubtype,
      $contactImageUrl
    ) = CRM_Contact_BAO_Contact::getDisplayAndImage($contactId,
      TRUE,
      TRUE
    );
  }

  static function checkUserPermission($page, $contactID = NULL) {
    // check for permissions
    $page->_permission = NULL;

    if (!$contactID) {
      $contactID = $page->_contactId;
    }

    // automatically grant permissin for users on their own record. makes
    // things easier in dashboard
    $session = CRM_Core_Session::singleton();

    if ($session->get('userID') == $contactID) {
      $page->assign('permission', 'edit');
      $page->_permission = CRM_Core_Permission::EDIT;
      // deleted contacts’ stuff should be (at best) only viewable
    }
    elseif (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'is_deleted') and CRM_Core_Permission::check('access deleted contacts')) {
      $page->assign('permission', 'view');
      $page->_permission = CRM_Core_Permission::VIEW;
    }
    elseif (CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::EDIT)) {
      $page->assign('permission', 'edit');
      $page->_permission = CRM_Core_Permission::EDIT;
    }
    elseif (CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::VIEW)) {
      $page->assign('permission', 'view');
      $page->_permission = CRM_Core_Permission::VIEW;
    }
    else {
      $session->pushUserContext(CRM_Utils_System::url('civicrm', 'reset=1'));
      CRM_Core_Error::statusBounce(ts('You do not have the necessary permission to view this contact.'));
    }
  }

  static function setTitle($contactId, $isDeleted = FALSE) {
    static $contactDetails;
    $displayName = $contactImage = NULL;
    if (!isset($contactDetails[$contactId])) {
      list($displayName, $contactImage) = self::getContactDetails($contactId);
      $contactDetails[$contactId] = array(
        'displayName' => $displayName,
        'contactImage' => $contactImage,
      );
    }
    else {
      $displayName = $contactDetails[$contactId]['displayName'];
      $contactImage = $contactDetails[$contactId]['contactImage'];
    }

    // set page title
    $title = "{$contactImage} {$displayName}";
    if ($isDeleted) {
      $title = "<del>{$title}</del>";
    }

    // Inline-edit places its own title on the page
    CRM_Utils_System::setTitle('CiviCRM', '<span id="crm-remove-title" style="display:none">CiviCRM</span>');

    return $title;
  }

  /**
   * Add urls for display in the actions menu
   */
  static function addUrls(&$obj, $cid) {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();
    $uid = CRM_Core_BAO_UFMatch::getUFId($cid);
    if ($uid) {
      // To do: we should also allow drupal users with CRM_Core_Permission::check( 'view user profiles' ) true to access $userRecordUrl
      // but this is currently returning false regardless of permission set for the role. dgg
      if ($config->userSystem->is_drupal == '1' &&
        ($session->get('userID') == $cid || CRM_Core_Permission::check('administer users'))
      ) {
        $userRecordUrl = CRM_Utils_System::url('user/' . $uid);
      }
      elseif ($config->userFramework == 'Joomla') {
        $userRecordUrl = NULL;
        // if logged in user is super user, then he can view other users joomla profile
        if (JFactory::getUser()->authorise('core.admin')) {
          $userRecordUrl = $config->userFrameworkBaseURL . "index.php?option=com_users&view=user&task=user.edit&id=" . $uid;
        }
        elseif ($session->get('userID') == $cid) {
          $userRecordUrl = $config->userFrameworkBaseURL . "index.php?option=com_admin&view=profile&layout=edit&id=" . $uid;
        }
      }
      else {
        $userRecordUrl = NULL;
      }
      $obj->assign('userRecordUrl', $userRecordUrl);
      $obj->assign('userRecordId', $uid);
    }
    elseif (($config->userSystem->is_drupal == '1' && CRM_Core_Permission::check('administer users')) ||
      ($config->userFramework == 'Joomla' &&
        JFactory::getUser()->authorise('core.create', 'com_users')
      )
    ) {
      $userAddUrl = CRM_Utils_System::url('civicrm/contact/view/useradd',
        'reset=1&action=add&cid=' . $cid
      );
      $obj->assign('userAddUrl', $userAddUrl);
    }

    if (CRM_Core_Permission::check('access Contact Dashboard')) {
      $dashboardURL = CRM_Utils_System::url('civicrm/user',
        "reset=1&id={$cid}"
      );
      $obj->assign('dashboardURL', $dashboardURL);
    }

    // See if other modules want to add links to the activtity bar
    $hookLinks = CRM_Utils_Hook::links('view.contact.activity',
      'Contact',
      $cid,
      CRM_Core_DAO::$_nullObject,
      CRM_Core_DAO::$_nullObject
    );
    if (is_array($hookLinks)) {
      $obj->assign('hookLinks', $hookLinks);
    }
  }
}

