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

use Civi\Api4\Action\SearchDisplay\AbstractRunAction;
use Civi\Api4\Query\SqlEquation;
use Civi\Api4\Query\SqlFunction;
use Civi\Api4\Tag;
use CRM_Search_ExtensionUtil as E;

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
    $extensions = \CRM_Extension_System::singleton()->getMapper();
    return [
      'schema' => self::addImplicitFKFields($schema),
      'joins' => self::getJoins($schema),
      'pseudoFields' => AbstractRunAction::getPseudoFields(),
      'operators' => \CRM_Utils_Array::makeNonAssociative(self::getOperators()),
      'functions' => self::getSqlFunctions(),
      'displayTypes' => Display::getDisplayTypes(['id', 'name', 'label', 'description', 'icon']),
      'styles' => \CRM_Utils_Array::makeNonAssociative(self::getStyles()),
      'defaultPagerSize' => \Civi::settings()->get('default_pager_size'),
      'afformEnabled' => $extensions->isActiveModule('afform'),
      'afformAdminEnabled' => $extensions->isActiveModule('afform_admin'),
      'tags' => Tag::get()
        ->addSelect('id', 'name', 'color', 'is_selectable', 'description')
        ->addWhere('used_for', 'CONTAINS', 'civicrm_saved_search')
        ->execute(),
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
      'CONTAINS' => E::ts('Contains'),
      'IN' => E::ts('Is One Of'),
      'NOT IN' => E::ts('Not One Of'),
      'LIKE' => E::ts('Is Like'),
      'REGEXP' => E::ts('Matches Regexp'),
      'NOT LIKE' => E::ts('Not Like'),
      'NOT REGEXP' => E::ts('Not Regexp'),
      'BETWEEN' => E::ts('Is Between'),
      'NOT BETWEEN' => E::ts('Not Between'),
      'IS EMPTY' => E::ts('Is Empty'),
      'IS NOT EMPTY' => E::ts('Not Empty'),
    ];
  }

  /**
   * @return string[]
   */
  public static function getStyles():array {
    return [
      'default' => E::ts('Default'),
      'primary' => E::ts('Primary'),
      'secondary' => E::ts('Secondary'),
      'success' => E::ts('Success'),
      'info' => E::ts('Info'),
      'warning' => E::ts('Warning'),
      'danger' => E::ts('Danger'),
    ];
  }

  /**
   * Fetch all entities the current user has permission to `get`
   * @return array
   */
  public static function getSchema() {
    $schema = [];
    $entities = \Civi\Api4\Entity::get()
      ->addSelect('name', 'title', 'title_plural', 'bridge_title', 'type', 'primary_key', 'description', 'label_field', 'icon', 'paths', 'dao', 'bridge', 'ui_join_filters', 'searchable')
      ->addWhere('searchable', '!=', 'none')
      ->addOrderBy('title_plural')
      ->setChain([
        'get' => ['$name', 'getActions', ['where' => [['name', '=', 'get']]], ['params']],
      ])->execute();
    foreach ($entities as $entity) {
      // Skip if entity doesn't have a 'get' action or the user doesn't have permission to use get
      if ($entity['get']) {
        // Add paths (but only RUD actions) with translated titles
        foreach ($entity['paths'] as $action => $path) {
          unset($entity['paths'][$action]);
          if (in_array($action, ['view', 'update', 'delete'], TRUE)) {
            $entity['paths'][] = [
              'path' => $path,
              'action' => $action,
            ];
          }
        }
        $getFields = civicrm_api4($entity['name'], 'getFields', [
          'select' => ['name', 'title', 'label', 'description', 'type', 'options', 'input_type', 'input_attrs', 'data_type', 'serialize', 'entity', 'fk_entity', 'readonly', 'operators'],
          'where' => [['name', 'NOT IN', ['api_key', 'hash']]],
          'orderBy' => ['label'],
        ]);
        foreach ($getFields as $field) {
          $field['fieldName'] = $field['name'];
          // Hack for RelationshipCache to make Relationship fields editable
          if ($entity['name'] === 'RelationshipCache') {
            $entity['primary_key'] = ['relationship_id'];
            if (in_array($field['name'], ['is_active', 'start_date', 'end_date'])) {
              $field['readonly'] = FALSE;
            }
          }
          $entity['fields'][] = $field;
        }
        $params = $entity['get'][0];
        // Entity must support at least these params or it is too weird for search kit
        if (!array_diff(['select', 'where', 'orderBy', 'limit', 'offset'], array_keys($params))) {
          \CRM_Utils_Array::remove($params, 'checkPermissions', 'debug', 'chain', 'language', 'select', 'where', 'orderBy', 'limit', 'offset');
          unset($entity['get']);
          $schema[$entity['name']] = ['params' => array_keys($params)] + array_filter($entity);
        }
      }
    }
    return $schema;
  }

  /**
   * Add in FK fields for implicit joins
   * For example, add a `campaign_id.title` field to the Contribution entity
   * @param $schema
   * @return array
   */
  private static function addImplicitFKFields($schema) {
    foreach ($schema as &$entity) {
      if ($entity['searchable'] !== 'bridge') {
        foreach (array_reverse($entity['fields'], TRUE) as $index => $field) {
          if (!empty($field['fk_entity']) && !$field['options'] && !empty($schema[$field['fk_entity']]['label_field'])) {
            $isCustom = strpos($field['name'], '.');
            // Custom fields: append "Contact ID" to original field label
            if ($isCustom) {
              $entity['fields'][$index]['label'] .= ' ' . E::ts('Contact ID');
            }
            // DAO fields: use title instead of label since it represents the id (title usually ends in ID but label does not)
            else {
              $entity['fields'][$index]['label'] = $field['title'];
            }
            // Add the label field from the other entity to this entity's list of fields
            $newField = \CRM_Utils_Array::findAll($schema[$field['fk_entity']]['fields'], ['name' => $schema[$field['fk_entity']]['label_field']])[0];
            $newField['name'] = $field['name'] . '.' . $schema[$field['fk_entity']]['label_field'];
            $newField['label'] = $field['label'] . ' ' . $newField['label'];
            array_splice($entity['fields'], $index, 0, [$newField]);
          }
        }
      }
    }
    return array_values($schema);
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
        usort($references, function($first, $second) {
          foreach ([-1 => $first, 1 => $second] as $weight => $reference) {
            if (is_a($reference, 'CRM_Core_Reference_Dynamic')) {
              return $weight;
            }
          }
          return 0;
        });
        $fields = array_column($entity['fields'], NULL, 'name');
        $bridge = in_array('EntityBridge', $entity['type']) ? $entity['name'] : NULL;
        $bridgeFields = array_keys($entity['bridge'] ?? []);
        foreach ($references as $reference) {
          $keyField = $fields[$reference->getReferenceKey()] ?? NULL;
          if (
            // Sanity check - keyField must exist
            !$keyField ||
            // Exclude any joins that are better represented by pseudoconstants
            is_a($reference, 'CRM_Core_Reference_OptionValue') || (!$bridge && !empty($keyField['options'])) ||
            // Limit bridge joins to just the first
            ($bridge && array_search($keyField['name'], $bridgeFields) !== 0) ||
            // Sanity check - table should match
            $daoClass::getTableName() !== $reference->getReferenceTable()
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
                'label' => $entity['title'] . ' ' . ($dynamicCol ? $targetEntity['title'] : $keyField['label']),
                'description' => '',
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
              $baseKey = array_search($reference->getReferenceKey(), $bridgeFields) ? $bridgeFields[0] : $bridgeFields[1];
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
                'description' => $entity['bridge'][$baseKey]['description'] ?? E::ts('Multiple %1 per %2', [1 => $targetsTitle, 2 => $baseEntity['title']]),
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
                  'description' => $entity['bridge'][$reference->getReferenceKey()]['description'] ?? E::ts('Multiple %1 per %2', [1 => $baseEntity['title_plural'], 2 => $targetEntity['title']]),
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
          'select' => ['options', 'data_type'],
          'where' => [['name', '=', $fieldName]],
          'loadOptions' => ['name'],
        ])->first();
        $value = '';
        if ($field['data_type'] === 'Boolean') {
          $value = TRUE;
        }
        elseif (isset($field['options'][0])) {
          $fieldName .= ':name';
          $value = json_encode($field['options'][0]['name']);
        }
        $conditions[] = [
          $alias . '.' . $fieldName,
          '=',
          $value,
        ];
      }
    }
    return $conditions;
  }

  private static function getSqlFunctions() {
    $functions = \CRM_Api4_Page_Api4Explorer::getSqlFunctions();
    // Add faux function "e" for SqlEquations
    $functions[] = [
      'name' => 'e',
      'title' => ts('Arithmetic'),
      'description' => ts('Add, subtract, multiply, divide'),
      'category' => SqlFunction::CATEGORY_MATH,
      'dataType' => 'Number',
      'params' => [
        [
          'label' => ts('Value'),
          'min_expr' => 1,
          'max_expr' => 1,
          'must_be' => ['SqlField', 'SqlNumber'],
        ],
        [
          'label' => ts('Value'),
          'min_expr' => 1,
          'max_expr' => 99,
          'flag_before' => array_combine(SqlEquation::$arithmeticOperators, SqlEquation::$arithmeticOperators),
          'must_be' => ['SqlField', 'SqlNumber'],
        ],
      ],
    ];
    // Filter out empty param properties (simplifies the javascript which treats empty arrays/objects as != null)
    foreach ($functions as &$function) {
      foreach ($function['params'] as $i => $param) {
        $function['params'][$i] = array_filter($param);
      }
    }
    usort($functions, function($a, $b) {
      return $a['title'] <=> $b['title'];
    });
    return $functions;
  }

}
