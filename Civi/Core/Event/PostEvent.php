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
   * @var \CRM_Core_DAO
   */
  public $object;

  /**
   * @var array
   */
  public $params;

  /**
   * Class constructor
   *
   * @param string $action
   * @param string $entity
   * @param int $id
   * @param object $object
   * @param array $params
   */
  public function __construct($action, $entity, $id, &$object, $params = NULL) {
    $this->action = $action;
    $this->entity = $entity;
    $this->id = $id;
    $this->object = &$object;
    $this->params = $params;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->action, $this->entity, $this->id, &$this->object, $this->params];
  }

}
