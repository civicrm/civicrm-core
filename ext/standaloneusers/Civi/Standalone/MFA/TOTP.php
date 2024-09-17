<?php
namespace Civi\Standalone\MFA;

use CRM_Core_DAO;

/**
 * Time based One-Time Password.
 *
 */
class TOTP extends Base implements MFAInterface {

  public function getFormUrl(): string {
    // Is TOTP set up for this user?
    $totp = CRM_Core_DAO::executeQuery("SELECT * FROM {$this->userID}")->fetch();
    return $totp ? "/civicrm/mfa/totp" : '/civicrm/mfa/totp-setup';
  }

}
