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
 * Class PostUpdate
 * @package Civi\Core\DAO\Event
 */
class PostDelete extends \Symfony\Component\EventDispatcher\Event {

  /**
   * @var \CRM_Core_DAO
   */
  public $object;

  /**
   * @var int|false
   */
  public $result;

  /**
   * @param \CRM_Core_DAO $object
   * @param int|false $result
   */
  public function __construct($object, $result) {
    $this->object = $object;
    $this->result = $result;
  }

}
