<?php
use Civi\WorkflowMessage\GenericWorkflowMessage;

/**
 *
 * @method static setResetUrlPlaintext(string $s)
 * @method static setResetUrlHtml(string $s)
 * @method static setUsernamePlaintext(string $s)
 * @method static setUsernameHtml(string $s)
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
   * @var array
   */
  protected $logParams;

  /**
   * Generate/regenerate a token for the user and load the tplParams
   */
  public function setDataFromUser(array $user, string $token) {
    $resetUrlPlaintext = \CRM_Utils_System::url('civicrm/login/password', ['token' => $token], TRUE, NULL, FALSE);
    $resetUrlHtml = htmlspecialchars($resetUrlPlaintext);
    $this->logParams = [
      'userID'   => $user['id'],
      'username' => $user['username'],
      'email'    => $user['uf_name'],
      'url'      => $resetUrlPlaintext,
    ];
    $this
      ->setResetUrlPlaintext($resetUrlPlaintext)
      ->setResetUrlHtml($resetUrlHtml)
      ->setUsernamePlaintext($user['username'])
      ->setUsernameHtml(htmlspecialchars($user['username']))
      ->setTo($user['uf_name']);
    return $this;
  }

  public function getParamsForLog(): array {
    return $this->logParams;
  }

}
