<?php
namespace Civi\Standalone\MFA;

use Civi\Api4\Totp as TotpEntity;
use CRM_Core_DAO;

/**
 * Time based One-Time Password.
 *
 */
class TOTP extends Base implements MFAInterface {

  public function getFormUrl(): string {
    // Is TOTP set up for this user?
    $totp = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_totp WHERE {$this->userID}")->fetch();
    return $totp ? "/civicrm/mfa/totp" : '/civicrm/mfa/totp-setup';
  }

  /**
   * Generate a new seed.
   *
   * This will be presented to the user so they can try it in their authenticator app.
   * If they are successfully able to enter a correct TOTP code from the app, then
   * we will store this against their record.
   *
   */
  public function generateNew(): string {
    $ga = $this->getAuthenticator();
    $secret = $ga->createSecret();
    return $secret;
    // echo "Secret is: " . $secret . "\n\n";

    // $qrCodeUrl = $ga->getQRCodeGoogleUrl('Blog', $secret);
    // echo "Google Charts URL for the QR-Code: " . $qrCodeUrl . "\n\n";

    // $oneCode = $ga->getCode($secret);
    // echo "Checking Code '$oneCode' and Secret '$secret':\n";
    //
    // // 2 = 2*30sec clock tolerance
    // $checkResult = $ga->verifyCode($secret, $oneCode, 2);
    // if ($checkResult) {
    //   echo 'OK';
    // }
    // else {
    //   echo 'FAILED';
    // }
    //
  }

  /**
   * Store encrypted seed against the User ID.
   */
  public function storeSeed(int $userID, string $seed) {
    $encrypted = \Civi::service('crypto.token')->encrypt($seed, 'CRED');
    TotpEntity::create(FALSE)
      ->addValue('user_id', $userID)
      ->addValue('seed', $encrypted)
      ->execute();
  }

  /**
   * Does a given code currently match the given seed?
   */
  public function verifyCode(string $seed, string $code): bool {
    // 2 = 2*30sec clock tolerance
    $ga = $this->getAuthenticator();
    return (bool) ($ga->verifyCode($seed, $code, 2));
  }

  /**
   * Generate the currently valid code.
   */
  public function getCode(string $seed): string {
    $ga = $this->getAuthenticator();
    return (string) ($ga->getCode($seed));
  }

  public function getAuthenticator(): \CiviGoogleAuthenticator {
    require_once \Civi::paths()->getPath('[civicrm.root]/packages/PHPGangsta/CiviGoogleAuthenticator.php');
    return new \CiviGoogleAuthenticator();
  }

  public function checkMFAData($data):bool {
    // Load the seed from the user
    $seed = TotpEntity::get(FALSE)
      ->addWhere('user_id', '=', $this->userID)
      ->execute()->first()['seed'] ?? '';
    $seed = \Civi::service('crypto.token')->decrypt($seed, ['plain', 'CRED']);
    return $this->verifyCode($seed, $data);
  }

}
