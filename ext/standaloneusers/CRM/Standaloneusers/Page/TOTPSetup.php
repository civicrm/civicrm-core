<?php
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
    if (!$pending) {
      // Invalid, send user back to login.
      CRM_Core_Session::singleton()->set('pendingLogin', []);
      CRM_Utils_System::redirect('/civicrm/login');
    }

    $totp = new \Civi\Standalone\MFA\TOTP($pending['userID']);

    $seed = $totp->generateNew();
    // Allow 10mins for them to set up their TOPT app.
    $pending['expiry'] = time() + 60 * 10;
    $pending['seed'] = $seed;
    CRM_Core_Session::singleton()->set('pendingLogin', $pending);
    $this->assign('totpseed', $seed);

    // Generate QR code

    // set the barcode content and type
    $domain = CRM_Core_BAO_Domain::getDomain()->name;
    $url = 'otpauth://totp/' . rawurlencode($domain) . ':none?'
      . http_build_query([
        'secret' => $pending['seed'],
        'digits' => 6,
        'period' => 30,
        'issuer' => $domain,
      ]);
    $barcodeobj = new TCPDF2DBarcode($url, 'QRCODE,H');
    $this->assign('totpqr', $barcodeobj->getBarcodeHTML(6, 6, 'black'));

    $this->assign('logoUrl', E::url('images/civicrm-logo.png'));
    $this->assign('pageTitle', '');
    $this->assign('breadcrumb', NULL);
    parent::run();
  }

}
