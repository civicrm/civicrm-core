<?php
use Civi\WorkflowMessage\GenericWorkflowMessage;

/**
 *
 * @method $this setResetUrlPlaintext(string $s)
 * @method $this setResetUrlHtml(string $s)
 * @method $this setUsernamePlaintext(string $s)
 * @method $this setUsernameHtml(string $s)
 *
 */
class CRM_Standaloneusers_WorkflowMessage_PasswordReset extends GenericWorkflowMessage {

  public const WORKFLOW = 'password_reset';

  /**
   * Plaintext full URL to user's password reset.
   *
   * @var string
   *
   * @scope tplParams
   */
  public $resetUrlPlaintext;

  /**
   * HTML full URL to user's password reset.
   *
   * @var string
   *
   * @scope tplParams
   */
  public $resetUrlHtml;

  /**
   * Plaintext username.
   *
   * @var string
   *
   * @scope tplParams
   */
  public $usernamePlaintext;

  /**
   * HTML username.
   *
   * @var string
   *
   * @scope tplParams
   */
  public $usernameHtml;

  /**
   * Load the required tplParams
   */
  public function setRequiredParams(
    string $username,
    string $email,
    int $contactId,
    string $token
    ) {
    $resetUrlPlaintext = \CRM_Utils_System::url('civicrm/login/password', ['token' => $token], TRUE, NULL, FALSE);

    $this
      ->setResetUrlPlaintext($resetUrlPlaintext)
      ->setResetUrlHtml(htmlspecialchars($resetUrlPlaintext))
      ->setUsernamePlaintext($username)
      ->setUsernameHtml(htmlspecialchars($username))
      ->setTo(['name' => $username, 'email' => $email])
      ->setContactID($contactId);
    return $this;
  }

}
