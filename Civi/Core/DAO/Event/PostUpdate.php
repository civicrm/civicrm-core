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

use Civi\Core\Event\EntityParamsTrait;

/**
 * Class PostUpdate
 * @package Civi\Core\DAO\Event
 */
class PostUpdate extends \Civi\Core\Event\GenericHookEvent {

  use EntityParamsTrait;

  /**
   * @var \CRM_Core_DAO
   */
  public $object;

  /**
   * @var mixed
   */
  public $result;

  /**
   * @var string
   */
  public $eventID;

  /**
   * @var array
   */
  public $params;

  /**
   * @param \CRM_Core_DAO $object
   * @param mixed $result
   * @param array $params
   */
  public function __construct($object, $result, array $params = []) {
    $this->object = $object;
    $this->result = $result;
    $this->params = $params;
  }

  /**
   * Core fields are read from the DAO, where NULL means "not being saved".
   */
  protected function findObjectValue(string $paramName): array {
    if (!isset($this->object->$paramName)) {
      return [FALSE, NULL];
    }
    // The DAO uses the string 'null' to write a NULL.
    $value = $this->object->$paramName;
    return [TRUE, $value === 'null' ? NULL : $value];
  }

}
