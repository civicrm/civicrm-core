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

class SqlEntityStorage implements EntityStorageInterface {

  /**
   * @var string
   */
  protected string $entityName;

  public function __construct(string $entityName) {
    $this->entityName = $entityName;
  }

  public function writeRecords(array $records): array {
    // TODO: Implement writeRecords() method.
  }

  public function deleteRecords(array $records): array {
    // TODO: Implement deleteRecords() method.
  }

  public function findReferences(array $record): array {
    // TODO
  }

  public function getReferenceCounts(array $record): array {
    $counts = [];
    $links = $this->getReferenceLinkSql($record);
    foreach ($links as $link) {
      $select = $link['select'];
      $select->select('COUNT(*) AS count');
      $count = \CRM_Core_DAO::singleValueQuery($select->toSQL());
      if ($count) {
        $counts[] = [
          'name' => $link['entity'],
          'field' => $link['field'],
          'count' => $count,
          'table' => $link['table'],
          'column' => $link['column'],
          'key' => $link['key'],
        ];
      }
    }
    $daoClass = \Civi::entity($this->entityName)->getMeta('class');
    if ($daoClass) {
      $dao = new $daoClass();
      $dao->copyValues($record);
      // TODO: Fix hook to work with non-dao entities
      // (probably need to add 2 params for $entityName and $record and deprecate the $dao param)
      \CRM_Utils_Hook::referenceCounts($dao, $counts);
    }
    return $counts;
  }

  private function getReferenceLinkSql(array $record): array {
    $links = [];
    $sep = \CRM_Core_DAO::VALUE_SEPARATOR;
    foreach ($this->getLinksToTable() as $link) {
      if (!isset($record[$link['key']])) {
        continue;
      }
      $select = \CRM_Utils_SQL_Select::from($link['table']);
      $args = ['!col' => $link['column'], '!op' => '=', '@val' => $record[$link['key']]];
      if (!empty($link['serialize'])) {
        $args['!op'] = 'LIKE';
        $args['@val'] = "%$sep{$record[$link['key']]}$sep%";
      }
      $select->where('`!col` !op @val', $args);
      foreach ($link['condition'] ?? [] as $key => $value) {
        $select->where("`!key` = @val", ['!key' => $key, '@val' => $value]);
      }
      $link['select'] = $select;
      $links[] = $link;
    }
    return $links;
  }

  public function getLinksToTable(): array {
    $links = [];
    $thisTableName = \Civi::entity($this->entityName)->getMeta('table');
    foreach (EntityRepository::getEntities() as $entityName => $entityInfo) {
      $entity = \Civi::entity($entityName);
      $entityFields = array_merge($entity->getFields(), $entity->getCustomFields(['is_active' => NULL]));
      foreach ($entityFields as $fieldName => $fieldInfo) {
        if (($fieldInfo['entity_reference']['entity'] ?? NULL) === $this->entityName) {
          $links[] = [
            'entity' => $entityName,
            'field' => $fieldName,
            'table' => $fieldInfo['table_name'] ?? $entity->getMeta('table'),
            'column' => $fieldInfo['column_name'] ?? $fieldName,
            'key' => $fieldInfo['entity_reference']['key'] ?? $entity->getMeta('primary_key'),
            'serialize' => $fieldInfo['serialize'] ?? FALSE,
          ];
        }
        elseif (!empty($fieldInfo['entity_reference']['dynamic_entity'])) {
          foreach ($entity->getOptions($fieldInfo['entity_reference']['dynamic_entity']) ?? [] as $option) {
            if (
              // Old-style: Flat arrays of ['table_name' => 'Entity Label'] will be formatted with identical id & name
              // Note: Adding strtolower ensures both values are also lowercase && not something like 'Contact' => 'Contact'
              (strtolower($option['id']) === $option['name'] && $option['id'] === $thisTableName) ||
              // New-style: ['id' => 'value', 'name' => 'EntityName', 'label' => 'Entity Label'][]
              (strtolower($option['id']) !== $option['name'] && $option['name'] === $this->entityName)
            ) {
              $links[] = [
                'entity' => $entityName,
                'field' => $fieldName,
                'table' => $fieldInfo['table_name'] ?? $entity->getMeta('table'),
                'column' => $fieldInfo['column_name'] ?? $fieldName,
                'key' => $fieldInfo['entity_reference']['key'] ?? $entity->getMeta('primary_key'),
                'serialize' => $fieldInfo['serialize'] ?? FALSE,
                'condition' => [$fieldInfo['entity_reference']['dynamic_entity'] => $option['id']],
              ];
            }
          }
        }
      }
    }
    return $links;
  }

}
