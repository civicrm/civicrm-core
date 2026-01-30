<?php

namespace Civi\Afform;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Afform;
use Civi\AfformAdmin\AfformAdminMeta;
use Civi\Api4\Utils\CoreUtil;
use CRM_Afform_ExtensionUtil as E;

/**
 * Class FormDataModel
 * @package Civi\Afform
 *
 * Examines a form and determines the entities, fields & joins in use.
 */
class FormDataModel {

  protected $defaults = [
    'security' => 'RBAC',
    'actions' => ['create' => TRUE, 'update' => TRUE],
    'min' => 1,
    'max' => 1,
  ];

  /**
   * @var array[]
   *   Ex: $entities['spouse']['type'] = 'Contact';
   */
  protected $entities;

  /**
   * @var array
   */
  protected $blocks = [];

  /**
   * @var array[]
   */
  protected $searchDisplays = [];

  /**
   * @var array
   *   Ex: $secureApi4s['spouse'] = function($entity, $action, $params){...};
   */
  protected $secureApi4s = [];

  public function __construct($layout) {
    $root = AHQ::makeRoot($layout);
    $this->entities = array_column(AHQ::getTags($root, 'af-entity'), NULL, 'name');
    foreach (array_keys($this->entities) as $entity) {
      $this->entities[$entity] = array_merge($this->defaults, $this->entities[$entity]);
      $this->entities[$entity]['fields'] = $this->entities[$entity]['joins'] = [];
    }
    // Pre-load full list of afforms in case this layout embeds other afform directives
    $this->blocks = (array) Afform::get(FALSE)->setSelect(['name', 'directive_name'])->execute()->indexBy('directive_name');
    $this->parseFields($layout);
  }

  /**
   * Prepare to access APIv4 on behalf of a particular entity. This will enforce
   * any security options associated with that entity.
   *
   * $formDataModel->getSecureApi4('me')('Contact', 'get', ['where'=>[...]]);
   * $formDataModel->getSecureApi4('me')('Email', 'create', [...]);
   *
   * @param string $entityName
   *   Ex: 'Individual1', 'Individual2', 'me', 'spouse', 'children', 'theMeeting'
   *
   * @return callable
   *   API4-style
   */
  public function getSecureApi4($entityName) {
    if (!isset($this->secureApi4s[$entityName])) {
      if (!isset($this->entities[$entityName])) {
        throw new UnauthorizedException("Cannot delegate APIv4 calls on behalf of unrecognized entity ($entityName)");
      }
      $this->secureApi4s[$entityName] = function(string $entity, string $action, $params = [], $index = NULL) use ($entityName) {
        $entityDefn = $this->entities[$entityName];

        switch ($entityDefn['security']) {
          // Role-based access control. Limits driven by the current user's role/group/permissions.
          case 'RBAC':
            $params['checkPermissions'] = TRUE;
            break;

          // Form-based access control. Limits driven by form configuration.
          case 'FBAC':
            $params['checkPermissions'] = FALSE;
            break;

          default:
            throw new UnauthorizedException("Cannot process APIv4 request for $entityName ($entity.$action): Unrecognized security model");
        }

        if (!$this->isActionAllowed($entityDefn, $entity, $action, $params)) {
          throw new UnauthorizedException("Cannot process APIv4 request for $entityName ($entity.$action): Action is not approved");
        }

        return civicrm_api4($entity, $action, $params, $index);
      };
    }
    return $this->secureApi4s[$entityName];
  }

  /**
   * Determine if we are allowed to perform a given action for this entity.
   *
   * @param $entityDefn
   * @param $entity
   * @param $action
   * @param $params
   *
   * @return bool
   */
  protected function isActionAllowed($entityDefn, $entity, $action, $params) {
    if ($action === 'save') {
      foreach ($params['records'] ?? [] as $record) {
        $nextAction = !isset($record['id']) ? 'create' : 'update';
        if (!$this->isActionAllowed($entityDefn, $entity, $nextAction, $record)) {
          return FALSE;
        }
      }
      return TRUE;
    }

    // "Get" is used for autofilling entities in "update" mode, but also for
    // pre-populating fields from a template in "create" mode.
    if ($action === 'get') {
      return TRUE;
    }

    $result = !empty($entityDefn['actions'][$action]);
    return $result;
  }

