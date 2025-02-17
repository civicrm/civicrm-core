<?php
namespace Civi\Api4\Action\User;

use Civi;
use Civi\Api4\Generic\BasicBatchAction;
use Civi\Api4\MessageTemplate;
use CRM_Standaloneusers_WorkflowMessage_PasswordReset;

/**
 * This is designed to be an internal API
 *
 * @method static setIdentifier(string $identifier)
 * @method static setTimeout(int $minutes)
 */
class SendPasswordResetEmail extends BasicBatchAction {

  /**
   * Timeout for the reset token in minutes
   *
   * @var int
   */
  protected $timeout = 60;

  /**
   * @inheritdoc
   *
   * Data we need from the User record
   */
  protected function getSelect() {
    return ['id', 'username', 'uf_name', 'contact_id'];
  }

  /**
   * @inheritdoc
   *
   * @param array $user user record with fields from getSelect
   */
  public function doTask($user) {
    // TODO: if you disable the reset email template, the action will still reset the token on the user record, which is a bit weird
    // (Re)generate token and store on User.
    $token = PasswordReset::updateToken($user['id'], $this->timeout);

    $workflowMessage = self::preparePasswordResetWorkflow($user, $token, $this->timeout);
    if ($workflowMessage) {
      // The template_params are used in the template like {$resetUrlHtml} and {$resetUrlHtml} {$usernamePlaintext} {$usernameHtml}
      try {
        [$sent, /*$subject, $text, $html*/] = $workflowMessage->sendTemplate();
        if (!$sent) {
          throw new \RuntimeException("sendTemplate() returned unsent.");
        }
        Civi::log()->info("Successfully sent password reset to user {$user['id']} ({$user['username']}) to {$user['uf_name']}");
      }
      catch (\Exception $e) {
        Civi::log()->error("Failed to send password reset to user {$user['id']} ({$user['username']}) to {$user['uf_name']}");
        return [
          'is_error' => TRUE,
        ];
      }
      return [
        'is_error' => FALSE,
      ];
    }
    return [
      'is_error' => TRUE,
      'message' => 'No password reset message template available',
    ];
  }

  /**
   * Prepare a password reset workflow email for a user
   *
   * Includes some checks that we have all the necessary pieces
   * in place
   *
   * @internal (only public for use in SecurityTest)
   *
   * @return \CRM_Standaloneusers_WorkflowMessage_PasswordReset|null
   */
  public static function preparePasswordResetWorkflow(array $user, string $token, int $tokenTimeout): ?CRM_Standaloneusers_WorkflowMessage_PasswordReset {
    // Find the message template
    $tplID = MessageTemplate::get(FALSE)
      ->setSelect(['id'])
      ->addWhere('workflow_name', '=', 'password_reset')
      ->addWhere('is_default', '=', TRUE)
      ->addWhere('is_reserved', '=', FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->first()['id'];
    if (!$tplID) {
      // Some sites may deliberately disable this, but it's unusual, so leave a notice in the log.
      Civi::log()->notice("There is no active, default password_reset message template, which has prevented emailing a reset to {username}", ['username' => $user['username']]);
      return NULL;
    }
    if (!filter_var($user['uf_name'] ?? '', \FILTER_VALIDATE_EMAIL)) {
      Civi::log()->warning("User {$user['id']} has an invalid email. Failed to send password reset.");
      return NULL;
    }

    // get the required params from the user record
    $username = $user['username'];
    $email = $user['uf_name'];
    $contactId = $user['contact_id'];

    // The template_params are used in the template like {$resetUrlHtml} and {$resetUrlHtml}
    // {$usernamePlaintext} {$usernameHtml} {$tokenTimeoutPlaintext} {$tokenTimeoutHtml}
    [$domainFromName, $domainFromEmail] = \CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    $workflowMessage = (new CRM_Standaloneusers_WorkflowMessage_PasswordReset())
      ->setRequiredParams($username, $email, $contactId, $token, $tokenTimeout)
      ->setFrom("\"$domainFromName\" <$domainFromEmail>");

    return $workflowMessage;
  }

}
