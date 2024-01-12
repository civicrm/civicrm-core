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
namespace Civi\FlexMailer\Event;

/**
 * Class CheckSendableEvent
 * @package Civi\FlexMailer\Event
 */
class CheckSendableEvent extends \Civi\Core\Event\GenericHookEvent {

  /**
   * @var array
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - attachments: array
   */
  public $context;

  /**
   * @var array
   *   A list of error messages.
   *   Ex: array('subject' => 'The Subject field is blank').
   *   Example keys: 'subject', 'name', 'from_name', 'from_email', 'body', 'body_html:unsubscribeUrl'.
   */
  protected $errors = [];

  /**
   * CheckSendableEvent constructor.
   * @param array $context
   */
  public function __construct(array $context) {
    $this->context = $context;
  }

  /**
   * @return \CRM_Mailing_BAO_Mailing
   */
  public function getMailing() {
    return $this->context['mailing'];
  }

  /**
   * @return array|NULL
   */
  public function getAttachments() {
    return $this->context['attachments'];
  }

  public function setError($key, $message) {
    $this->errors[$key] = $message;
    return $this;
  }

  public function getErrors() {
    return $this->errors;
  }

  /**
   * Get the full, combined content of the header, body, and footer.
   *
   * @param string $field
   *   Name of the field -- either 'body_text' or 'body_html'.
   * @return string|NULL
   *   Either the combined header+body+footer, or NULL if there is no body.
   */
  public function getFullBody($field) {
    if ($field !== 'body_text' && $field !== 'body_html') {
      throw new \RuntimeException("getFullBody() only supports body_text and body_html");
    }
    $mailing = $this->getMailing();
    $header = $mailing->header_id && $mailing->header_id != 'null' ? \CRM_Mailing_BAO_MailingComponent::findById($mailing->header_id) : NULL;
    $footer = $mailing->footer_id && $mailing->footer_id != 'null' ? \CRM_Mailing_BAO_MailingComponent::findById($mailing->footer_id) : NULL;
    if (empty($mailing->{$field})) {
      return NULL;
    }
    return ($header ? $header->{$field} : '') . $mailing->{$field} . ($footer ? $footer->{$field} : '');
  }

}
