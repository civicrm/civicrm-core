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
 * This is a part of CiviCRM extension management functionality.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This page displays the list of extensions registered in the system.
 */
class CRM_Admin_Page_Extensions extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Obtains the group name from url and sets the title.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviCRM Extensions'));
        $destination = CRM_Utils_System::url( 'civicrm/admin/extensions',
                                              'reset=1' );

        $destination = urlencode( $destination );
        $this->assign( 'destination', $destination );
  }

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_Extension';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::ADD => array(
          'name' => ts('Install'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=add&id=%%id%%&key=%%key%%',
          'title' => ts('Install'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=enable&id=%%id%%&key=%%key%%',
          'ref' => 'enable-action',
          'title' => ts('Enable'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=disable&id=%%id%%&key=%%key%%',
          'title' => ts('Disable'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Uninstall'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=delete&id=%%id%%&key=%%key%%',
          'title' => ts('Uninstall Extension'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Download'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=update&id=%%id%%&key=%%key%%',
          'title' => ts('Download Extension'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Run the basic page (run essentially starts execution for that page).
   *
   * @return void
   */
  function run() {
    $this->preProcess();
    return parent::run();
  }

  /**
   * Browse all options
   *
   *
   * @return void
   * @access public
   * @static
   */
  function browse() {
    $mapper = CRM_Extension_System::singleton()->getMapper();
    $manager = CRM_Extension_System::singleton()->getManager();

    // build announcements at the top of the page
    $this->assign('extAddNewEnabled', CRM_Extension_System::singleton()->getBrowser()->isEnabled());
    $reqs = CRM_Extension_System::singleton()->getDownloader()->checkRequirements();
    if (empty($reqs)) {
      $reqs = CRM_Extension_System::singleton()->getBrowser()->checkRequirements();
    }
    if (empty($reqs)) {
      $reqs = CRM_Extension_System::singleton()->getDefaultContainer()->checkRequirements();
    }
    $this->assign('extAddNewReqs', $reqs);

    $this->assign('extDbUpgrades', CRM_Extension_Upgrades::hasPending());
    $this->assign('extDbUpgradeUrl', CRM_Utils_System::url('civicrm/admin/extensions/upgrade', 'reset=1'));

    // TODO: Debate whether to immediately detect changes in underlying source tree
    // $manager->refresh();

    // build list of local extensions
    $localExtensionRows = array(); // array($pseudo_id => extended_CRM_Extension_Info)
    $keys = array_keys($manager->getStatuses());
    sort($keys);
    foreach($keys as $key) {
      try {
        $obj = $mapper->keyToInfo($key);
      } catch (CRM_Extension_Exception $ex) {
        CRM_Core_Session::setStatus(ts('Failed to read extension (%1). Please refresh the extension list.', array(1 => $key)));
        continue;
      }

      $row = self::createExtendedInfo($obj);
      $row['id'] = $obj->key;

      // assign actions
      $action = 0;
      switch ($row['status']) {
        case CRM_Extension_Manager::STATUS_UNINSTALLED:
          $action += CRM_Core_Action::ADD;
          break;
        case CRM_Extension_Manager::STATUS_DISABLED:
          $action += CRM_Core_Action::ENABLE;
          $action += CRM_Core_Action::DELETE;
          break;
        case CRM_Extension_Manager::STATUS_DISABLED_MISSING:
          $action += CRM_Core_Action::DELETE;
          break;
        case CRM_Extension_Manager::STATUS_INSTALLED:
        case CRM_Extension_Manager::STATUS_INSTALLED_MISSING:
          $action += CRM_Core_Action::DISABLE;
          break;
        default:
      }
      // TODO if extbrowser is enabled and extbrowser has newer version than extcontainer,
      // then $action += CRM_Core_Action::UPDATE
      $row['action'] = CRM_Core_Action::formLink(self::links(),
        $action,
        array(
          'id' => $row['id'],
          'key' => $obj->key,
        ),
        ts('more'),
        FALSE,
        'extension.local.action',
        'Extension',
        $row['id']
      );
      // Key would be better to send, but it's not an integer.  Moreover, sending the
      // values to hook_civicrm_links means that you can still get at the key

      $localExtensionRows[$row['id']] = $row;
    }
    $this->assign('localExtensionRows', $localExtensionRows);

    // build list of availabe downloads
    $remoteExtensionRows = array();
    foreach (CRM_Extension_System::singleton()->getBrowser()->getExtensions() as $info) {
      $row = (array) $info;
      $row['id'] = $info->key;
      $action = CRM_Core_Action::UPDATE;
      $row['action'] = CRM_Core_Action::formLink(self::links(),
        $action,
        array(
          'id' => $row['id'],
          'key' => $row['key'],
        ),
        ts('more'),
        FALSE,
        'extension.remote.action',
        'Extension',
        $row['id']
      );
      if (isset($localExtensionRows[$info->key])) {
        if (version_compare($localExtensionRows[$info->key]['version'], $info->version, '<')) {
          $row['is_upgradeable'] = TRUE;
        }
      }
      $remoteExtensionRows[$row['id']] = $row;
    }
    $this->assign('remoteExtensionRows', $remoteExtensionRows);
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_Extensions';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'CRM_Admin_Form_Extensions';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/extensions';
  }

  /**
   * function to get userContext params
   *
   * @param int $mode mode that we are in
   *
   * @return string
   * @access public
   */
  function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }

  /**
   * Take an extension's raw XML info and add information about the
   * extension's status on the local system.
   *
   * The result format resembles the old CRM_Core_Extensions_Extension.
   *
   * @param CRM_Extension_Info $obj
   *
   * @return array
   */
  public static function createExtendedInfo(CRM_Extension_Info $obj) {
    $mapper = CRM_Extension_System::singleton()->getMapper();
    $manager = CRM_Extension_System::singleton()->getManager();

    $extensionRow = (array) $obj;
    try {
      $extensionRow['path'] = $mapper->keyToBasePath($obj->key);
    } catch (CRM_Extension_Exception $e) {
      $extensionRow['path'] = '';
    }
    $extensionRow['status'] = $manager->getStatus($obj->key);

    switch ($extensionRow['status']) {
      case CRM_Extension_Manager::STATUS_UNINSTALLED:
        $extensionRow['statusLabel'] = ''; // ts('Uninstalled');
        break;
      case CRM_Extension_Manager::STATUS_DISABLED:
        $extensionRow['statusLabel'] = ts('Disabled');
        break;
      case CRM_Extension_Manager::STATUS_INSTALLED:
        $extensionRow['statusLabel'] = ts('Enabled'); // ts('Installed');
        break;
      case CRM_Extension_Manager::STATUS_DISABLED_MISSING:
        $extensionRow['statusLabel'] = ts('Disabled (Missing)');
        break;
      case CRM_Extension_Manager::STATUS_INSTALLED_MISSING:
        $extensionRow['statusLabel'] = ts('Enabled (Missing)'); // ts('Installed');
        break;
      default:
        $extensionRow['statusLabel'] = '(' . $extensionRow['status'] . ')';
    }
    return $extensionRow;
  }
}

