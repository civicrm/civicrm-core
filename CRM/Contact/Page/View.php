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
 * Main page for viewing contact.
 */
class CRM_Contact_Page_View extends CRM_Core_Page {
  use CRM_Contact_Form_ContactFormTrait;

  /**
   * The id of the object being viewed (note/relationship etc)
   *
   * @var int
   */
  protected $_id;

  /**
   * The contact id of the contact being viewed
   *
   * @var int
   */
  protected $_contactId;

  /**
   * The action that we are performing
   *
   * @var string
   */
  protected $_action;

  /**
   * The permission we have on this contact
   *
   * @var string
   */
  public $_permission;

  /**
   * Heart of the viewing process.
   *
   * The runner gets all the meta data for the contact and calls the appropriate type of page to view.
   */
  public function preProcess() {
    // process url params
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->assign('id', $this->_id);

    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
    //validate the qfKey
    if (!CRM_Utils_Rule::qfKey($qfKey)) {
      $qfKey = NULL;
    }
    $this->assign('searchKey', $qfKey);
    $this->assign('contactId', $this->getContactID());

    // see if we can get prev/next positions from qfKey
    $navContacts = [
      'prevContactID' => NULL,
      'prevContactName' => NULL,
      'nextContactID' => NULL,
      'nextContactName' => NULL,
      'nextPrevError' => 0,
    ];
    if ($qfKey) {
      $pos = Civi::service('prevnext')->getPositions("civicrm search $qfKey",
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

      $context = $_GET['context'] ?? NULL;
      if (!$found) {
        // seems like we did not find any contacts
        // maybe due to bug CRM-9096
        // however we should account for 1 contact results (which dont have prev next)
        if (!$pos['foundEntry']) {
          $navContacts['nextPrevError'] = 1;
        }
      }
      elseif ($context) {
        $this->assign('context', $context);
        CRM_Utils_System::appendBreadCrumb([
          [
            'title' => ts('Search Results'),
            'url' => CRM_Utils_System::url("civicrm/contact/search/$context", ['qfKey' => $qfKey]),
          ],
        ]);
      }
    }
    $this->assign($navContacts);

    $path = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->_contactId);
    CRM_Utils_System::appendBreadCrumb([['title' => ts('View Contact'), 'url' => $path]]);

    $image_URL = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'image_URL');
    $this->assign('imageURL', $image_URL ? CRM_Utils_File::getImageURL($image_URL) : '');

