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

namespace Civi\Schema;

class SqlEntityMetadata extends EntityMetadataBase {

  public function getProperty(string $propertyName) {
    switch ($propertyName) {
      case 'primary_keys':
        $keys = [];
        foreach ($this->getFields() as $name => $field) {
          if (!empty($field['primary_key'])) {
            $keys[] = $name;
          }
        }
        return $keys;

      case 'primary_key':
        foreach ($this->getFields() as $name => $field) {
          if (!empty($field['primary_key'])) {
            return $name;
          }
        }
        return NULL;

      case 'paths':
        if (isset($this->getEntity()['getPaths'])) {
          return $this->getEntity()['getPaths']();
        }
        return [];

      default:
        return $this->getEntity()[$propertyName] ?? $this->getEntity()['getInfo']()[$propertyName] ?? NULL;

    }
  }

  public function getFields(): array {
    return $this->getEntity()['getFields']();
  }

}
