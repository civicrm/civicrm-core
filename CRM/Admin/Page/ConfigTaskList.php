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
 * Page for displaying list of site configuration tasks with links to each setting form.
 */
class CRM_Admin_Page_ConfigTaskList extends CRM_Core_Page {

  /**
   * Run page.
   *
   * @return string
   */
  public function run() {
    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');

    CRM_Utils_System::setTitle(ts("Configuration Checklist"));
    $this->assign('recentlyViewed', FALSE);

    $destination = CRM_Utils_System::url('civicrm/admin/configtask',
      'reset=1',
      FALSE, NULL, FALSE
    );

    $destination = urlencode($destination);
    $this->assign('destination', $destination);

    $this->assign('registerSite', htmlspecialchars('https://civicrm.org/register-your-site?src=iam&sid=' . CRM_Utils_System::getSiteID()));

    //Provide ability to optionally display some component checklist items when components are on
    $result = civicrm_api3('Setting', 'get', [
      'sequential' => 1,
      'return' => ["enable_components"],
    ]);
    $enabled = [];
    foreach ($result['values'][0]['enable_components'] as $component) {
      $enabled[$component] = 1;
    }

    // Create an array of translated Component titles to use as part of links on the page.
    $translatedComponents = CRM_Core_Component::getNames(TRUE);
    $translatedTitles = [];
    foreach (CRM_Core_Component::getNames() as $key => $component) {
      $translatedTitles[$component] = $translatedComponents[$key];
    }
    $this->assign('componentTitles', $translatedTitles);
    $this->assign('enabledComponents', $enabled);

    return parent::run();
  }

}
