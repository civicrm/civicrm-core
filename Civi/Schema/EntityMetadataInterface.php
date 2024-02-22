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

interface EntityMetadataInterface {

  public function getProperty(string $propertyName);

  public function getFields(): array;

  public function getOptions(string $fieldName, array $values = NULL): ?array;

}
