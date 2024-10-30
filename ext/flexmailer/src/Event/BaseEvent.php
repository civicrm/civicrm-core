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
 * Class BaseEvent
 * @package Civi\FlexMailer\Event
 */
class BaseEvent extends \Civi\Core\Event\GenericHookEvent {
  /**
   * @var array
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - job: \CRM_Mailing_BAO_MailingJob
   *     - attachments: array
   */
  public $context;

  /**
   * BaseEvent constructor.
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
   * @return \CRM_Mailing_BAO_MailingJob
   */
  public function getJob() {
    return $this->context['job'];
  }

  /**
   * @return array|NULL
   */
  public function getAttachments() {
    return $this->context['attachments'];
  }

}
