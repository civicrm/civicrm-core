<?php
namespace Civi\Api4\Action\User;

use Civi\Api4\Generic\Result;
use CRM_Core_Exception;
use Civi\Api4\User;
use Civi\Api4\Generic\AbstractAction;
use Civi\Standalone\Event\LoginEvent;

/**
 * This is designed to be a public API
 *
 * @method static setIdentifier(string $identifier)
 */
class RequestPasswordResetEmail extends AbstractAction {

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
      throw new CRM_Core_Exception("Missing identifier");
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
      $event = new LoginEvent('pre_send_password_reset', $userID ?: NULL);
      \Civi::dispatcher()->dispatch('civi.standalone.login', $event);
    }
    catch (\Exception $e) {
      // If we caught an exception, silently disable sending.
      $userID = 0;
      \Civi::log()->warning("Sending password reset blocked: " . $e->getMessage());
    }

    if ($userID) {
      // we've got through all the guards - now use the
      // internal API action to actually send the email
      User::sendPasswordResetEmail(FALSE)
        ->addWhere('id', '=', $userID)
        ->execute();
    }

    // Ensure we took at least 0.25s. The assumption is that it takes
    // less than 0.25s to generate and send an email, and so this will
    // disguise whether an email has been sent or not. It's won't
    // thwart concerted timing attacks, but in combination with flood
    // control, it might help.
    usleep(1000000 * max(0, $endNoSoonerThan - microtime(TRUE)));
  }

}
