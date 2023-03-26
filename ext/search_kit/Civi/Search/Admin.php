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
use Civi\Api4\Entity;
use Civi\Api4\Extension;
use Civi\Api4\Query\SqlEquation;
use Civi\Api4\Query\SqlFunction;
use Civi\Api4\SearchDisplay;
use Civi\Api4\Tag;
use Civi\Api4\Utils\CoreUtil;
use CRM_Search_ExtensionUtil as E;

/**
 * Class Admin
 * @package Civi\Search
 */
class Admin {

  /**
   * Returns clientside data needed for the `crmSearchAdmin` Angular module.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getAdminSettings():array {
    $schema = self::getSchema();
    $extensions = Extension::get(FALSE)->addWhere('status', '=', 'installed')
      ->execute()->indexBy('key')->column('label');
    $data = [
      'schema' => self::addImplicitFKFields($schema),
      'joins' => self::getJoins($schema),
      'pseudoFields' => AbstractRunAction::getPseudoFields(),
      'operators' => \CRM_Utils_Array::makeNonAssociative(self::getOperators()),
      'permissions' => [],
      'functions' => self::getSqlFunctions(),
      'displayTypes' => Display::getDisplayTypes(['id', 'name', 'label', 'description', 'icon']),
      'styles' => \CRM_Utils_Array::makeNonAssociative(self::getStyles()),
      'defaultPagerSize' => (int) \Civi::settings()->get('default_pager_size'),
      'defaultDisplay' => SearchDisplay::getDefault(FALSE)->setSavedSearch(['id' => NULL])->execute()->first(),
      'modules' => $extensions,
      'defaultContactType' => \CRM_Contact_BAO_ContactType::basicTypeInfo()['Individual']['name'] ?? NULL,
      'defaultDistanceUnit' => \CRM_Utils_Address::getDefaultDistanceUnit(),
      'tags' => Tag::get()
        ->addSelect('id', 'name', 'color', 'is_selectable', 'description')
        ->addWhere('used_for', 'CONTAINS', 'civicrm_saved_search')
        ->execute(),
    ];
    $perms = \Civi\Api4\Permission::get()
      ->addWhere('group', 'IN', ['civicrm', 'cms'])
      ->addWhere('is_active', '=', 1)
      ->setOrderBy(['title' => 'ASC'])
      ->execute();
    foreach ($perms as $perm) {
      $data['permissions'][] = [
        'id' => $perm['name'],
        'text' => $perm['title'],
        'description' => $perm['description'] ?? NULL,
      ];
    }
    return $data;
  }

  /**
   * Returns operators supported by SearchKit with translated labels.
   *
   * This is a subset of APIv4 operators; some redundant ones are omitted for clarity.
   *
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
      'NOT LIKE' => E::ts('Not Like'),
      'REGEXP' => E::ts('Matches Pattern'),
      'NOT REGEXP' => E::ts("Doesn't Match Pattern"),
      'BETWEEN' => E::ts('Is Between'),
      'NOT BETWEEN' => E::ts('Not Between'),
      'IS EMPTY' => E::ts('Is Empty'),
      'IS NOT EMPTY' => E::ts('Not Empty'),
    ];
  }

  /**
   * Returns list of css style names (based on Bootstrap3).
   *
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
   * Fetch all entities the current user has permission to `get`.
   *
   * @return array[]
   * @throws \CRM_Core_Exception
   */
  public static function getSchema(): array {
    $schema = [];
    $entities = Entity::get()
      ->addSelect('name', 'title', 'title_plural', 'bridge_title', 'type', 'primary_key', 'description', 'label_field', 'icon', 'dao', 'bridge', 'ui_join_filters', 'searchable', 'order_by')
      ->addWhere('searchable', '!=', 'none')
      ->addOrderBy('title_plural')
      ->setChain([
        'get' => ['$name', 'getActions', ['where' => [['name', '=', 'get']]], ['params']],
      ])->execute();
    foreach ($entities as $entity) {
      // Skip if entity doesn't have a 'get' action or the user doesn't have permission to use get
      if ($entity['get']) {
        // Add links with translatable titles
        $links = Display::getEntityLinks($entity['name']);
        if ($links) {
          $entity['links'] = array_values($links);
        }
        $paths = CoreUtil::getInfoItem($entity['name'], 'paths');
        if (!empty($paths['add'])) {
          $entity['addPath'] = $paths['add'];
        }
        try {
          $getFields = civicrm_api4($entity['name'], 'getFields', [
            'select' => ['name', 'title', 'label', 'description', 'type', 'options', 'input_type', 'input_attrs', 'data_type', 'serialize', 'entity', 'fk_entity', 'readonly', 'operators', 'suffixes', 'nullable'],
            'where' => [['deprecated', '=', FALSE], ['name', 'NOT IN', ['api_key', 'hash']]],
            'orderBy' => ['label'],
          ]);
        }
        catch (\CRM_Core_Exception $e) {
          \Civi::log()->warning('Entity could not be loaded', ['entity' => $entity['name']]);
          continue;
        }
        foreach ($getFields as $field) {
          $field['fieldName'] = $field['name'];
          // Hack for RelationshipCache to make Relationship fields editable
          if ($entity['name'] === 'RelationshipCache') {
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
   * Add in FK fields for implicit joins.
   *
   * For example, add a `campaign_id.title` field to the Contribution entity.
   *
   * @param array $schema
   * @return array
   */
  private static function addImplicitFKFields(array $schema):array {
    foreach ($schema as &$entity) {
      if ($entity['searchable'] !== 'bridge') {
        foreach (array_reverse($entity['fields'], TRUE) as $index => $field) {
          if (!empty($field['fk_entity']) && !$field['options'] && !empty($schema[$field['fk_entity']]['label_field'])) {
            $isCustom = strpos($field['name'], '.');
            // Custom fields: append "Contact ID" etc. to original field label
            if ($isCustom) {
              $idField = array_column($schema[$field['fk_entity']]['fields'], NULL, 'name')['id'];
              $entity['fields'][$index]['label'] .= ' ' . $idField['title'];
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
        // Useful address fields (see ContactSchemaMapSubscriber)
        if ($entity['name'] === 'Contact') {
          $addressFields = ['city', 'state_province_id', 'country_id'];
          foreach ($addressFields as $fieldName) {
            foreach (['primary', 'billing'] as $type) {
              $newField = \CRM_Utils_Array::findAll($schema['Address']['fields'], ['name' => $fieldName])[0];
              $newField['name'] = "address_$type.$fieldName";
              $arg = [1 => $newField['label']];
              $newField['label'] = $type === 'primary' ? ts('Address (primary) %1', $arg) : ts('Address (billing) %1', $arg);
              $entity['fields'][] = $newField;
            }
          }
        }
      }
    }
    return array_values($schema);
  }

  /**
   * Find all the ways each entity can be joined.
   *
   * @param array $allowedEntities
   * @return array
   */
  public static function getJoins(array $allowedEntities):array {
    $joins = [];
    foreach ($allowedEntities as $entity) {
      // Multi-record custom field groups (to-date only the contact entity supports these)
      if (in_array('CustomValue', $entity['type'])) {
        // TODO: Lookup target entity from custom group if someday other entities support multi-record custom data
        $targetEntity = $allowedEntities['Contact'];
        // Join from Custom group to Contact (n-1)
        $alias = "{$entity['name']}_{$targetEntity['name']}_entity_id";
        $joins[$entity['name']][] = [
          'label' => $entity['title'] . ' ' . $targetEntity['title'],
          'description' => '',
          'entity' => $targetEntity['name'],
          'conditions' => self::getJoinConditions('entity_id', $alias . '.id'),
          'defaults' => self::getJoinDefaults($alias, $targetEntity),
          'alias' => $alias,
          'multi' => FALSE,
        ];
        // Join from Contact to Custom group (n-n)
        $alias = "{$targetEntity['name']}_{$entity['name']}_entity_id";
        $joins[$targetEntity['name']][] = [
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
        /** @var \CRM_Core_DAO $daoClass */
        $daoClass = $entity['dao'];
        $references = $daoClass::getReferenceColumns();
        $fields = array_column($entity['fields'], NULL, 'name');
        $bridge = in_array('EntityBridge', $entity['type']) ? $entity['name'] : NULL;

        // Non-bridge joins directly between 2 entities
        if ($entity['searchable'] !== 'bridge') {
          foreach ($references as $reference) {
            $keyField = $fields[$reference->getReferenceKey()] ?? NULL;
            if (
              // Sanity check - keyField must exist
              !$keyField ||
              // Exclude any joins that are better represented by pseudoconstants
              is_a($reference, 'CRM_Core_Reference_OptionValue') ||
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
          }
        }
        // Bridge joins go through an intermediary table
        if ($bridge && !empty($entity['bridge'])) {
          foreach ($entity['bridge'] as $targetKey => $bridgeInfo) {
            $baseKey = $bridgeInfo['to'];
            $reference = self::getReference($targetKey, $references);
            $dynamicCol = $reference->getTypeColumn();
            $keyField = $fields[$reference->getReferenceKey()] ?? NULL;
            foreach ($reference->getTargetEntities() as $targetTable => $targetEntityName) {
              $targetEntity = $allowedEntities[$targetEntityName] ?? NULL;
              $baseEntity = $allowedEntities[$fields[$baseKey]['fk_entity']] ?? NULL;
              if (!$targetEntity || !$baseEntity) {
                continue;
              }
              // Add joins for the two entities that connect through this bridge (n-n)
              $targetsTitle = $bridgeInfo['label'] ?? $targetEntity['title_plural'];
              $alias = $baseEntity['name'] . "_{$bridge}_" . $targetEntityName;
              $joins[$baseEntity['name']][] = [
                'label' => $baseEntity['title'] . ' ' . $targetsTitle,
                'description' => $bridgeInfo['description'] ?? E::ts('Multiple %1 per %2', [1 => $targetsTitle, 2 => $baseEntity['title']]),
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
              // Back-fill the reverse join if declared
              if ($dynamicCol && $keyField && !empty($entity['bridge'][$baseKey])) {
                $alias = $targetEntityName . "_{$bridge}_" . $baseEntity['name'];
                $joins[$targetEntityName][] = [
                  'label' => $targetEntity['title'] . ' ' . ($entity['bridge'][$baseKey]['label'] ?? $baseEntity['title_plural']),
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
   * Find the reference for a given fieldName.
   *
   * @param string $fieldName
   * @param \CRM_Core_Reference_Basic[] $references
   * @return \CRM_Core_Reference_Basic
   */
  private static function getReference(string $fieldName, array $references) {
    foreach ($references as $reference) {
      if ($reference->getReferenceKey() === $fieldName) {
        return $reference;
      }
    }
  }

  /**
   * Fill in boilerplate join clause with supplied values.
   *
   * @param string $nearCol
   * @param string $farCol
   * @param string|null $targetTable
   * @param string|null $dynamicCol
   * @return array[]
   */
  private static function getJoinConditions(string $nearCol, string $farCol, string $targetTable = NULL, string $dynamicCol = NULL):array {
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
   * Calculate default conditions for a join.
   *
   * @param string $alias
   * @param array ...$entities
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  private static function getJoinDefaults(string $alias, ...$entities):array {
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

  /**
   * Get all sql functions that can be used in SearchKit.
   *
   * Includes the generic "Arithmetic" pseudo-function.
   *
   * @return array
   */
  private static function getSqlFunctions():array {
    $functions = \CRM_Api4_Page_Api4Explorer::getSqlFunctions();
    // Add faux function "e" for SqlEquations
    $functions[] = [
      'name' => 'e',
      'title' => ts('Arithmetic'),
      'description' => ts('Add, subtract, multiply, divide'),
      'category' => SqlFunction::CATEGORY_MATH,
      'data_type' => 'Number',
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
    foreach ($functions as &$function) {
      // Normalize this property name to match fields data_type
      $function['data_type'] = $function['dataType'] ?? NULL;
      unset($function['dataType']);
      if ($function['data_type'] === 'Date') {
        $function['input_type'] = 'Date';
      }
      // Filter out empty param properties (simplifies the javascript which treats empty arrays/objects as != null)
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
