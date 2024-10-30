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
namespace Civi\FlexMailer;

/**
 * Class FlexMailerTask
 * @package Civi\FlexMailer
 *
 * A FlexMailerTask describes an individual message that needs to be
 * composed and delivered. Generally, it's used in three steps:
 *  - At the start, we instantiate the task with a few inputs
 *    (e.g. $contactId and $address).
 *  - During composition, we read those values and fill-in the
 *    message's content ($task->setMailParams(...));
 *  - During delivery, we read the message ($task->getMailParams())
 *    and send it.
 */
class FlexMailerTask {

  /**
   * @var int
   *   A persistent record for this email delivery.
   * @see \CRM_Mailing_Event_DAO_MailingEventQueue
   */
  private $eventQueueId;

  /**
   * @var int
   *   The ID of the recipiient.
   * @see \CRM_Contact_DAO_Contact
   */
  private $contactId;

  /**
   * @var string
   *   An authentication code. The name is misleading - it may be hash, but
   *   that implementation detail is outside our purview.
   */
  private $hash;

  /**
   * @var string
   *   Selected/preferred email address of the intended recipient.
   */
  private $address;

  /**
   * The full email message to send to this recipient (per alterMailParams).
   *
   * @var array
   * @see MailParams
   * @see \CRM_Utils_Hook::alterMailParams()
   */
  private $mailParams = [];

  /**
   * FlexMailerTask constructor.
   *
   * @param int $eventQueueId
   *   A persistent record for this email delivery.
   * @param int $contactId
   *   The ID of the recipiient.
   * @param string $hash
   *   An authentication code.
   * @param string $address
   *   Selected/preferred email address of the intended recipient.
   */
  public function __construct(
    $eventQueueId,
    $contactId,
    $hash,
    $address
  ) {
    $this->eventQueueId = $eventQueueId;
    $this->contactId = $contactId;
    $this->hash = $hash;
    $this->address = $address;
  }

  /**
   * @return int
   * @see \CRM_Mailing_Event_DAO_MailingEventQueue
   */
  public function getEventQueueId() {
    return $this->eventQueueId;
  }

  /**
   * @return int
   *   The ID of the recipiient.
   * @see \CRM_Contact_DAO_Contact
   */
  public function getContactId() {
    return $this->contactId;
  }

  /**
   * @return string
   *   An authentication code. The name is misleading - it may be hash, but
   *   that implementation detail is outside our purview.
   */
  public function getHash() {
    return $this->hash;
  }

  /**
   * @return string
   *   Selected email address of the intended recipient.
   */
  public function getAddress() {
    return $this->address;
  }

  /**
   * @return bool
   */
  public function hasContent() {
    return !empty($this->mailParams['html']) || !empty($this->mailParams['text']);
  }

  /**
   * @return array
   * @see CRM_Utils_Hook::alterMailParams
   */
  public function getMailParams() {
    return $this->mailParams;
  }

  /**
   * @param \array $mailParams
   * @return FlexMailerTask
   * @see CRM_Utils_Hook::alterMailParams
   */
  public function setMailParams($mailParams) {
    $this->mailParams = $mailParams;
    return $this;
  }

  /**
   * @param string $key
   * @param string $value
   * @return $this
   * @see CRM_Utils_Hook::alterMailParams
   */
  public function setMailParam($key, $value) {
    $this->mailParams[$key] = $value;
    return $this;
  }

  /**
   * @param string $key
   * @return string
   * @see CRM_Utils_Hook::alterMailParams
   */
  public function getMailParam($key) {
    return isset($this->mailParams[$key]) ? $this->mailParams[$key] : NULL;
  }

}
