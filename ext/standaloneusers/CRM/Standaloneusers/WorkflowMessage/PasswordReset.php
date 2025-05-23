<?php
use Civi\WorkflowMessage\GenericWorkflowMessage;
use CRM_Standaloneusers_ExtensionUtil as E;

/**
 *
 * @method $this setResetUrlPlaintext(string $s)
 * @method $this setResetUrlHtml(string $s)
 * @method $this setUsernamePlaintext(string $s)
 * @method $this setUsernameHtml(string $s)
 * @method $this setTokenTimeoutPlaintext(string $s)
 * @method $this setTokenTimeoutHtml(string $s)
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
   * Plaintext token timeout.
   *
   * @var string
   *
   * @scope tplParams
   */
  public $tokenTimeoutPlaintext;

  /**
   * HTML token timeout.
   *
   * @var string
   *
   * @scope tplParams
   */
  public $tokenTimeoutHtml;

  /**
   * Load the required tplParams
   */
  public function setRequiredParams(
    string $username,
    string $email,
    int $contactId,
    string $token,
    int $tokenTimeout
    ) {
    $resetUrlPlaintext = \CRM_Utils_System::url('civicrm/login/password', ['token' => $token], TRUE, NULL, FALSE);

    if ($tokenTimeout < 120) {
      $timeout = E::ts("This link expires %1 minutes after the date of this email.", [1 => $tokenTimeout]);
    }
    elseif ($tokenTimeout < 60 * 48) {
      $timeout = E::ts("This link expires %1 hours after the date of this email.", [1 => floor($tokenTimeout / 60)]);
    }
    else {
      $timeout = E::ts("This link expires %1 days after the date of this email.", [1 => floor($tokenTimeout / 60 / 24)]);
    }

    $this
      ->setResetUrlPlaintext($resetUrlPlaintext)
      ->setResetUrlHtml(htmlspecialchars($resetUrlPlaintext))
      ->setUsernamePlaintext($username)
      ->setUsernameHtml(htmlspecialchars($username))
      ->setTokenTimeoutPlaintext($timeout)
      ->setTokenTimeoutHtml(htmlspecialchars($timeout))
      ->setTo(['name' => $username, 'email' => $email])
      ->setContactID($contactId);
    return $this;
  }

}
