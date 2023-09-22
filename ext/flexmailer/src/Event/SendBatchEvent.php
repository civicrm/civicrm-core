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
 * Class SendBatchEvent
 * @package Civi\FlexMailer\Event
 */
class SendBatchEvent extends BaseEvent {

  /**
   * @var \Civi\FlexMailer\FlexMailerTask[]
   */
  private $tasks;

  /**
   * @var bool|null
   */
  private $isCompleted = NULL;

  public function __construct($context, $tasks) {
    parent::__construct($context);
    $this->tasks = $tasks;
  }

  /**
   * @return array<\Civi\FlexMailer\FlexMailerTask>
   */
  public function getTasks() {
    return $this->tasks;
  }

  /**
   * @return bool|NULL
   */
  public function getCompleted() {
    return $this->isCompleted;
  }

  /**
   * @param bool|NULL $isCompleted
   * @return SendBatchEvent
   */
  public function setCompleted($isCompleted) {
    $this->isCompleted = $isCompleted;
    return $this;
  }

}
