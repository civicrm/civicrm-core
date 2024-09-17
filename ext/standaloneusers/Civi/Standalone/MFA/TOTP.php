<?php
namespace Civi\Standalone\MFA;

/**
 * Time based One-Time Password.
 *
 */
class TOTP extends Base implements MFAInterface {

  public function getFormUrl(): string {

    // Is TOTP set up for this user?

    return "/civicrm/auth-totp";
  }

}
