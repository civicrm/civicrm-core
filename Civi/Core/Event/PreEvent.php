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

namespace Civi\Core\Event;

/**
 * Class AuthorizeEvent
 * @package Civi\API\Event
 */
class PreEvent extends GenericHookEvent {

  /**
   * This adapter automatically emits a narrower event.
   *
   * For example, `hook_civicrm_pre(Contact, ...)` will also dispatch `hook_civicrm_pre::Contact`.
   *
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function dispatchSubevent(PreEvent $event) {
    \Civi::dispatcher()->dispatch("hook_civicrm_pre::" . $event->entity, $event);
  }

  /**
   * One of: 'create'|'edit'|'delete'
   *
   * @var string
   */
  public $action;

  /**
   * @var string
   */
  public $entity;

  /**
   * @var int|null
   */
  public $id;

  /**
   * @var array
   */
  public $params;

  /**
   * Class constructor.
   *
   * @param string $action
   * @param string $entity
   * @param int $id
   * @param array $params
   */
  public function __construct($action, $entity, $id, &$params) {
    $this->action = $action;
    $this->entity = $entity;
    $this->id = $id;
    $this->params = &$params;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->action, $this->entity, $this->id, &$this->params];
  }

}
