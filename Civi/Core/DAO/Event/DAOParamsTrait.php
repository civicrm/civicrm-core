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
 * Getters for DAO events: custom fields come from $params, core fields from
 * the DAO, where a NULL property means the field is not being saved.
 */
trait DAOParamsTrait {

  use EntityParamsTrait;

  protected function findObjectValue(string $paramName): array {
    if (!array_key_exists($paramName, $this->object::fieldKeys()) || !isset($this->object->$paramName)) {
      return [FALSE, NULL];
    }
    // The DAO uses the string 'null' to write a NULL.
    $value = $this->object->$paramName;
    return [TRUE, $value === 'null' ? NULL : $value];
  }

  protected function getObjectValues(): array {
    $values = [];
    foreach (array_keys($this->object::fieldKeys()) as $name) {
      [$found, $value] = $this->findObjectValue($name);
      if ($found) {
        $values[$name] = $value;
      }
    }
    return $values;
  }

}
