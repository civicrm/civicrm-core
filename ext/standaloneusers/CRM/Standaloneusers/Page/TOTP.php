<?php

use CRM_Standaloneusers_ExtensionUtil as E;
use Civi\Standalone\MFA\Base as MFABase;
use Civi\Standalone\MFA\TOTP;

/**
 * Page for /civicrm/mfa/totp
 */
class CRM_Standaloneusers_Page_TOTP extends CRM_Core_Page {

  public function run() {
    // Nb. Get SQL from schema like so:
    // echo E::schema('totp')->generateInstallSql(); exit;

    if (CRM_Core_Session::getLoggedInContactID()) {
      // Already logged in.
      CRM_Utils_System::redirect('/civicrm');
    }

    $pending = MFABase::getPendingLogin();
    if (!$pending || !is_array($pending)
      || (($pending['expiry'] ?? 0) < time())
    ) {
      // Invalid, send user back to login.
      CRM_Core_Session::singleton()->set('pendingLogin', []);
      CRM_Utils_System::redirect('/civicrm/login');
    }

    // In order to ask for TOTP, we need to have it set up.
    $mfa = new TOTP($pending['userID']);
    if (!$mfa->userHasCompletedSetup()) {
      CRM_Utils_System::redirect('/civicrm/mfa/totp-setup');
    }

    $this->assign('pageTitle', '');
    $this->assign('logoUrl', E::url('images/civicrm-logo.png'));
    $this->assign('breadcrumb', NULL);
    parent::run();
  }

}
