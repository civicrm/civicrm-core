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
 *
 */
class CRM_Profile_Page_View extends CRM_Core_Page {

  /**
   * The id of the contact.
   *
   * @var int
   */
  protected $_id;

  /**
   * The group id that we are editing.
   *
   * @var int
   */
  protected $_gid;

  /**
   * Should the primary email be converted into a link, if emailabe.
   *
   * @var bool
   */
  protected $isShowEmailTaskLink = FALSE;

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE
    );
    $this->isShowEmailTaskLink = CRM_Utils_Request::retrieve('is_show_email_task', 'Positive', $this);
    if (!$this->_id) {
      $session = CRM_Core_Session::singleton();
      $this->_id = $session->get('userID');
      if (!$this->_id) {
        CRM_Core_Error::statusBounce(ts('Could not find the required contact id parameter (id=) for viewing a contact record with a Profile.'));
      }
    }
    $this->assign('cid', $this->_id);

    $gids = explode(',', CRM_Utils_Request::retrieve('gid', 'String', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET'));

    $profileIds = [];
    if (count($gids) > 1) {
      if (!empty($gids)) {
        foreach ($gids as $pfId) {
          $profileIds[] = CRM_Utils_Type::escape($pfId, 'Positive');
        }
      }

      // check if we are rendering mixed profiles
      if (CRM_Core_BAO_UFGroup::checkForMixProfiles($profileIds)) {
        CRM_Core_Error::statusBounce(ts('You cannot combine profiles of multiple types.'));
      }

      $this->_gid = $profileIds[0];
    }

    if (!$this->_gid) {
      $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE, 0, 'GET');
    }

    $anyContent = TRUE;
    if ($this->_gid) {
      $page = new CRM_Profile_Page_Dynamic($this->_id, $this->_gid, 'Profile', FALSE, $profileIds, $this->isShowEmailTaskLink);
      $profileGroup = [];
      $profileGroup['title'] = NULL;
      $profileGroup['content'] = $page->run();
      if (empty($profileGroup['content'])) {
        $anyContent = FALSE;
      }
      $profileGroups[] = $profileGroup;

      $gidString = $this->_gid;
      if (!empty($profileIds)) {
        $gidString = implode(',', $profileIds);
      }

      $map = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'is_map');
      if ($map) {
        $this->assign('mapURL',
          CRM_Utils_System::url("civicrm/profile/map",
            "reset=1&pv=1&cid={$this->_id}&gid={$gidString}"
          )
        );
      }
      if (CRM_Core_Permission::ufGroupValid($this->_gid,
        CRM_Core_Permission::SEARCH
      )
      ) {
        $this->assign('listingURL',
          CRM_Utils_System::url("civicrm/profile",
            "force=1&gid={$gidString}"
          )
        );
      }
    }
    else {
      $ufGroups = CRM_Core_BAO_UFGroup::getModuleUFGroup('Profile');

      $profileGroups = [];
      foreach ($ufGroups as $groupid => $group) {
        $page = new CRM_Profile_Page_Dynamic($this->_id, $groupid, 'Profile', FALSE, $profileIds);
        $profileGroup = [];
        $profileGroup['title'] = $group['title'];
        $profileGroup['content'] = $page->run();
        if (empty($profileGroup['content'])) {
          $anyContent = FALSE;
        }
        $profileGroups[] = $profileGroup;
      }
      $this->assign('listingURL',
        CRM_Utils_System::url("civicrm/profile",
          "force=1"
        )
      );
    }

    $this->assign('groupID', $this->_gid);

    $this->assign('profileGroups', $profileGroups);
    $this->assign('recentlyViewed', FALSE);

    // do not set title if there is no content
    // CRM-6081
    if (!$anyContent) {
      CRM_Utils_System::setTitle('');
    }
  }

  /**
   * Build the outcome basing on the CRM_Profile_Page_Dynamic's HTML.
   *
   */
  public function run() {
    $this->preProcess();
    return parent::run();
  }

  /**
   * Check template file exists.
   *
   * @param string|null $suffix
   *
   * @return string|null
   *   Template file path, else null
   */
  public function checkTemplateFileExists($suffix = NULL) {
    if ($this->_gid) {
      $templateFile = "CRM/Profile/Page/{$this->_gid}/View.{$suffix}tpl";
      $template = CRM_Core_Page::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }

      // lets see if we have customized by name
      $ufGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'name');
      if ($ufGroupName) {
        $templateFile = "CRM/Profile/Page/{$ufGroupName}/View.{$suffix}tpl";
        if ($template->template_exists($templateFile)) {
          return $templateFile;
        }
      }
    }
    return NULL;
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ?: parent::getTemplateFileName();
  }

  /**
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we dont override
   *
   * @return string
   */
  public function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ?: parent::overrideExtraTemplateFileName();
  }

}
