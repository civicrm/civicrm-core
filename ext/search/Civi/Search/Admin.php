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

namespace Civi\Search;

/**
 * Class Admin
 * @package Civi\Search
 */
class Admin {

  /**
   * @return array
   */
  public static function getAdminSettings():array {
    $schema = self::getSchema();
    return [
      'schema' => $schema,
      'joins' => self::getJoins(array_column($schema, NULL, 'name')),
      'operators' => \CRM_Utils_Array::makeNonAssociative(self::getOperators()),
      'functions' => \CRM_Api4_Page_Api4Explorer::getSqlFunctions(),
      'displayTypes' => Display::getDisplayTypes(['id', 'name', 'label', 'description', 'icon']),
      'afformEnabled' => (bool) \CRM_Utils_Array::findAll(
        \CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(),
        ['fullName' => 'org.civicrm.afform']
      ),
      'afformAdminEnabled' => (bool) \CRM_Utils_Array::findAll(
        \CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(),
        ['fullName' => 'org.civicrm.afform_admin']
      ),
    ];
  }

  /**
   * @return string[]
   */
  public static function getOperators():array {
    return [
      '=' => '=',
      '!=' => '≠',
      '>' => '>',
      '<' => '<',
      '>=' => '≥',
      '<=' => '≤',
      'CONTAINS' => ts('Contains'),
      'IN' => ts('Is One Of'),
      'NOT IN' => ts('Not One Of'),
      'LIKE' => ts('Is Like'),
      'NOT LIKE' => ts('Not Like'),
      'BETWEEN' => ts('Is Between'),
      'NOT BETWEEN' => ts('Not Between'),
      'IS NULL' => ts('Is Null'),
      'IS NOT NULL' => ts('Not Null'),
    ];
  }

  /**
   * Fetch all entities the current user has permission to `get`
   * @return array
   */
  public static function getSchema() {
    $schema = [];
    $entities = \Civi\Api4\Entity::get()
      ->addSelect('name', 'title', 'type', 'title_plural', 'description', 'icon', 'paths', 'dao', 'bridge', 'ui_join_filters')
      ->addWhere('searchable', '=', TRUE)
      ->addOrderBy('title_plural')
      ->setChain([
        'get' => ['$name', 'getActions', ['where' => [['name', '=', 'get']]], ['params']],
      ])->execute();
    $getFields = ['name', 'label', 'description', 'options', 'input_type', 'input_attrs', 'data_type', 'serialize', 'fk_entity'];
    foreach ($entities as $entity) {
      // Skip if entity doesn't have a 'get' action or the user doesn't have permission to use get
      if ($entity['get']) {
        // Add paths (but only RUD actions) with translated titles
        foreach ($entity['paths'] as $action => $path) {
          unset($entity['paths'][$action]);
          switch ($action) {
            case 'view':
              $title = ts('View %1', [1 => $entity['title']]);
              break;

            case 'update':
              $title = ts('Edit %1', [1 => $entity['title']]);
              break;

            case 'delete':
              $title = ts('Delete %1', [1 => $entity['title']]);
              break;

            default:
              continue 2;
          }
          $entity['paths'][] = [
            'path' => $path,
            'title' => $title,
            'action' => $action,
          ];
        }
        $entity['fields'] = (array) civicrm_api4($entity['name'], 'getFields', [
          'select' => $getFields,
          'where' => [['name', 'NOT IN', ['api_key', 'hash']]],
          'orderBy' => ['label'],
        ]);
        $params = $entity['get'][0];
        // Entity must support at least these params or it is too weird for search kit
        if (!array_diff(['select', 'where', 'orderBy', 'limit', 'offset'], array_keys($params))) {
          \CRM_Utils_Array::remove($params, 'checkPermissions', 'debug', 'chain', 'language', 'select', 'where', 'orderBy', 'limit', 'offset');
          unset($entity['get']);
          $schema[] = ['params' => array_keys($params)] + array_filter($entity);
        }
      }
    }
    return $schema;
  }

