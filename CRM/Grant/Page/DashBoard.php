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
 * This is page is for Grant Dashboard
 */
class CRM_Grant_Page_DashBoard extends CRM_Core_Page {
 
  private static $_actionLinks;
  private static $_configureActionLinks;
  private static $_onlineGrantLinks;

/**
   * Get the action links for this page.
   *
   * @return array $_actionLinks
   *
   */ function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this Grant application page?');

      self::$_actionLinks = array(
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'title' => ts('Disable'),
          'ref' => 'crm-enable-disable',
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
        ),
      );
    }
    return self::$_actionLinks;
  }
 /**
   * Get the configure action links for this page.
   *
   * @return array $_configureActionLinks
   *
   */
  function &configureActionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_configureActionLinks)) {
      $urlString = 'civicrm/admin/grant/';
      $urlParams = 'reset=1&action=update&id=%%id%%';

      self::$_configureActionLinks = array(
        CRM_Core_Action::ADD => array(
          'name' => ts('Info and Settings'),
          'title' => ts('Info and Settings'),
          'url' => $urlString . 'settings',
          'qs' => $urlParams,
          'uniqueName' => 'settings',
        ),
        CRM_Core_Action::EXPORT => array(
          'name' => ts('Receipt'),
          'title' => ts('Receipt'),
          'url' => $urlString . 'thankyou',
          'qs' => $urlParams,
          'uniqueName' => 'thankyou',
        ),
        CRM_Core_Action::PROFILE => array(
          'name' => ts('Profiles'),
          'title' => ts('Profiles'),
          'url' => $urlString . 'custom',
          'qs' => $urlParams,
          'uniqueName' => 'custom',
        ),
      );
    }

    return self::$_configureActionLinks;
  }

/**
   * Get the online grant links.
   *
   * @return array $_onlineGrantLinks.
   *
   */
  function &onlineGrantLinks() {
    if (!isset(self::$_onlineGrantLinks)) {
      $urlString = 'civicrm/grant/transact';
      $urlParams = 'reset=1&id=%%id%%';
      self::$_onlineGrantLinks = array(
        CRM_Core_Action::RENEW => array(
          'name' => ts('Grant Application (Live)'),
          'title' => ts('Grant Application (Live)'),
          'url' => $urlString,
          'qs' => $urlParams,
          'fe' => TRUE,
          'uniqueName' => 'live_page',
        ),
      );
    }

    return self::$_onlineGrantLinks;
  }
  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    $admin = CRM_Core_Permission::check('administer CiviCRM');

    $grantSummary = CRM_Grant_BAO_Grant::getGrantSummary($admin);

    $this->assign('grantAdmin', $admin);
    $this->assign('grantSummary', $grantSummary);
  }
  
 /**
   * Browse all grant application pages
   *
   * @return void
   * @access public
   * @static
   */
  function browse($action = NULL) {
     $params = array();
     $query = "SELECT * from civicrm_grant_app_page WHERE 1";
     $grantPage = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Grant_DAO_GrantApplicationPage');
     $rows = array();
     $allowToDelete = CRM_Core_Permission::check('delete in CiviGrant');
     //get configure actions links.
     $configureActionLinks = self::configureActionLinks();
     $query = "
       SELECT  id
       FROM  civicrm_grant_app_page
       WHERE  1";
     $grantAppPage = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Grant_DAO_GrantApplicationPage');
     $grantAppPageIds = array();
     while ($grantAppPage->fetch()) {
         $grantAppPageIds[$grantAppPage->id] = $grantAppPage->id;
     }
    //get all section info.
    $grantAppPageSectionInfo = CRM_Grant_BAO_GrantApplicationPage::getSectionInfo($grantAppPageIds);
    
    while ($grantPage->fetch()) {
      $rows[$grantPage->id] = array();
      CRM_Core_DAO::storeValues($grantPage, $rows[$grantPage->id]);

      // form all action links
      $action = array_sum(array_keys($this->actionLinks()));

      //add configure actions links.
      $action += array_sum(array_keys($configureActionLinks));

      //add online grant links.
      $action += array_sum(array_keys(self::onlineGrantLinks()));

      if ($grantPage->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }
         
      //CRM-441
      if (!$allowToDelete) {
        $action -= CRM_Core_Action::DELETE;
      }
      $sectionsInfo = CRM_Utils_Array::value($grantPage->id, $grantAppPageSectionInfo, array());

      $rows[$grantPage->id]['configureActionLinks'] = CRM_Core_Action::formLink(self::formatConfigureLinks($sectionsInfo),
        $action,
        array('id' => $grantPage->id),
        ts('Configure'),
        TRUE,
        'grantapppage.configure.actions',
        'GrantAppPage',
        $grantPage->id
      );
                  
      //build the online grant application links.
      $rows[$grantPage->id]['onlineGrantLinks'] = CRM_Core_Action::formLink(self::onlineGrantLinks(),
        $action,
        array('id' => $grantPage->id),
        ts('Grant Application (Live)'),
        FALSE,
        'grantapppage.online.links',
        'GrantAppPage',
        $grantPage->id
      );
         
      //build the normal action links.
      $rows[$grantPage->id]['action'] = CRM_Core_Action::formLink(self::actionLinks(),
        $action,
        array('id' => $grantPage->id),
        ts('more'),
        TRUE,
        'grantapppage.action.links',
        'GrantAppPage',
        $grantPage->id
      );
         
      $rows[$grantPage->id]['title'] = $grantPage->title;
      $rows[$grantPage->id]['is_active'] = $grantPage->is_active;
      $rows[$grantPage->id]['id'] = $grantPage->id;
    }
     $this->assign('fields', $rows);
  }
  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
      
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );
  
    $this->preProcess();

    $breadCrumb = array(array('title' => ts('Add Grant Application Page'),
      'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(),
      'reset=1'
     ),
    ));
    // what action to take ?
    if ($action & CRM_Core_Action::ADD) {
       $session = CRM_Core_Session::singleton();
       $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/grant/apply/settings',
         'action=add&reset=1'
       ));
   

      $controller = new CRM_Grant_Controller_GrantPage(NULL, $action);
      CRM_Utils_System::setTitle(ts('Manage Grant Application Page'));
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
      return $controller->run();
    }

    if ($action & CRM_Core_Action::DELETE) {
      CRM_Utils_System::appendBreadCrumb($breadCrumb);

      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
        'reset=1&action=browse'
      ));

      $id = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, FALSE, 0
      );
    
      $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_GrantPage_Delete',
        'Delete Grant Application Page',
        CRM_Core_Action::DELETE
      );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }else {
      $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_Search', ts('grants'), NULL);
      $controller->setEmbedded(TRUE);
      $controller->reset();
      $controller->set('limit', 10);
      $controller->set('force', 1);
      $controller->set('context', 'dashboard');
      $controller->process();
      $controller->run();
      $this->browse();
    }
    return parent::run();
  }

  function formatConfigureLinks($sectionsInfo) {
    //build the formatted configure links.
    $formattedConfLinks = self::configureActionLinks();
    foreach ($formattedConfLinks as $act => & $link) {
      $sectionName = CRM_Utils_Array::value('uniqueName', $link);
      if (!$sectionName) {
        continue;
      }

      $classes = array();
      if (isset($link['class'])) {
        $classes = $link['class'];
      }

      if (!CRM_Utils_Array::value($sectionName, $sectionsInfo)) {
        $classes = array();
        if (isset($link['class'])) {
          $classes = $link['class'];
        }
        $link['class'] = array_merge($classes, array('disabled'));
      }
    }

    return $formattedConfLinks;
  }
}