  /**
   * Fills $this->entities[*]['fields'] and $this->['entities'][*]['joins'][*]['fields']
   * and $this->searchDisplays[*]['fields']
   *
   * Note that it does not fill in fields metadata from the schema, only the markup in the form.
   * To fetch field's schema definition, use the getFields function.
   *
   * @param array $nodes
   * @param string $entity
   * @param string $join
   * @param string $searchDisplay
   * @param array $afIfConditions
   */
  protected function parseFields($nodes, $entity = NULL, $join = NULL, $searchDisplay = NULL, $afIfConditions = []) {
    foreach ($nodes as $node) {
      if (!is_array($node) || !isset($node['#tag'])) {
        continue;
      }
      if (!empty($node['af-if'])) {
        $conditional = substr($node['af-if'], 1, -1);
        $afIfConditions[] = json_decode(html_entity_decode($conditional));
      }
      if ($node['#tag'] === 'af-field' && $afIfConditions) {
        $node['af-if'] = $afIfConditions;
      }
      if (isset($node['af-fieldset'])) {
        $entity = $node['af-fieldset'] ?? NULL;
        $searchDisplay = $entity ? NULL : $this->findSearchDisplay($node);
        if ($entity && isset($node['af-repeat'])) {
          $this->entities[$entity]['min'] = $node['min'] ?? 0;
          $this->entities[$entity]['max'] = $node['max'] ?? NULL;
        }
        $this->parseFields($node['#children'] ?? [], $node['af-fieldset'], $join, $searchDisplay, $afIfConditions);
      }
      elseif ($searchDisplay && $node['#tag'] === 'af-field') {
        $this->searchDisplays[$searchDisplay]['fields'][$node['name']] = AHQ::getProps($node);
      }
      elseif ($entity && $node['#tag'] === 'af-field') {
        if ($join) {
          $this->entities[$entity]['joins'][$join]['fields'][$node['name']] = AHQ::getProps($node);
        }
        else {
          $this->entities[$entity]['fields'][$node['name']] = AHQ::getProps($node);
        }
      }
      elseif ($entity && !empty($node['af-join'])) {
        $joinProps = AHQ::getProps($node);
        // If the join is declared > once, merge data
        $existingJoin = $this->entities[$entity]['joins'][$node['af-join']] ?? [];
        if (!empty($existingJoin['data']) && !empty($joinProps['data'])) {
          foreach ($joinProps['data'] as $key => $value) {
            if (!empty($existingJoin['data'][$key]) && $existingJoin['data'][$key] !== $value) {
              $joinProps['data'][$key] = array_unique(array_merge((array) $existingJoin['data'][$key], (array) $value));
            }
          }
        }
        $this->entities[$entity]['joins'][$node['af-join']] = $joinProps + $existingJoin;
        $this->parseFields($node['#children'] ?? [], $entity, $node['af-join'], NULL, $afIfConditions);
      }
      elseif (!empty($node['#children'])) {
        $this->parseFields($node['#children'], $entity, $join, $searchDisplay, $afIfConditions);
      }
      // Recurse into embedded blocks
      if (isset($this->blocks[$node['#tag']])) {
        if (!isset($this->blocks[$node['#tag']]['layout'])) {
          $this->blocks[$node['#tag']] = Afform::get(FALSE)->setSelect(['name', 'layout'])->addWhere('name', '=', $this->blocks[$node['#tag']]['name'])->execute()->first();
        }
        if (!empty($this->blocks[$node['#tag']]['layout'])) {
          $this->parseFields($this->blocks[$node['#tag']]['layout'], $entity, $join, $searchDisplay, $afIfConditions);
        }
      }
    }
  }

