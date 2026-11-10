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

use Civi\Core\Event\EntityParamsSetterTrait;

/**
 * Class PreUpdate
 *
 * Custom values set here are stored by save(). BAOs that store $params['custom']
 * themselves after save() (e.g. Contact, Contribution) overwrite any field the
 * caller also passed; use hook_civicrm_pre for those.
 *
 * @package Civi\Core\DAO\Event
 */
class PreUpdate extends \Civi\Core\Event\GenericHookEvent {

  use DAOParamsTrait;
  use EntityParamsSetterTrait;

  /**
   * @var \CRM_Core_DAO
   */
  public $object;

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
   * @param array $params
   */
  public function __construct($object, array $params = []) {
    $this->object = $object;
    $this->params = $params;
  }

  protected function setObjectValue(string $name, $value): void {
    // Format the value as writeRecord() does. copyValues() skips NULL, so NULL is passed as '' to clear the field.
    $this->object->copyValues([$name => $value ?? '']);
  }

  protected function getEntityId(): ?int {
    return $this->object->id ? (int) $this->object->id : NULL;
  }

}
