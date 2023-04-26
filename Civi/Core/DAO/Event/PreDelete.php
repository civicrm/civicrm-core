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

namespace Civi\Core\DAO\Event;

/**
 * Class PreDelete
 * @package Civi\Core\DAO\Event
 */
class PreDelete extends \Civi\Core\Event\GenericHookEvent {

  /**
   * @var \CRM_Core_DAO
   */
  public $object;

  /**
   * @var string
   */
  public $eventID;

  /**
   * @param $object
   */
  public function __construct($object) {
    $this->object = $object;
  }

}
