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
    // Add dashboard js and css
    $resources = CRM_Core_Resources::singleton();
    $resources->addScriptFile('civicrm', 'js/jquery/jquery.dashboard.js', 0, 'html-header', FALSE);
    $resources->addStyleFile('civicrm', 'css/dashboard.css');
    $this->assign('contactDashlets', CRM_Core_BAO_Dashboard::getContactDashletsForJS());

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

    return parent::run();
  }

}
