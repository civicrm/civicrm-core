<?php
namespace Civi\Api4\Action\User;

// @todo
// URL is (a) just theh path in the emails.
// clicking button on form with proper token does nothing.
// should redirect to login on success

use Civi;
use Civi\Api4\Generic\Result;
use API_Exception;
use Civi\Api4\User;
use Civi\Standalone\Security;
use Civi\Api4\Generic\AbstractAction;

/**
 * @class API_Exception
 */

/**
 * This is designed to be a public API
 *
 * @method static setIdentifier(string $identifier)
 */
class SendPasswordReset extends AbstractAction {

  /**
   * Username or email of user to send email for.
   *
   * @var string
   * @default ''
   */
  protected $identifier;

  public function _run(Result $result) {
    $endNoSoonerThan = 0.25 + microtime(TRUE);

    $identifier = trim($this->identifier);
    if (!$identifier) {
      throw new API_Exception("Missing identifier");
    }

    $user = User::get(FALSE)
      ->addSelect('id', 'uf_name', 'username', 'contact_id')
      ->addWhere('is_active', '=', TRUE)
      ->setLimit(1)
      ->addWhere(
        filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'uf_name' : 'username',
        '=',
        $identifier)
      ->execute()
      ->first();
    $userID = $user['id'] ?? 0;

    try {
      // Allow flood control by extensions. (e.g. Moat).
      $event = \Civi\Core\Event\GenericHookEvent::create([
        'action'      => 'send_password_reset',
        'identifiers' => ["user:$userID"],
      ]);
      \Civi::dispatcher()->dispatch('civi.flood.drip', $event);
    }
    catch (\Exception $e) {
      // If we caught an exception, disable sending.
      $userID = 0;
    }

    if ($userID) {
      // (Re)generate token and store on User.
      $token = static::updateToken($userID);

      $workflowMessage = Security::singleton()->preparePasswordResetWorkflow($user, $token);
      if ($workflowMessage) {
        // The template_params are used in the template like {$resetUrlHtml} and {$resetUrlHtml} {$usernamePlaintext} {$usernameHtml}
        try {
          [$sent, /*$subject, $text, $html*/] = $workflowMessage->sendTemplate();
          if (!$sent) {
            throw new \RuntimeException("sendTemplate() returned unsent.");
          }
          Civi::log()->info("Successfully sent password reset to user {userID} ({username}) to {email}", $workflowMessage->getParamsForLog());
        }
        catch (\Exception $e) {
          Civi::log()->error("Failed to send password reset to user {userID} ({username}) to {email}", $workflowMessage->getParamsForLog() + ['exception' => $e]);
        }
      }
    }

    // Ensure we took at least 0.25s. The assumption is that it takes
    // less than 0.25s to generate and send an email, and so this will
    // disguise whether an email has been sent or not. It's won't
    // thwart concerted timing attacks, but in combination with flood
    // control, it might help.
    usleep(1000000 * max(0, $endNoSoonerThan - microtime(TRUE)));
  }

  /**
   * Generate and store a token on the User record.
   *
   * @param int $userID
   *
   * @return string
   *   The token
   */
  public static function updateToken(int $userID): string {
    // Generate a once-use token that expires in 1 hour.
    // We'll store this on the User record, that way invalidating any previous token that may have been generated.
    // The format is <expiry><random><userID>
    // The UserID shouldn't need to be secret.
    // We only store <expiry><random> on the User record.
    $expires = time() + 60 * 60;
    $token = dechex($expires) . substr(preg_replace('@[/+=]+@', '', base64_encode(random_bytes(64))), 0, 32);

    User::update(FALSE)
      ->addValue('password_reset_token', $token)
      ->addWhere('id', '=', $userID)
      ->execute();

    return $token . dechex($userID);
  }

}
