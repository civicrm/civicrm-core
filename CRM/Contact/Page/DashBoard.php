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
 * CiviCRM Dashboard.
 */
class CRM_Contact_Page_DashBoard extends CRM_Core_Page {

  /**
   * Run dashboard.
   */
  public function run() {
    CRM_Utils_System::setTitle(ts('CiviCRM Home'));
    $contactID = CRM_Core_Session::getLoggedInContactID();

    // call hook to get html from other modules
    // ignored but needed to prevent warnings
    $contentPlacement = CRM_Utils_Hook::DASHBOARD_BELOW;
    $html = CRM_Utils_Hook::dashboard($contactID, $contentPlacement);
    if (is_array($html)) {
      $this->assign_by_ref('hookContent', $html);
      $this->assign('hookContentPlacement', $contentPlacement);
    }

    $communityMessages = CRM_Core_CommunityMessages::create();
    if ($communityMessages->isEnabled()) {
      $message = $communityMessages->pick();
      if ($message) {
        $this->assign('communityMessages', $communityMessages->evalMarkup($message['markup']));
      }
    }

    $loader = new Civi\Angular\AngularLoader();
    $loader->setPageName('civicrm/dashboard');

    // For each dashlet that requires an angular directive, load the angular module which provides that directive
    $modules = [];
    foreach (CRM_Core_BAO_Dashboard::getContactDashlets() as $dashlet) {
      if (!empty($dashlet['directive'])) {
        foreach ($loader->getAngular()->getModules() as $name => $module) {
          if (!empty($module['exports'][$dashlet['directive']])) {
            $modules[] = $name;
            continue;
          }
        }
      }
    }
    $loader->setModules($modules);

    $loader->load();

    return parent::run();
  }

  /**
   * partialsCallback from crmDashboard.ang.php
   *
   * Generates an html template for each angular-based dashlet.
   *
   * @param $moduleName
   * @param $module
   * @return array
   */
  public static function angularPartials($moduleName, $module) {
    $partials = [];
    foreach (CRM_Core_BAO_Dashboard::getContactDashlets() as $dashlet) {
      if (!empty($dashlet['directive'])) {
        $partials["~/$moduleName/directives/{$dashlet['directive']}.html"] = "<{$dashlet['directive']}></{$dashlet['directive']}>";
      }
    }
    return $partials;
  }

  /**
   * settingsFactory from crmDashboard.ang.php
   *
   * @return array
   */
  public static function angularSettings() {
    return [
      'dashlets' => CRM_Core_BAO_Dashboard::getContactDashlets(),
    ];
  }

}
