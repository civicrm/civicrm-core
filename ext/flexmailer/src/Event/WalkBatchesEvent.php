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
 * Class WalkBatchesEvent
 * @package Civi\FlexMailer\Event
 */
class WalkBatchesEvent extends BaseEvent {

  /**
   * @var callable
   */
  protected $callback;

  /**
   * @var bool|null
   */
  protected $isDelivered = NULL;

  public function __construct($context, $callback) {
    parent::__construct($context);
    $this->callback = $callback;
  }

  /**
   * @return bool|NULL
   */
  public function getCompleted() {
    return $this->isDelivered;
  }

  /**
   * @param bool|NULL $isCompleted
   * @return WalkBatchesEvent
   */
  public function setCompleted($isCompleted) {
    $this->isDelivered = $isCompleted;
    return $this;
  }

  /**
   * @param \Civi\FlexMailer\FlexMailerTask[] $tasks
   * @return mixed
   */
  public function visit($tasks) {
    return call_user_func($this->callback, $tasks);
  }

}
