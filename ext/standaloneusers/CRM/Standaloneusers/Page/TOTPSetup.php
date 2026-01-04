<?php

use Civi\Api4\Totp;
use CRM_Standaloneusers_ExtensionUtil as E;
use Civi\Standalone\MFA\Base as MFABase;

/**
 * Page for /civicrm/mfa/totp-setup
 */
class CRM_Standaloneusers_Page_TOTPSetup extends CRM_Core_Page {

  public function run() {

    if (CRM_Core_Session::getLoggedInContactID()) {
      // Already logged in.
      CRM_Utils_System::redirect('/civicrm');
    }

    $pending = MFABase::getPendingLogin();
    if (!$pending || empty($pending['userID'])) {
      // Invalid, send user back to login.
      CRM_Core_Session::singleton()->set('pendingLogin', []);
      CRM_Utils_System::redirect('/civicrm/login');
    }

    // Check that the pending UserID does not have TOTP already set up,
    // to prevent them being able to access this URL and set up a new one,
    // thereby bypassing MFA!
    $preExistingTotp = Totp::get(FALSE)
      ->addWhere('user_id', '=', $pending['userID'])
      ->execute()->first();
    if ($preExistingTotp) {
      \Civi::log()->notice("Possibly malicious: Attempt to access TOTP setup during login, when TOTP is already set up.", [
        'pendingLogin' => $pending,
      ]);
      CRM_Utils_System::redirect('/civicrm/mfa/totp');
    }

    // statusMessages are usually at top of page but in login forms they look much better
    // inside the main box, so we assign them to this var for the tpl to output.
    $this->assign('statusMessages', CRM_Core_Smarty::singleton()->fetch("CRM/common/status.tpl"));

    $totp = new \Civi\Standalone\MFA\TOTP($pending['userID']);

    $seed = $totp->generateNew();
    // Allow 10mins for them to set up their TOPT app.
    $totp->updatePendingLogin(['expiry' => time() + 60 * 10, 'seed' => $seed]);
    $this->assign('totpseed', $seed);

    // Generate QR code
    $domain = CRM_Core_BAO_Domain::getDomain()->name;
    $url = 'otpauth://totp/' . rawurlencode(str_replace(':', '', $domain))
      . ':' . rawurlencode(str_replace(':', '', $pending['username']))
      . '?' . http_build_query([
        'secret' => $seed,
        'digits' => 6,
        'period' => 30,
        'issuer' => $domain,
      ]);
    $barcodeobj = new TCPDF2DBarcode($url, 'QRCODE,H');
    $this->assign('totpqr', $barcodeobj->getBarcodeHTML(4, 4, 'black'));

    $this->assign('pageTitle', '');
    $this->assign('breadcrumb', NULL);
    parent::run();
  }

}
