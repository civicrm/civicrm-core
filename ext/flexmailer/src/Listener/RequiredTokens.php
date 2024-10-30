<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
namespace Civi\FlexMailer\Listener;

use Civi\Core\Service\AutoService;
use CRM_Flexmailer_ExtensionUtil as E;
use Civi\FlexMailer\Event\CheckSendableEvent;

/**
 * Class RequiredTokens
 * @package Civi\FlexMailer\Listener
 *
 * The RequiredTokens listener checks draft mailings for traditional
 * CiviMail tokens like `{action.unsubscribeUrl}`, which are often required
 * to comply with anti-spam regulations.
 */
class RequiredTokens extends AutoService {

  use IsActiveTrait;

  /**
   * @service civi_flexmailer_required_tokens
   */
  public static function factory(): RequiredTokens {
    return new static(
      ['traditional'],
      [
        'domain.address' => ts("Domain address - displays your organization's postal address."),
        'action.optOutUrl or action.unsubscribeUrl' => [
          'action.optOut' => ts("'Opt out via email' - displays an email address for recipients to opt out of receiving emails from your organization."),
          'action.optOutUrl' => ts("'Opt out via web page' - creates a link for recipients to click if they want to opt out of receiving emails from your organization. Alternatively, you can include the 'Opt out via email' token."),
          'action.unsubscribe' => ts("'Unsubscribe via email' - displays an email address for recipients to unsubscribe from the specific mailing list used to send this message."),
          'action.unsubscribeUrl' => ts("'Unsubscribe via web page' - creates a link for recipients to unsubscribe from the specific mailing list used to send this message. Alternatively, you can include the 'Unsubscribe via email' token or one of the Opt-out tokens."),
        ],
      ]
    );
  }

  /**
   * @var array
   *   Ex: array('domain.address' => ts('The organizational postal address'))
   */
  private $requiredTokens;

  /**
   * @var array
   *
   * List of template-types for which we are capable of enforcing token
   * requirements.
   */
  private $templateTypes;

  /**
   * RequiredTokens constructor.
   *
   * @param array $templateTypes
   *   Ex: array('traditional').
   * @param array $requiredTokens
   *   Ex: array('domain.address' => ts('The organizational postal address'))
   */
  public function __construct($templateTypes, $requiredTokens) {
    $this->templateTypes = $templateTypes;
    $this->requiredTokens = $requiredTokens;
  }

  /**
   * Check for required fields.
   *
   * @param \Civi\FlexMailer\Event\CheckSendableEvent $e
   */
  public function onCheckSendable(CheckSendableEvent $e) {
    if (!$this->isActive()) {
      return;
    }
    if (\Civi::settings()->get('disable_mandatory_tokens_check')) {
      return;
    }
    if (!in_array($e->getMailing()->template_type, $this->getTemplateTypes())) {
      return;
    }

    foreach (['body_html', 'body_text'] as $field) {
      $str = $e->getFullBody($field);
      if (empty($str)) {
        continue;
      }
      foreach ($this->findMissingTokens($str) as $token => $desc) {
        $e->setError("{$field}:{$token}", E::ts('This message is missing a required token - {%1}: %2',
          [1 => $token, 2 => $desc]
        ));
      }
    }
  }

  public function findMissingTokens($str) {
    $missing = [];
    foreach ($this->getRequiredTokens() as $token => $value) {
      if (!is_array($value)) {
        if (!preg_match('/(^|[^\{])' . preg_quote('{' . $token . '}') . '/', $str)) {
          $missing[$token] = $value;
        }
      }
      else {
        $present = FALSE;
        $desc = NULL;
        foreach ($value as $t => $d) {
          $desc = $d;
          if (preg_match('/(^|[^\{])' . preg_quote('{' . $t . '}') . '/', $str)) {
            $present = TRUE;
          }
        }
        if (!$present) {
          $missing[$token] = $desc;
        }
      }
    }
    return $missing;
  }

  /**
   * @return array
   *   Ex: array('domain.address' => ts('The organizational postal address'))
   */
  public function getRequiredTokens() {
    return $this->requiredTokens;
  }

  /**
   * @param array $requiredTokens
   *   Ex: array('domain.address' => ts('The organizational postal address'))
   * @return RequiredTokens
   */
  public function setRequiredTokens($requiredTokens) {
    $this->requiredTokens = $requiredTokens;
    return $this;
  }

  /**
   * @return array
   *   Ex: array('traditional').
   */
  public function getTemplateTypes() {
    return $this->templateTypes;
  }

  /**
   * Set the list of template-types for which we check tokens.
   *
   * @param array $templateTypes
   *   Ex: array('traditional').
   * @return RequiredTokens
   */
  public function setTemplateTypes($templateTypes) {
    $this->templateTypes = $templateTypes;
    return $this;
  }

  /**
   * Add to the list of template-types for which we check tokens.
   *
   * @param array $templateTypes
   *   Ex: array('traditional').
   * @return RequiredTokens
   */
  public function addTemplateTypes($templateTypes) {
    $this->templateTypes = array_unique(array_merge($this->templateTypes, $templateTypes));
    return $this;
  }

}
