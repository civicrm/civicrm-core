<?php
namespace Civi\Api4\Action\Totp;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

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

    $pending = \CRM_Core_Session::singleton()->get('pendingLogin');
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
