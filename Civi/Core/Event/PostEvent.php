<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
   * @var string 'create'|'edit'|'delete' etc
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
