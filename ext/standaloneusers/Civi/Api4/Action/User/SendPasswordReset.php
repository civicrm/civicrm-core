<?php
namespace Civi\Api4\Action\User;

// @todo
// URL is (a) just theh path in the emails.
// clicking button on form with proper token does nothing.
// should redirect to login on success
//

use Civi;
use Civi\Api4\Generic\Result;
use API_Exception;
use Civi\Api4\User;
use Civi\Api4\MessageTemplate;
use Civi\Api4\Generic\AbstractAction;

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
  protected string $identifier;

  public function _run(Result $result) {
    $endNoSoonerThan = 0.25 + microtime(TRUE);

    $identifier = trim($this->identifier);
    if (!$identifier) {
      throw new API_Exception("Missing identifier");
    }

    $user = User::get(FALSE)
      ->addSelect('id', 'uf_name', 'username')
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
      // Allow flood control.
      $ip = \CRM_Utils_System::ipAddress();
      $event = \Civi\Core\Event\GenericHookEvent::create([
        'identifiers' => ["ip:$ip", "user:$userID"],
        'action'      => 'send_password_reset',
      ]);
      \Civi::dispatcher()->dispatch('civi.flood.drip', $event);
    }
    catch (\Exception $e) {
      // If we caught an exception, disable sending.
      $userID = 0;
    }

    if ($userID) {
      $this->sendResetEmail($user);
    }

    // Ensure we took at least 0.25s. The assumption is that it takes
    // less than 0.25s to generate and send an email, and so this will
    // disguise whether an email has been sent or not. It's won't
    // thwart concerted timing attacks, but in combination with flood
    // control, it might help.
    usleep(1000000 * max(0, $endNoSoonerThan - microtime(TRUE)));
  }

  protected function sendResetEmail(array $user) {
    // Find the message template
    $tplID = MessageTemplate::get(FALSE)
      ->setSelect(['id'])
      ->addWhere('workflow_name', '=', 'password_reset')
      ->addWhere('is_default', '=', TRUE)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->first()['id'];
    if (!$tplID) {
      // Some sites may deliberately disable this, but it's unusual, so leave a notice in the log.
      Civi::log()->notice("There is no active, default password_reset message template, which has prevented emailing a reset to {username}", ['username' => $user['username']]);
      return;
    }
    if (!filter_var($user['uf_name'] ?? '', FILTER_VALIDATE_EMAIL)) {
      Civi::log()->warning("User $user[id] has an invalid email. Failed to send password reset.");
      return;
    }

    $token = static::updateToken($user['id']);

    list($domainFromName, $domainFromEmail) = \CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    // xxx this is not generating https://blah - just the path. Why?
    $resetUrlPlaintext = \CRM_Utils_System::url('civicrm/login/password', ['token' => $token], TRUE, NULL, FALSE);
    $resetUrlHtml = htmlspecialchars($resetUrlPlaintext);
    // The template_params are used in the template like {$resetUrlHtml} and {$resetUrlHtml}
    $params = [
      'id' => $tplID,
      'template_params' => compact('resetUrlPlaintext', 'resetUrlHtml'),
      'from' => "\"$domainFromName\" <$domainFromEmail>",
      'to_email' => $user['uf_name'],
      'disable_smarty' => 1,
    ];

    try {
      civicrm_api3('MessageTemplate', 'send', $params);
      Civi::log()->info("Sent password_reset_token MessageTemplate (ID {tplID}) to {to_email} for user {userID}",
        $params + ['userID' => $user['id']]);
    }
    catch (\Exception $e) {
      Civi::log()->error("Failed to send password_reset_token MessageTemplate (ID {tplID}) to {to_email} for user {userID}",
        $params + ['userID' => $user['id'], 'exception' => $e]);
    }
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
