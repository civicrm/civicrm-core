<?php
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_Page_TOTP extends CRM_Core_Page {

  public function run() {

    if (CRM_Core_Session::getLoggedInContactID()) {
      // Already logged in.
      CRM_Utils_System::redirect('/civicrm');
    }

    $pending = CRM_Core_Session::singleton()->get('pendingLogin');
    if (!$pending || !is_array($pending)
      || (($pending['expiry'] ?? 0) < time())
    ) {
      // Invalid, send user back to login.
      $pending = CRM_Core_Session::singleton()->set('pendingLogin', []);
      CRM_Utils_System::redirect('/login');
    }

    $this->assign('logoUrl', E::url('images/civicrm-logo.png'));
    $this->assign('pageTitle', '');
    $this->assign('forgottenPasswordURL', CRM_Utils_System::url('civicrm/login/password'));
    // Remove breadcrumb for login page.
    $this->assign('breadcrumb', NULL);

    $this->assign('justLoggedOut', isset($_GET['justLoggedOut']));

    parent::run();
  }

  /**
   * Log out.
   */
  public static function logout() {
    Security::singleton()->logoutUser();
    // Dump them back on the log-IN page.
    CRM_Utils_System::redirect('/civicrm/login?justLoggedOut');
  }

}