    // also store in session for future use
    $session = CRM_Core_Session::singleton();
    $session->set('view.id', $this->_contactId);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);

    // check logged in user permission
    self::checkUserPermission($this);

    [$displayName, $contactImage, $contactType, $contactSubtype, $contactImageUrl] = self::getContactDetails($this->_contactId);
    $this->assign('displayName', $displayName);

    $this->set('contactType', $contactType);

    // note: there could still be multiple subtypes. We just trimming the outer separator.
    $this->set('contactSubtype', trim(($contactSubtype ?? ''), CRM_Core_DAO::VALUE_SEPARATOR));

    // add to recently viewed block
    $isDeleted = (bool) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'is_deleted');

    $recentOther = [
      'imageUrl' => $contactImageUrl,
      'is_deleted' => $isDeleted,
    ];

    if (CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)) {
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
      'Contact',
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
    }
    else {
      $this->assign('domainContact', FALSE);
    }

    // Add links for actions menu
    self::addUrls($this, $this->_contactId);
    $this->assign('groupOrganizationUrl', $this->getGroupOrganizationUrl($contactType));

    // Assign deleteURL variable, used as part of ContactImage.tpl
    self::$_template->ensureVariablesAreAssigned(['deleteURL']);
  }

  /**
   * Get meta details of the contact.
   *
   * @param int $contactId
   *
   * @return array
   *   contact fields in fixed order
   */
  public static function getContactDetails($contactId) {
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

  /**
   * @param CRM_Core_Page $page
   * @param int $contactID
   */
  public static function checkUserPermission($page, $contactID = NULL) {
    // check for permissions
    $page->_permission = NULL;

    if (!$contactID) {
      $contactID = $page->_contactId;
    }

    // automatically grant permission for users on their own record. makes
    // things easier in dashboard
    $session = CRM_Core_Session::singleton();

    if ($session->get('userID') == $contactID && CRM_Core_Permission::check('edit my contact')) {
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

  /**
   * @param int $contactId
   * @param bool $isDeleted
   *
   * @return string
   */
  public static function setTitle($contactId, $isDeleted = FALSE) {
    static $contactDetails;
    $contactImage = NULL;
    if (!isset($contactDetails[$contactId])) {
      [$displayName, $contactImage] = self::getContactDetails($contactId);
      $contactDetails[$contactId] = [
        'displayName' => $displayName,
        'contactImage' => $contactImage,
        'isDeceased' => (bool) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'is_deceased'),
      ];
    }
    else {
      $displayName = $contactDetails[$contactId]['displayName'];
      $contactImage = $contactDetails[$contactId]['contactImage'];
    }

    // set page title
    $title = "{$contactImage} {$displayName}";
    if ($contactDetails[$contactId]['isDeceased']) {
      $title .= '  <span class="crm-contact-deceased">(' . ts('deceased') . ')</span>';
    }
    if ($isDeleted) {
      $title = "<del>{$title}</del>";
      try {
        $mergedTo = civicrm_api3('Contact', 'getmergedto', ['contact_id' => $contactId, 'api.Contact.get' => ['return' => 'display_name']]);
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Session::singleton()->setStatus(ts('This contact was deleted during a merge operation. The contact it was merged into cannot be found and may have been deleted.'));
        $mergedTo = ['count' => 0];
      }
      if ($mergedTo['count']) {
        $mergedToContactID = $mergedTo['id'];
        $mergedToDisplayName = $mergedTo['values'][$mergedToContactID]['api.Contact.get']['values'][0]['display_name'];
        $title .= ' ' . ts('(This contact has been merged to <a href="%1">%2</a>)', [
          1 => CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'cid' => $mergedToContactID]),
          2 => $mergedToDisplayName,
        ]);
      }
    }

    // Inline-edit places its own title on the page
    CRM_Utils_System::setTitle('CiviCRM', '<span id="crm-remove-title" style="display:none">CiviCRM</span>');

    return $title;
  }

  /**
   * Add urls for display in the actions menu.
   * @param CRM_Core_Page $obj
   * @param int $cid
   */
  public static function addUrls(&$obj, $cid) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($cid);
    $obj->assign('userRecordId', $uid);
    $userRecordUrl = '';
    if ($uid) {
      $userRecordUrl = CRM_Core_Config::singleton()->userSystem->getUserRecordUrl($cid);
    }
    elseif (CRM_Core_Config::singleton()->userSystem->checkPermissionAddUser()) {
      $userAddUrl = CRM_Utils_System::url('civicrm/contact/view/useradd', 'reset=1&action=add&cid=' . $cid);
      $obj->assign('userAddUrl', $userAddUrl);
    }
    $obj->assign('userRecordUrl', $userRecordUrl);

    // See if other modules want to add links to the activtity bar
    $hookLinks = [];
    CRM_Utils_Hook::links('view.contact.activity',
      'Contact',
      $cid,
      $hookLinks
    );
    if (is_array($hookLinks)) {
      $obj->assign('hookLinks', $hookLinks);
    }
  }

  /**
   * @param string $contactType
   *
   * @return string
   */
  protected function getGroupOrganizationUrl(string $contactType): string {
    if ($contactType !== 'Organization' || !CRM_Core_Permission::check('administer Multiple Organizations')
      || !CRM_Contact_BAO_GroupOrganization::hasGroupAssociated($this->_contactId)
      || !Civi::settings()->get('is_enabled')
    ) {
      return '';
    }
    return CRM_Utils_System::url('civicrm/group', "reset=1&oid={$this->_contactId}");
  }

  /**
   * @throws \CRM_Core_Exception
   *
   * @api This function will not change in a minor release and is supported for
   *  use outside of core. This annotation / external support for properties
   *  is only given where there is specific test cover.
   */
  public function getContactID(): int {
    if (!isset($this->_contactId)) {
      // retrieve the group contact id, so that we can get contact id
      $gcid = CRM_Utils_Request::retrieve('gcid', 'Positive', $this);

      if (!$gcid) {
        $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
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
      if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'id') != $this->_contactId) {
        CRM_Core_Error::statusBounce(
          ts('A Contact with that ID does not exist: %1', [1 => $this->_contactId]),
          CRM_Utils_System::url('civicrm/dashboard', 'reset=1')
        );
      }
    }
    return $this->_contactId;
  }

}
