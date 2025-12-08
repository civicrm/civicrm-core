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

class BasicEntityMetadata extends EntityMetadataBase {

  public function getProperty(string $propertyName) {
    switch ($propertyName) {
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
