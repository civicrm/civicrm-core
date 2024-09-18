<?php
namespace Civi\Api4\Action\Totp;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Standalone\MFA\Base as MFABase;
use Civi\Api4\Totp;

/**
 * Verify that the user correctly applied the seed to their authenticator app.
 * Store the seed if so.
 *
 * This is a public API that depends on session data.
 *
 */
class ConfirmSeed extends AbstractAction {

  /**
   *
   * @var string
   */
  protected string $seed;

  /**
   * TOTP code; if this matches, we store the seed.
   *
   * @var string
   */
  protected string $code;

  public function _run(Result $result) {

    $pending = MFABase::getPendingLogin();
    if (!$pending || !is_array($pending)
      || (($pending['expiry'] ?? 0) < time())
    ) {
      $result['success'] = FALSE;
      $result['error'] = 'Possibly expired session.';
      // Clear our session data completely.
      \CRM_Core_Session::singleton()->set('pendingLogin', []);
      return;
    }

    $userID = $pending['userID'];

    // Check that the pending UserID does not have TOTP already set up,
    // to prevent them being able to access this URL and set up a new one,
    // thereby bypassing MFA!
    $preExistingTotp = Totp::get(FALSE)
      ->addWhere('user_id', '=', $pending['userID'])
      ->execute()->first();
    if ($preExistingTotp) {
      \Civi::log()->notice("Possibly malicious: Attempt to access Totp.ConfirmSeed API, when TOTP is already set up.", [
        'pendingLogin' => $pending,
      ]);
      $result['error'] = 'TOTP already enabled.';
      return;
    }

    $t = new \Civi\Standalone\MFA\TOTP($userID);
    $result['success'] = $t->verifyCode($this->seed, $this->code);
    if ($result['success']) {
      $t->storeSeed($userID, $this->seed);
    }
    else {
      $result['error'] = 'Code did not match.';
    }
  }

}
