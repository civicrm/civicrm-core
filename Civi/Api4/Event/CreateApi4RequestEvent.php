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
namespace Civi\Api4\Event;

use Civi\Core\Event\GenericHookEvent;

/**
 * civi.api4.createRequest event.
 *
 * This event fires whenever resolving the name of an api entity to an api class.
 *
 * e.g. the entity name "Contact" resolves to the class `Civi\Api4\Contact`
 * and the entity "Case" resolves to `Civi\Api4\CiviCase`
 */
class CreateApi4RequestEvent extends GenericHookEvent {

  /**
   * Name of the entity to matched to an api class
   *
   * @var string
   */
  public $entityName;

  /**
   * Resolved fully-namespaced class name.
   *
   * @var string
   */
  public $className;

  /**
   * Additional arguments which should be passed to the action factory function.
   *
   * For example, `Civi\Api4\CustomValue` factory functions require the name of the custom group.
   *
   * @var array
   */
  public $args = [];

  /**
   * Event constructor
   */
  public function __construct($entityName) {
    $this->entityName = $entityName;
  }

}
