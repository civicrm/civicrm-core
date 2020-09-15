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
 * Class ComposeBatchEvent
 * @package Civi\FlexMailer\Event
 *
 * The general formula for an agent handling ComposeBatchEvent:
 *
 * ```php
 * foreach ($event->getTasks() as $task) {
 *   $task->setMailParam('Subject', 'Hello');
 *   $task->setMailParam('text', 'Hello there');
 *   $task->setMailParam('html', '<html><body><p>Hello there</p></body></html>');
 * }
 * ```
 */
class ComposeBatchEvent extends BaseEvent {

  /**
   * @var \Civi\FlexMailer\FlexMailerTask[]
   */
  private $tasks;

  public function __construct($context, $tasks) {
    parent::__construct($context);
    $this->tasks = $tasks;
  }

  /**
   * @return \Civi\FlexMailer\FlexMailerTask[]
   */
  public function getTasks() {
    return $this->tasks;
  }

  /**
   * @return bool
   */
  public function isPreview() {
    return isset($this->context['is_preview'])
      ? (bool) $this->context['is_preview']
      : FALSE;
  }

}