  /**
   * @param array $allowedEntities
   * @return array
   */
  public static function getJoins(array $allowedEntities) {
    $joins = [];
    foreach ($allowedEntities as $entity) {
      // Multi-record custom field groups (to-date only the contact entity supports these)
      if (in_array('CustomValue', $entity['type'])) {
        $targetEntity = $allowedEntities['Contact'];
        // Join from Custom group to Contact (n-1)
        $alias = $entity['name'] . '_Contact_entity_id';
        $joins[$entity['name']][] = [
          'label' => $entity['title'] . ' ' . $targetEntity['title'],
          'description' => '',
          'entity' => 'Contact',
          'conditions' => self::getJoinConditions('entity_id', $alias . '.id'),
          'defaults' => self::getJoinDefaults($alias, $targetEntity),
          'alias' => $alias,
          'multi' => FALSE,
        ];
        // Join from Contact to Custom group (n-n)
        $alias = 'Contact_' . $entity['name'] . '_entity_id';
        $joins['Contact'][] = [
          'label' => $entity['title_plural'],
          'description' => '',
          'entity' => $entity['name'],
          'conditions' => self::getJoinConditions('id', $alias . '.entity_id'),
          'defaults' => self::getJoinDefaults($alias, $entity),
          'alias' => $alias,
          'multi' => TRUE,
        ];
      }
      // Non-custom DAO entities
      elseif (!empty($entity['dao'])) {
        /* @var \CRM_Core_DAO $daoClass */
        $daoClass = $entity['dao'];
        $references = $daoClass::getReferenceColumns();
        // Only the first bridge reference gets processed, so if it's dynamic we want to be sure it's first in the list
        usort($references, function($reference) {
          return is_a($reference, 'CRM_Core_Reference_Dynamic') ? -1 : 1;
        });
        $fields = array_column($entity['fields'], NULL, 'name');
        $bridge = in_array('EntityBridge', $entity['type']) ? $entity['name'] : NULL;
        foreach ($references as $reference) {
          $keyField = $fields[$reference->getReferenceKey()] ?? NULL;
          // Exclude any joins that are better represented by pseudoconstants
          if (is_a($reference, 'CRM_Core_Reference_OptionValue')
            || !$keyField || !empty($keyField['options'])
            // Limit bridge joins to just the first
            || $bridge && array_search($keyField['name'], $entity['bridge']) !== 0
            // Sanity check - table should match
            || $daoClass::getTableName() !== $reference->getReferenceTable()
          ) {
            continue;
          }
          // Dynamic references use a column like "entity_table" (for normal joins this value will be null)
          $dynamicCol = $reference->getTypeColumn();
          // For dynamic references getTargetEntities will return multiple targets; for normal joins this loop will only run once
          foreach ($reference->getTargetEntities() as $targetTable => $targetEntityName) {
            if (!isset($allowedEntities[$targetEntityName]) || $targetEntityName === $entity['name']) {
              continue;
            }
            $targetEntity = $allowedEntities[$targetEntityName];
            // Non-bridge joins directly between 2 entities
            if (!$bridge) {
              // Add the straight 1-1 join
              $alias = $entity['name'] . '_' . $targetEntityName . '_' . $keyField['name'];
              $joins[$entity['name']][] = [
                'label' => $entity['title'] . ' ' . $targetEntity['title'],
                'description' => $dynamicCol ? '' : $keyField['label'],
                'entity' => $targetEntityName,
                'conditions' => self::getJoinConditions($keyField['name'], $alias . '.' . $reference->getTargetKey(), $targetTable, $dynamicCol),
                'defaults' => self::getJoinDefaults($alias, $targetEntity),
                'alias' => $alias,
                'multi' => FALSE,
              ];
              // Flip the conditions & add the reverse (1-n) join
              $alias = $targetEntityName . '_' . $entity['name'] . '_' . $keyField['name'];
              $joins[$targetEntityName][] = [
                'label' => $targetEntity['title'] . ' ' . $entity['title_plural'],
                'description' => $dynamicCol ? '' : $keyField['label'],
                'entity' => $entity['name'],
                'conditions' => self::getJoinConditions($reference->getTargetKey(), $alias . '.' . $keyField['name'], $targetTable, $dynamicCol ? $alias . '.' . $dynamicCol : NULL),
                'defaults' => self::getJoinDefaults($alias, $entity),
                'alias' => $alias,
                'multi' => TRUE,
              ];
            }
            // Bridge joins (sanity check - bridge must specify exactly 2 FK fields)
            elseif (count($entity['bridge']) === 2) {
              // Get the other entity being linked through this bridge
              $baseKey = array_search($reference->getReferenceKey(), $entity['bridge']) ? $entity['bridge'][0] : $entity['bridge'][1];
              $baseEntity = $allowedEntities[$fields[$baseKey]['fk_entity']] ?? NULL;
              if (!$baseEntity) {
                continue;
              }
              // Add joins for the two entities that connect through this bridge (n-n)
              $symmetric = $baseEntity['name'] === $targetEntityName;
              $targetsTitle = $symmetric ? $allowedEntities[$bridge]['title_plural'] : $targetEntity['title_plural'];
              $alias = $baseEntity['name'] . "_{$bridge}_" . $targetEntityName;
              $joins[$baseEntity['name']][] = [
                'label' => $baseEntity['title'] . ' ' . $targetsTitle,
                'description' => ts('Multiple %1 per %2', [1 => $targetsTitle, 2 => $baseEntity['title']]),
                'entity' => $targetEntityName,
                'conditions' => array_merge(
                  [$bridge],
                  self::getJoinConditions('id', $alias . '.' . $baseKey, NULL, NULL)
                ),
                'defaults' => self::getJoinDefaults($alias, $targetEntity, $entity),
                'bridge' => $bridge,
                'alias' => $alias,
                'multi' => TRUE,
              ];
              if (!$symmetric) {
                $alias = $targetEntityName . "_{$bridge}_" . $baseEntity['name'];
                $joins[$targetEntityName][] = [
                  'label' => $targetEntity['title'] . ' ' . $baseEntity['title_plural'],
                  'description' => ts('Multiple %1 per %2', [1 => $baseEntity['title_plural'], 2 => $targetEntity['title']]),
                  'entity' => $baseEntity['name'],
                  'conditions' => array_merge(
                    [$bridge],
                    self::getJoinConditions($reference->getTargetKey(), $alias . '.' . $keyField['name'], $targetTable, $dynamicCol ? $alias . '.' . $dynamicCol : NULL)
                  ),
                  'defaults' => self::getJoinDefaults($alias, $baseEntity, $entity),
                  'bridge' => $bridge,
                  'alias' => $alias,
                  'multi' => TRUE,
                ];
              }
            }
          }
        }
      }
    }
    return $joins;
  }

