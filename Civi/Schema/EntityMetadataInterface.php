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

  public function getCustomFields(array $customGroupFilters = []): array;

  public function getField(string $fieldName): ?array;

  public function getOptions(string $fieldName, array $values = [], bool $includeDisabled = FALSE, bool $checkPermissions = FALSE, ?int $userId = NULL): ?array;

}
