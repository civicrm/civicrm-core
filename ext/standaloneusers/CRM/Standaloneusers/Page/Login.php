<?php
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_Page_Login extends CRM_Core_Page {

  public function run() {
    if (CRM_Core_Config::singleton()->userSystem->isUserLoggedIn()) {
      // Already logged in.
      CRM_Utils_System::redirect('/civicrm');
    }

    $this->assign('logoUrl', E::url('images/civicrm-logo.png'));
    $this->assign('pageTitle', '');
    $this->assign('forgottenPasswordURL', CRM_Utils_System::url('civicrm/login/password'));
    // Remove breadcrumb for login page.
    $this->assign('breadcrumb', NULL);
    $this->assign('justLoggedOut', isset($_GET['justLoggedOut']));
    $this->assign('sessionLost', isset($_GET['sessionLost']));

    parent::run();
  }

  /**
   * Log out.
   */
  public static function logout() {
    CRM_Core_Config::singleton()->userSystem->logout();
    // Dump them back on the log-IN page.
    CRM_Utils_System::redirect('/civicrm/login?justLoggedOut');
  }

}
