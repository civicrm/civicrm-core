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
    return $this->userHasCompletedSetup() ? "/civicrm/mfa/totp" : '/civicrm/mfa/totp-setup';
  }

  /**
   * Returns whether this MFA is configured for the user.
   */
  public function userHasCompletedSetup(): bool {
    return (bool) CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_totp WHERE user_id = {$this->userID}")->fetch();
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
    // Ensure only one per user is stored.
    TotpEntity::delete(FALSE)
      ->addWhere('user_id', '=', $userID)
      ->execute();
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
    require_once \Civi::paths()->getPath('[civicrm.packages]/PHPGangsta/CiviGoogleAuthenticator.php');
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

  /**
   */
  public function processMFAAttempt(array $pending, $code): bool {

    // Either: we are checking an existing TOTP, OR verifying that
    // the user has successfully imported the new seed to their authenticator
    if (!empty($pending['seed'])) {
      // We are trying to verify a new authenticator.
      if ($this->verifyCode($pending['seed'], $code)) {
        // Good! Store the seed against the user.
        $this->storeSeed($pending['userID'], $pending['seed']);
        return TRUE;
      }
    }
    else {
      // Normal login check.
      return $this->checkMFAData($code);
    }

    return FALSE;
  }

}