  /**
   * Boilerplate join clause
   *
   * @param string $nearCol
   * @param string $farCol
   * @param string $targetTable
   * @param string|null $dynamicCol
   * @return array[]
   */
  private static function getJoinConditions($nearCol, $farCol, $targetTable = NULL, $dynamicCol = NULL) {
    $conditions = [
      [
        $nearCol,
        '=',
        $farCol,
      ],
    ];
    if ($dynamicCol) {
      $conditions[] = [
        $dynamicCol,
        '=',
        "'$targetTable'",
      ];
    }
    return $conditions;
  }

  /**
   * @param $alias
   * @param array ...$entities
   * @return array
   */
  private static function getJoinDefaults($alias, ...$entities):array {
    $conditions = [];
    foreach ($entities as $entity) {
      foreach ($entity['ui_join_filters'] ?? [] as $fieldName) {
        $field = civicrm_api4($entity['name'], 'getFields', [
          'select' => ['options'],
          'where' => [['name', '=', $fieldName]],
          'loadOptions' => ['name'],
        ])->first();
        $value = isset($field['options'][0]) ? json_encode($field['options'][0]['name']) : '';
        $conditions[] = [
          $alias . '.' . $fieldName . ($value ? ':name' : ''),
          '=',
          $value,
        ];
      }
    }
    return $conditions;
  }

}
