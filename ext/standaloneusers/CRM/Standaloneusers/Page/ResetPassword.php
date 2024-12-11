<?php
use CRM_Standaloneusers_ExtensionUtil as E;

use Civi\Api4\Action\User\PasswordReset;

/**
 * Provide the send password reset / reset password page.
 * URL: /civicrm/login/password[?token=xxxx]
 *
 * If called with ?token=xxxx then it's the latter.
 */
class CRM_Standaloneusers_Page_ResetPassword extends CRM_Core_Page {

  public function run() {

    $this->assign('logoUrl', E::url('images/civicrm-logo.png'));
    $this->assign('hibp', CIVICRM_HIBP_URL);
    $this->assign('pageTitle', '');
    $this->assign('breadcrumb', NULL);
    // $this->assign('loggedInUserID', CRM_Utils_System::getLoggedInUfID());
    Civi::service('angularjs.loader')->addModules('crmResetPassword');

    // If we have a password reset token, validate it without 'spending' it.
    $token = CRM_Utils_Request::retrieveValue('token', 'String', NULL, FALSE, $method = 'GET');
    if ($token) {
      if (!PasswordReset::checkPasswordResetToken($token, FALSE)) {
        $token = 'invalid';
      }
      $this->assign('token', $token);
    }

    parent::run();
  }

}
