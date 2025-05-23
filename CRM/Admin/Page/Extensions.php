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
 * This is a part of CiviCRM extension management functionality.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This page displays the list of extensions registered in the system.
 */
class CRM_Admin_Page_Extensions extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * Obtains the group name from url and sets the title.
   */
  public function preProcess() {
    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');

    CRM_Utils_System::setTitle(ts('CiviCRM Extensions'));
    $destination = CRM_Utils_System::url('civicrm/admin/extensions',
      'reset=1');

    $destination = urlencode($destination);
    $this->assign('destination', $destination);
  }

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_Extension';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::ADD => [
          'name' => ts('Install'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=add&id=%%id%%&key=%%key%%',
          'title' => ts('Install'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ADD),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=enable&id=%%id%%&key=%%key%%',
          'ref' => 'enable-action',
          'title' => ts('Enable'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=disable&id=%%id%%&key=%%key%%',
          'title' => ts('Disable'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Uninstall'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=delete&id=%%id%%&key=%%key%%',
          'title' => ts('Uninstall Extension'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Install'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=update&id=%%id%%&key=%%key%%',
          'title' => ts('Install Extension'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Run the basic page (run essentially starts execution for that page).
   */
  public function run() {
    $this->preProcess();
    return parent::run();
  }

  /**
   * Browse all options.
   */
  public function browse() {

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

    $localExtensionRows = $this->formatLocalExtensionRows();
    $this->assign('localExtensionRows', $localExtensionRows);

    $remoteExtensionRows = $this->formatRemoteExtensionRows($localExtensionRows);
    $this->assign('remoteExtensionRows', $remoteExtensionRows);

    Civi::resources()
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting([
        'tabSettings' => ['active' => $_GET['selectedChild'] ?? NULL],
      ]);
  }

  /**
   * Get the list of local extensions and format them as a table with
   * status and action data.
   *
   * @return array
   */
  public function formatLocalExtensionRows() {
    $mapper = CRM_Extension_System::singleton()->getMapper();
    $manager = CRM_Extension_System::singleton()->getManager();

    // array($pseudo_id => extended_CRM_Extension_Info)
    $localExtensionRows = [];
    $keys = array_keys($manager->getStatuses());
    sort($keys);
    $hiddenExtensions = $mapper->getKeysByTag('mgmt:hidden');
    $requiredExtensions = $mapper->getKeysByTag('mgmt:required');
    foreach ($keys as $key) {
      if (in_array($key, $hiddenExtensions)) {
        continue;
      }
      try {
        $obj = $mapper->keyToInfo($key);
      }
      catch (CRM_Extension_Exception $ex) {
        CRM_Core_Session::setStatus(ts('Failed to read extension (%1). Please refresh the extension list.', [1 => $key]));
        continue;
      }

      $mapper = CRM_Extension_System::singleton()->getMapper();

      $row = self::createExtendedInfo($obj);
      $row['id'] = $obj->key;
      $row['action'] = '';

      // assign actions
      $action = 0;
      switch ($row['status']) {
        case CRM_Extension_Manager::STATUS_UNINSTALLED:
          if (!$manager->isIncompatible($row['id'])) {
            $action += CRM_Core_Action::ADD;
          }
          break;

        case CRM_Extension_Manager::STATUS_DISABLED:
          if (!$manager->isIncompatible($row['id'])) {
            $action += CRM_Core_Action::ENABLE;
          }
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
      if ($action && !in_array($key, $requiredExtensions)) {
        $row['action'] = CRM_Core_Action::formLink(self::links(),
          $action,
          ['id' => $row['id'], 'key' => $obj->key],
          ts('more'),
          FALSE,
          'extension.local.action',
          'Extension',
          $row['id']
        );
      }
      // Key would be better to send, but it's not an integer.  Moreover, sending the
      // values to hook_civicrm_links means that you can still get at the key

      $localExtensionRows[$row['id']] = $row;
    }
    return $localExtensionRows;
  }

  /**
   * Get the list of remote extensions and format them as a table with
   * status and action data.
   *
   * @param array $localExtensionRows
   * @return array
   */
  public function formatRemoteExtensionRows($localExtensionRows) {
    try {
      $remoteExtensions = CRM_Extension_System::singleton()->getBrowser()->getExtensions();
    }
    catch (CRM_Extension_Exception $e) {
      $remoteExtensions = [];
      CRM_Core_Session::setStatus($e->getMessage(), ts('Extension download error'), 'warning');
    }

    // build list of available downloads
    $remoteExtensionRows = [];
    $compat = CRM_Extension_System::getCompatibilityInfo();
    $mapper = CRM_Extension_System::singleton()->getMapper();

    foreach ($remoteExtensions as $info) {
      if (!empty($compat[$info->key]['obsolete'])) {
        continue;
      }
      $row = self::fillMissingInfoKeys((array) $info);
      $row['id'] = $info->key;
      $row['upgradelink'] = '';
      $action = CRM_Core_Action::UPDATE;
      $row['action'] = CRM_Core_Action::formLink(self::links(),
        $action,
        [
          'id' => $row['id'],
          'key' => $row['key'],
        ],
        ts('more'),
        FALSE,
        'extension.remote.action',
        'Extension',
        $row['id']
      );
      if (isset($localExtensionRows[$info->key])) {
        if (array_key_exists('version', $localExtensionRows[$info->key])) {
          if (version_compare($localExtensionRows[$info->key]['version'] ?? '', $info->version, '<')) {
            $row['upgradelink'] = $mapper->getUpgradeLink($remoteExtensions[$info->key], $localExtensionRows[$info->key]);
          }
        }
      }
      $remoteExtensionRows[$row['id']] = $row;
    }

    return $remoteExtensionRows;
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_Extensions';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'CRM_Admin_Form_Extensions';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/extensions';
  }

  /**
   * Get userContext params.
   *
   * @param int $mode
   *   Mode that we are in.
   *
   * @return string
   */
  public function userContextParams($mode = NULL) {
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
    return self::fillMissingInfoKeys(CRM_Extension_System::createExtendedInfo($obj));
  }

  /**
   * Extension templates expect certain keys to always be set, but these might be missing from the relevant info.xml files
   * This ensures the expect keys are always set.
   *
   * @param array $info
   * @return array
   */
  private static function fillMissingInfoKeys(array $info) {
    $defaultKeys = [
      'urls' => [],
      'authors' => [],
      'version' => '',
      'description' => '',
      'license' => '',
      'path' => '',
      'releaseDate' => '',
      'downloadUrl' => FALSE,
      'compatibility' => FALSE,
      'develStage' => FALSE,
      'ready' => '',
      'usage' => '',
      'comments' => FALSE,
    ];
    $support = [
      'alpha' => ts('Alpha'),
      'beta' => ts('Beta'),
      'stable' => ts('Stable'),
      'reviewed' => ts('Reviewed'),
    ];
    $info = array_merge($defaultKeys, $info);
    $info['is_stable'] = $info['develStage'] === 'stable' && !preg_match(";(alpha|beta|dev);", $info['version']);
    $info['develStage_formatted'] = $support[$info['develStage']] ?? $info['develStage'];
    if ($info['ready'] == 'ready') {
      $info['develStage_formatted'] = $support['reviewed'];
    }
    foreach ($info['authors'] as &$author) {
      $author = array_merge(['homepage' => ''], $author);
    }
    return $info;
  }

}
