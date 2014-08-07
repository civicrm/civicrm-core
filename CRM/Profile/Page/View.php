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
 * Main page for viewing contact.
 *
 */
class CRM_Profile_Page_View extends CRM_Core_Page {

  /**
   * The id of the contact
   *
   * @var int
   */
  protected $_id;

  /**
   * The group id that we are editing
   *
   * @var int
   */
  protected $_gid;

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE
    );
    if (!$this->_id) {
      $session = CRM_Core_Session::singleton();
      $this->_id = $session->get('userID');
      if (!$this->_id) {
        CRM_Core_Error::fatal(ts('Could not find the required contact id parameter (id=) for viewing a contact record with a Profile.'));
      }
    }
    $this->assign('cid', $this->_id);

    $gids = explode(',', CRM_Utils_Request::retrieve('gid', 'String', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET'));

    $profileIds = array();
    if (count($gids) > 1) {
      if (!empty($gids)) {
        foreach ($gids as $pfId) {
          $profileIds[] = CRM_Utils_Type::escape($pfId, 'Positive');
        }
      }

      // check if we are rendering mixed profiles
      if (CRM_Core_BAO_UFGroup::checkForMixProfiles($profileIds)) {
        CRM_Core_Error::fatal(ts('You cannot combine profiles of multiple types.'));
      }

      $this->_gid = $profileIds[0];
    }

    if (!$this->_gid) {
      $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE, 0, 'GET');
    }

    $anyContent = TRUE;
    if ($this->_gid) {
      $page = new CRM_Profile_Page_Dynamic($this->_id, $this->_gid, 'Profile', FALSE, $profileIds);
      $profileGroup = array();
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
        )) {
        $this->assign('listingURL',
          CRM_Utils_System::url("civicrm/profile",
            "force=1&gid={$gidString}"
          )
        );
      }
    }
    else {
      $ufGroups = CRM_Core_BAO_UFGroup::getModuleUFGroup('Profile');

      $profileGroups = array();
      foreach ($ufGroups as $groupid => $group) {
        $page = new CRM_Profile_Page_Dynamic($this->_id, $groupid, 'Profile', FALSE, $profileIds);
        $profileGroup = array();
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
   * build the outcome basing on the CRM_Profile_Page_Dynamic's HTML
   *
   * @return void
   * @access public
   *
   */
  function run() {
    $this->preProcess();
    return parent::run();
  }

  /**
   * @param string $suffix
   *
   * @return null|string
   */
  function checkTemplateFileExists($suffix = '') {
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
   * Use the form name to create the tpl file name
   *
   * @return string
   * @access public
   */
  /**
   * @return string
   */
  function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  /**
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we dont override
   *
   * @return string
   * @access public
   */
  /**
   * @return string
   */
  function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ? $fileName : parent::overrideExtraTemplateFileName();
  }
}

