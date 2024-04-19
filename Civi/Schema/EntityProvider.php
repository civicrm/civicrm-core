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

final class EntityProvider {

  /**
   * @var string
   */
  private string $entityName;

  /**
   * @var EntityMetadataInterface
   */
  private $meta;

  /**
   * @var EntityStorageInterface
   */
  private $storage;

  public function __construct(string $entityName) {
    $this->entityName = $entityName;
  }

  public function getMeta(string $property) {
    return $this->getMetaProvider()->getProperty($property);
  }

  public function getFields(): array {
    return $this->getMetaProvider()->getFields();
  }

  public function getField(string $fieldName): ?array {
    return $this->getFields()[$fieldName] ?? NULL;
  }

  public function getOptions(string $fieldName, array $values = NULL): ?array {
    return $this->getMetaProvider()->getOptions($fieldName, $values);
  }

  public function writeRecords(array $records): array {
    return $this->getStorageProvider()->writeRecords($records);
  }

  public function deleteRecords(array $records): array {
    return $this->getStorageProvider()->deleteRecords($records);
  }

  private function getMetaProvider(): EntityMetadataInterface {
    if (!isset($this->meta)) {
      $entity = EntityRepository::getEntity($this->entityName);
      if (isset($entity['metaProvider'])) {
        return new $entity['metaProvider']($this->entityName);
      }
      if (isset($entity['getFields'])) {
        return new SqlEntityMetadata($this->entityName);
      }
      if (isset($entity['table'])) {
        return new LegacySqlEntityMetadata($this->entityName);
      }
      throw new \CRM_Core_Exception("Unknown entity $this->entityName");
    }
    return $this->meta;
  }

  private function getStorageProvider(): EntityStorageInterface {
    if (!isset($this->storage)) {
      $entity = EntityRepository::getEntity($this->entityName);
      if (isset($entity['storageProvider'])) {
        return new $entity['storageProvider']($this->entityName);
      }
      if (isset($entity['table'])) {
        return new SqlEntityStorage($this->entityName);
      }
      throw new \CRM_Core_Exception("Unknown entity $this->entityName");
    }
    return $this->storage;
  }

}
