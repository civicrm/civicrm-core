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
      $this->assign('hookContent', $html);
      $this->assign('hookContentPlacement', $contentPlacement);
    }

    $this->assign('communityMessages', $this->getCommunityMessageOutput());

    $loader = Civi::service('angularjs.loader');
    $loader->addModules('crmDashboard');
    $loader->setPageName('civicrm/dashboard');

    // For each dashlet that requires an angular directive, load the angular module which provides that directive
    foreach (CRM_Core_BAO_Dashboard::getContactDashlets() as $dashlet) {
      if (!empty($dashlet['directive'])) {
        foreach ($loader->getAngular()->getModules() as $name => $module) {
          if (!empty($module['exports'][$dashlet['directive']])) {
            $loader->addModules($name);
            continue;
          }
        }
      }
    }

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
        // FIXME: Wrapping each directive in <div id='bootstrap-theme'> produces invalid html (duplicate ids in the dom)
        // but it's the only practical way to selectively apply boostrap3 theming to specific dashlets
        $partials["~/$moduleName/directives/{$dashlet['directive']}.html"] = "<div id='bootstrap-theme'><{$dashlet['directive']}></{$dashlet['directive']}></div>";
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

  /**
   * Get community message output.
   *
   * @return string
   */
  protected function getCommunityMessageOutput(): string {
    $communityMessages = CRM_Core_CommunityMessages::create();
    if ($communityMessages->isEnabled()) {
      $message = $communityMessages->pick();
      if ($message) {
        return $communityMessages->evalMarkup($message['markup']);
      }
    }
    return '';
  }

}
