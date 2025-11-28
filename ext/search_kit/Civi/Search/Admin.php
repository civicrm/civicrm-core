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
    // Check minimum permission needed to reach this
    if (!\CRM_Core_Permission::check('manage own search_kit')) {
      return [];
    }
    $schema = self::getSchema();
    $data = [
      'schema' => self::addImplicitFKFields($schema),
      'joins' => self::getJoins($schema),
      'pseudoFields' => AbstractRunAction::getPseudoFields(),
      'operators' => \CRM_Utils_Array::makeNonAssociative(self::getOperators()),
      'permissions' => [],
      'functions' => self::getSqlFunctions(),
      'displayTypes' => Display::getDisplayTypes(['id', 'name', 'label', 'description', 'icon', 'grouping']),
      'styles' => \CRM_Utils_Array::makeNonAssociative(self::getStyles()),
      'defaultPagerSize' => (int) \Civi::settings()->get('default_pager_size'),
      'defaultDisplay' => SearchDisplay::getDefault(FALSE)->setSavedSearch(['id' => NULL])->execute()->first(),
      'modules' => \CRM_Core_BAO_Managed::getBaseModules(),
      'defaultDistanceUnit' => \CRM_Utils_Address::getDefaultDistanceUnit(),
      'optionAttributes' => \CRM_Core_SelectValues::optionAttributes(),
      'jobFrequency' => \Civi\Api4\Job::getFields()
        ->addWhere('name', '=', 'run_frequency')
        ->setLoadOptions(['id', 'label'])
        ->execute()->first()['options'],
      'tags' => Tag::get()
        ->addSelect('id', 'label', 'color', 'is_selectable', 'description')
        ->addWhere('used_for', 'CONTAINS', 'civicrm_saved_search')
        ->execute(),
      'myName' => \CRM_Core_Session::singleton()->getLoggedInContactDisplayName(),
      'dateFormats' => self::getDateFormats(),
      'numberAttributes' => [
        \NumberFormatter::MAX_FRACTION_DIGITS => E::ts('Max Decimal Places'),
        \NumberFormatter::MIN_FRACTION_DIGITS => E::ts('Min Decimal Places'),
      ],
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
      'CONTAINS' => E::ts('Contains All'),
      'NOT CONTAINS' => E::ts("Doesn't Contain All"),
      'CONTAINS ONE OF' => E::ts('Contains Any'),
      'NOT CONTAINS ONE OF' => E::ts("Doesn't Contain Any"),
      'IN' => E::ts('Is One Of'),
      'NOT IN' => E::ts('Not One Of'),
      'LIKE' => E::ts('Is Like'),
      'NOT LIKE' => E::ts('Not Like'),
      'REGEXP' => E::ts('Matches Pattern'),
      'NOT REGEXP' => E::ts("Doesn't Match Pattern"),
      'REGEXP BINARY' => E::ts('Matches Pattern (case-sensitive)'),
      'NOT REGEXP BINARY' => E::ts("Doesn't Match Pattern (case-sensitive)"),
      'BETWEEN' => E::ts('Is Between'),
      'NOT BETWEEN' => E::ts('Not Between'),
      'IS EMPTY' => E::ts('Is Empty'),
      'IS NOT EMPTY' => E::ts('Not Empty'),
      'IS NOT NULL' => E::ts('Any Value'),
      'IS NULL' => E::ts('No Value'),
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
      ->addSelect('name', 'title', 'title_plural', 'bridge_title', 'type', 'primary_key', 'description', 'label_field', 'parent_field', 'search_fields', 'icon', 'dao', 'bridge', 'ui_join_filters', 'searchable', 'order_by')
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
        try {
          $getFields = civicrm_api4($entity['name'], 'getFields', [
            'select' => ['name', 'title', 'label', 'description', 'type', 'options', 'input_type', 'input_attrs', 'data_type', 'serialize', 'entity', 'fk_entity', 'readonly', 'operators', 'suffixes', 'nullable'],
            'where' => [['deprecated', '=', FALSE], ['name', 'NOT IN', ['api_key', 'hash']]],
            'orderBy' => ['label' => 'ASC'],
          ])->indexBy('name');
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
        if (empty($entity['fields'])) {
          continue;
        }
        $entity['default_columns'] = self::getDefaultColumns($entity, $getFields);
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
   * Build default columns - these are used when creating a new search with this entity
   *
   * @param array $entity
   * @param iterable $getFields
   * @return array
   */
  private static function getDefaultColumns(array $entity, iterable $getFields): array {
    // Start with id & label
    $defaultColumns = array_merge(
      $entity['primary_key'] ?? [],
      $entity['search_fields'] ?? []
    );
    $possibleColumns = [];
    // Include grouping fields like "event_type_id"
    foreach ((array) (CoreUtil::getCustomGroupExtends($entity['name'])['grouping'] ?? []) as $column) {
      $possibleColumns[$column] = "$column:label";
    }
    // Other possible relevant columns... now we're just guessing
    //
    // TODO: these can be specified using the @searchColumns annotation on
    // the Api4 entity class so would probably be better to specify sensible
    // options for core entities explicitly - which allows you to order logically too
    $possibleColumns['description'] = 'description';
    // E.g. "activity_status_id"
    $possibleColumns[strtolower($entity['name']) . 'status_id'] = strtolower($entity['name']) . 'status_id:label';
    $possibleColumns['start_date'] = 'start_date';
    $possibleColumns['end_date'] = 'end_date';
    $possibleColumns['is_active'] = 'is_active';
    foreach ($possibleColumns as $fieldName => $columnName) {
      if (
        (str_contains($columnName, ':') && !empty($getFields[$fieldName]['options'])) ||
        (!str_contains($columnName, ':') && !empty($getFields[$fieldName]))
      ) {
        $defaultColumns[] = $columnName;
      }
    }
    // `array_unique` messes with the index so reset it with `array_values` so it cleanly encodes to a json array
    return array_values(array_unique($defaultColumns));
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
        foreach (array_reverse($entity['fields'] ?? [], TRUE) as $index => $field) {
          if (!empty($field['fk_entity']) && !$field['options'] && !$field['suffixes'] && !empty($schema[$field['fk_entity']]['search_fields'])) {
            $labelFields = array_unique(array_merge($schema[$field['fk_entity']]['search_fields'], (array) ($schema[$field['fk_entity']]['label_field'] ?? [])));
            foreach ($labelFields as $labelField) {
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
              $newField = \CRM_Utils_Array::findAll($schema[$field['fk_entity']]['fields'], ['name' => $labelField])[0] ?? NULL;
              if ($newField) {
                $newField['name'] = $field['name'] . '.' . $labelField;
                $newField['label'] = $field['label'] . ' ' . $newField['label'];
                array_splice($entity['fields'], $index + 1, 0, [$newField]);
              }
            }
          }
        }
        // Useful address fields (see ContactSchemaMapSubscriber)
        if ($entity['name'] === 'Contact') {
          $addressFields = ['city', 'state_province_id', 'country_id', 'street_address', 'postal_code', 'supplemental_address_1'];
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
      $isVirtualEntity = (bool) array_intersect(['CustomValue', 'SavedSearch'], $entity['type']);

      // Normal DAO entities (excludes virtual entities)
      // FIXME: At this point DAO entities have enough metadata that using getReferenceColumns()
      // is no longer necessary and they could be handled the same as virtual entities.
      // So this entire block could, in theory, be removed in favor of the foreach loop below.
      // Just need a solid before/after comparison to ensure the output stays stable.
      if (!empty($entity['dao']) && !$isVirtualEntity) {
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
            foreach ($reference->getTargetEntities() as $dynamicValue => $targetEntityName) {
              if (!isset($allowedEntities[$targetEntityName])) {
                // Skip if target entity doesn't exist
                continue;
              }
              $targetEntity = $allowedEntities[$targetEntityName];
              $isSelf = $targetEntityName === $entity['name'];
              // Add the straight 1-1 join (but only if it's not a reference to itself, those can be done with implicit joins)
              if (!$isSelf) {
                $alias = $entity['name'] . '_' . $targetEntityName . '_' . $keyField['name'];
                $joins[$entity['name']][] = [
                  'label' => $entity['title'] . ' ' . ($dynamicCol ? $targetEntity['title'] : $keyField['label']),
                  'description' => '',
                  'entity' => $targetEntityName,
                  'conditions' => self::getJoinConditions($keyField['name'], $alias . '.' . $reference->getTargetKey(), $dynamicValue, $dynamicCol),
                  // No default conditions for straight joins as they ought to be direct 1-1
                  'defaults' => [],
                  'alias' => $alias,
                  'multi' => FALSE,
                ];
              }
              // Flip the conditions & add the reverse (1-n) join
              $alias = $targetEntityName . '_' . $entity['name'] . '_' . $keyField['name'];
              $joins[$targetEntityName][] = [
                'label' => ($isSelf ? $keyField['label'] : $targetEntity['title']) . ' ' . $entity['title_plural'],
                'description' => $dynamicCol || $isSelf ? '' : $keyField['label'],
                'entity' => $entity['name'],
                'conditions' => self::getJoinConditions($reference->getTargetKey(), $alias . '.' . $keyField['name'], $dynamicValue, $dynamicCol ? $alias . '.' . $dynamicCol : NULL),
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
            foreach ($reference->getTargetEntities() as $dynamicValue => $targetEntityName) {
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
                  self::getJoinConditions('id', $alias . '.' . $baseKey)
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
                    self::getJoinConditions($reference->getTargetKey(), $alias . '.' . $keyField['name'], $dynamicValue, $alias . '.' . $dynamicCol)
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

      // This handles joins for custom fields and virtual entities which don't have a DAO.
      foreach ($entity['fields'] as $field) {
        // FIXME: See comment above: this loop should be able to handle every entity.
        // Above block could be removed and the first part of this conditional
        // `($field['type'] === 'Custom' || $isVirtualEntity)` can be removed.
        if (($field['type'] === 'Custom' || $isVirtualEntity) && $field['fk_entity'] && $field['input_type'] === 'EntityRef') {
          $entityRefJoins = self::getEntityRefJoins($entity, $field);
          foreach ($entityRefJoins as $joinEntity => $joinInfo) {
            $joins[$joinEntity][] = $joinInfo;
          }
        }
      }
    }
    // Add contact joins to the contactType pseudo-entities
    foreach (\CRM_Contact_BAO_ContactType::basicTypes() as $contactType) {
      $joins += [$contactType => []];
      $joins[$contactType] = array_merge($joins[$contactType], $joins['Contact']);
    }
    return $joins;
  }

  /**
   * Get joins for entity reference custom fields, and the entity_id field in
   * multi-record custom groups.
   *
   * @return array[]
   */
  public static function getEntityRefJoins(array $entity, array $field): array {
    $exploded = explode('.', $field['name']);
    $bareFieldName = array_reverse($exploded)[0];
    $alias = "{$entity['name']}_{$field['fk_entity']}_$bareFieldName";
    $joins[$entity['name']] = [
      'label' => $entity['title'] . ' ' . $field['label'],
      'description' => $field['description'],
      'entity' => $field['fk_entity'],
      'conditions' => self::getJoinConditions($field['name'], $alias . '.id'),
      'defaults' => [],
      'alias' => $alias,
      'multi' => FALSE,
    ];
    // Do reverse join if not the same entity
    if ($entity['name'] !== $field['fk_entity']) {
      $alias = "{$field['fk_entity']}_{$entity['name']}_$bareFieldName";
      $joins[$field['fk_entity']] = [
        'label' => $entity['title_plural'],
        'description' => $entity['description'] ?? '',
        'entity' => $entity['name'],
        'conditions' => self::getJoinConditions('id', "$alias.{$field['name']}"),
        'defaults' => [],
        'alias' => $alias,
        'multi' => TRUE,
      ];
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
   * @param string|null $dynamicValue
   * @param string|null $dynamicCol
   * @return array[]
   */
  private static function getJoinConditions(string $nearCol, string $farCol, ?string $dynamicValue = NULL, ?string $dynamicCol = NULL):array {
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
        "'$dynamicValue'",
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
  private static function getJoinDefaults(string $alias, ...$entities): array {
    $conditions = [];
    foreach ($entities as $entity) {
      if (!empty($entity['ui_join_filters'])) {
        $filterFields = civicrm_api4($entity['name'], 'getFields', [
          'select' => ['name', 'options', 'data_type'],
          'where' => [['name', 'IN', $entity['ui_join_filters']]],
          'loadOptions' => ['name'],
        ])->indexBy('name');
        foreach ($filterFields as $fieldName => $field) {
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
    $functions = CoreUtil::getSqlFunctions();
    // Add faux function "e" for SqlEquations
    $functions[] = [
      'name' => 'e',
      'title' => ts('Arithmetic'),
      'description' => ts('Add, subtract, multiply, divide'),
      'category' => SqlFunction::CATEGORY_MATH,
      'data_type' => 'Number',
      'options' => FALSE,
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

  private static function getDateFormats(): array {
    return \Civi\Api4\Setting::getFields(FALSE)
      ->addWhere('name', 'LIKE', 'dateformat%')
      ->execute()
      ->indexBy('name')
      ->column('title');
  }

}