  /**
   * Loads a field definition from the schema
   *
   * @param string $entityName
   * @param string $fieldName
   * @param string $action
   * @param array $values
   * @return array|NULL
   */
  public static function getField(string $entityName, string $fieldName, string $action, array $values = []): ?array {
    // For explicit joins, strip the alias off the field name
    if (strpos($entityName, ' AS ')) {
      [$entityName, $alias] = explode(' AS ', $entityName);
      $fieldName = preg_replace('/^' . preg_quote($alias . '.', '/') . '/', '', $fieldName);
    }
    $namesToMatch = [$fieldName];
    // Also match base field if this is an implicit join
    if ($action === 'get' && strpos($fieldName, '.')) {
      $namesToMatch[] = substr($fieldName, 0, strrpos($fieldName, '.'));
    }
    $select = ['name', 'label', 'input_type', 'data_type', 'input_attrs', 'help_pre', 'help_post', 'options', 'fk_entity', 'required', 'dfk_entities', 'serialize'];
    if ($action === 'get') {
      $select[] = 'operators';
    }
    $params = [
      'action' => $action,
      'where' => [['name', 'IN', $namesToMatch]],
      'select' => $select,
      'loadOptions' => ['id', 'label'],
      // If the admin included this field on the form, then it's OK to get metadata about the field regardless of user permissions.
      'checkPermissions' => FALSE,
      'values' => $values,
    ];
    foreach (civicrm_api4($entityName, 'getFields', $params) as $field) {
      // In the highly unlikely event of 2 fields returned, prefer the exact match
      if ($field['name'] === $fieldName) {
        break;
      }
    }
    if (!isset($field)) {
      return NULL;
    }

    // Id field for selecting existing entity
    if ($field['name'] === CoreUtil::getIdFieldName($entityName)) {
      $entityTitle = CoreUtil::getInfoItem($entityName, 'title');
      $field['input_type'] = 'EntityRef';
      $field['fk_entity'] = $entityName;
      $field['label'] = E::ts('Existing %1', [1 => $entityTitle]);
      // Afform-only (so far) metadata tells the form to update an existing entity autofilled from this value
      $field['input_attrs']['autofill'] = 'update';
      $field['input_attrs']['placeholder'] = E::ts('Select %1', [1 => $entityTitle]);
    }
    // If this is an implicit join, get new field from fk entity
    if ($field['name'] !== $fieldName && $field['fk_entity']) {
      $params['where'] = [['name', '=', substr($fieldName, 1 + strrpos($fieldName, '.'))]];
      $originalField = $field;
      $field = civicrm_api4($field['fk_entity'], 'getFields', $params)->first();
      if ($field) {
        $field['label'] = $originalField['label'] . ' ' . $field['label'];
      }
    }
    return $field;
  }

  /**
   * @param string $inputType name of input type
   * @return string path to the angular template for this input type
   */
  public static function getInputTypeTemplate(string $inputType): ?string {
    // if afform admin is not enabled, there is no hook
    // to add custom input types so we can just use the
    // naive string concatenation
    if (!class_exists('\\Civi\\AfformAdmin\\AfformAdminMeta')) {
      return '~/af/fields/' . $inputType . '.html';
    }

    $inputTypes = AfformAdminMeta::getMetadata()['inputTypes'];

    foreach ($inputTypes as $type) {
      if ($type['name'] === $inputType) {
        return $type['template'];
      }
    }
    return NULL;
  }

  /**
   * Retrieves the main search entity plus join entities & their aliases.
   *
   * @param array $savedSearch
   * @return array
   *   e.g.
   *   ```
   *   ['Contact', 'Activity AS Contact_Activity_01']
   *   ```
   */
  public static function getSearchEntities(array $savedSearch): array {
    $entityList = [$savedSearch['api_entity']];
    foreach ($savedSearch['api_params']['join'] ?? [] as $join) {
      $entityList[] = $join[0];
      if (is_string($join[2] ?? NULL)) {
        $entityList[] = $join[2] . ' AS ' . (explode(' AS ', $join[0])[1]);
      }
    }
    return $entityList;
  }

  /**
   * Determines name of the api entit(ies) based on the field name prefix
   *
   * Note: Normally will return a single entity name, but
   * Will return 2 entity names in the case of Bridge joins e.g. RelationshipCache
   *
   * @param string $fieldName
   * @param string[] $entityList
   * @return array
   */
  public static function getSearchFieldEntityType($fieldName, $entityList): array {
    $prefix = strpos($fieldName, '.') ? explode('.', $fieldName)[0] : NULL;
    $joinEntities = [];
    $baseEntity = array_shift($entityList);
    if ($prefix) {
      foreach ($entityList as $entityAndAlias) {
        [$entity, $alias] = explode(' AS ', $entityAndAlias);
        if ($alias === $prefix) {
          $joinEntities[] = $entityAndAlias;
        }
      }
    }
    return $joinEntities ?: [$baseEntity];
  }

  /**
   * Finds a search display within a fieldset
   *
   * @param array $node
   */
  public function findSearchDisplay($node) {
    foreach (\Civi\Search\Display::getDisplayTypes(['name']) as $displayType) {
      foreach (AHQ::getTags($node, $displayType['name']) as $display) {
        $this->searchDisplays[$display['display-name']]['searchName'] = $display['search-name'];
        return $display['display-name'];
      }
    }
  }

  /**
   * @return array[]
   *   Ex: $entities['spouse']['type'] = 'Contact';
   */
  public function getEntities() {
    return $this->entities;
  }

  /**
   * @return array{type: string, fields: array, joins: array, security: string, actions: array}
   */
  public function getEntity($entityName) {
    return $this->entities[$entityName] ?? NULL;
  }

  /**
   * @return array{fields: array, searchName: string}
   */
  public function getSearchDisplay($displayName) {
    return $this->searchDisplays[$displayName] ?? NULL;
  }

}
