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
class PostEvent extends GenericHookEvent {

  /**
   * This adapter automatically emits a narrower event.
   *
   * For example, `hook_civicrm_pre(Contact, ...)` will also dispatch `hook_civicrm_pre::Contact`.
   *
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function dispatchSubevent(PostEvent $event) {
    \Civi::service('dispatcher')->dispatch("hook_civicrm_post::" . $event->entity, $event);
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
   * @var Object
   */
  public $object;

  /**
   * Class constructor
   *
   * @param string $action
   * @param string $entity
   * @param int $id
   * @param object $object
   */
  public function __construct($action, $entity, $id, &$object) {
    $this->action = $action;
    $this->entity = $entity;
    $this->id = $id;
    $this->object = &$object;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->action, $this->entity, $this->id, &$this->object];
  }

}
