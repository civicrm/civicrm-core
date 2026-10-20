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

use Civi;
use Civi\Core\Event\GenericHookEvent;

final class EntityProvider {

  /**
   * @var EntityMetadataInterface
   */
  private EntityMetadataInterface $meta;

  /**
   * @var EntityStorageInterface
   */
  private EntityStorageInterface $storage;

  public function __construct(private string $entityName) {}

  public function getMeta(string $property) {
    return $this->getMetaProvider()->getProperty($property);
  }

  /**
   * @return array
   *   List of field descriptors, keyed by name.
   *   Fields may or may not be defined in the underlying data-store, depending on the status of upgrade.
   *
   *   Ex: ['field_1' => ['title' => ..., 'sqlType' => ...]]
   */
  public function getFields(): array {
    $locale = \CRM_Core_I18N::getLocale();
    $cacheKey = $locale . ' ' . $this->entityName;
    if (!isset(Civi::$statics['civi.entity.fields'][$cacheKey])) {
      $facade = EntityRepository::getFacades()[$this->entityName] ?? NULL;
      // Facades use their parent's fields (as modified by listeners), altered by the facade's `alterFields`
      // callback. Fields set to FALSE by the callback are not part of the facade.
      if ($facade) {
        $fields = Civi::entity($facade['facade_of'])->getFields();
        if (isset($facade['alterFields'])) {
          $fields = $facade['alterFields']($fields);
          if (!is_array($fields)) {
            throw new \CRM_Core_Exception("The alterFields callback of facade $this->entityName must return an array of fields");
          }
        }
        Civi::$statics['civi.entity.fields'][$cacheKey] = array_filter($fields);
      }
      else {
        Civi::$statics['civi.entity.fields'][$cacheKey] = $this->getMetaProvider()->getFields();
      }
      $hookParams = [
        'entity' => $this->entityName,
        'fields' => &Civi::$statics['civi.entity.fields'][$cacheKey],
      ];
      $event = GenericHookEvent::create($hookParams);
      Civi::service('dispatcher')->dispatch('civi.entity.fields', $event);
      Civi::service('dispatcher')->dispatch("civi.entity.fields::$this->entityName", $event);
    }
    return Civi::$statics['civi.entity.fields'][$cacheKey];
  }

  public function getCustomFields(array $customGroupFilters = []): array {
    return $this->getMetaProvider()->getCustomFields($customGroupFilters);
  }

  /**
   * @return array
   *   List of field descriptors, keyed by name.
   *   Only include fields that are currently expected to be active/supported.
   *
   *   Ex: ['field_1' => ['title' => ..., 'sqlType' => ...]]
   */
  public function getSupportedFields(): array {
    $fields = $this->getFields();
    if ($this->getMeta('module') === 'civicrm' && version_compare($dbVer = \CRM_Core_BAO_Domain::version(), \CRM_Utils_System::version()) < 0) {
      // Exclude fields yet not added by pending upgrades
      $fields = array_filter($fields, function($field) use ($dbVer) {
        if (!isset($field['add'])) {
          return TRUE;
        }
        if (substr_count($field['add'], '.') < 2) {
          $field['add'] .= '.alpha1';
        }
        return version_compare($dbVer, $field['add'], '>=');
      });
    }
    return $fields;
  }

  public function getField(string $fieldName): ?array {
    $field = $this->getFields()[$fieldName] ?? NULL;
    // If not a core field, may be a custom field
    if (!$field && str_contains($fieldName, '.')) {
      [$customGroupName] = explode('.', $fieldName);
      // Include disabled custom fields so that getOptions handles them consistently
      $field = $this->getCustomFields(['name' => $customGroupName, 'is_active' => NULL])[$fieldName] ?? NULL;
    }
    return $field;
  }

  public function getOptions(string $fieldName, array $values = [], bool $includeDisabled = FALSE, bool $checkPermissions = FALSE, ?int $userId = NULL, bool $isView = FALSE): ?array {
    return $this->getMetaProvider()->getOptions($fieldName, $values, $includeDisabled, $checkPermissions, $userId, $isView);
  }

  public function writeRecords(array $records): array {
    return $this->getStorageProvider()->writeRecords($records);
  }

  public function deleteRecords(array $records): array {
    return $this->getStorageProvider()->deleteRecords($records);
  }

  public function getReferenceCounts (array $record): array {
    return $this->getStorageProvider()->getReferenceCounts($record);
  }

  private function getMetaProvider(): EntityMetadataInterface {
    if (!isset($this->meta)) {
      $entity = EntityRepository::getEntity($this->entityName);
      if (isset($entity['metaProvider'])) {
        $this->meta = new $entity['metaProvider']($this->entityName);
      }
      elseif (isset($entity['getFields'])) {
        $this->meta = new SqlEntityMetadata($this->entityName);
      }
      elseif (isset($entity['table'])) {
        $this->meta = new LegacySqlEntityMetadata($this->entityName);
      }
      else {
        throw new \CRM_Core_Exception("Unknown entity $this->entityName");
      }
    }
    return $this->meta;
  }

  private function getStorageProvider(): EntityStorageInterface {
    if (!isset($this->storage)) {
      $entity = EntityRepository::getEntity($this->entityName);
      if (isset($entity['storageProvider'])) {
        $this->storage = new $entity['storageProvider']($this->entityName);
      }
      elseif (isset($entity['table'])) {
        $this->storage = new SqlEntityStorage($this->entityName);
      }
      else {
        throw new \CRM_Core_Exception("Unknown entity $this->entityName");
      }
    }
    return $this->storage;
  }

}
