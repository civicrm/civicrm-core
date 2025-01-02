<?php

namespace Civi\Api4\Action\Afform;

use Civi\AfformAdmin\AfformAdminMeta;
use Civi\Api4\Afform;
use Civi\Api4\AfformBehavior;
use Civi\Api4\Utils\CoreUtil;

/**
 * This action is used by the Afform Admin extension to load metadata for the Admin GUI.
 *
 * @package Civi\Api4\Action\Afform
 */
class LoadAdminData extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Any properties already known about the afform
   * @var array
   * @required
   */
  protected $definition;

  /**
   * Entity type when creating a new form
   * @var string
   */
  protected $entity;

  /**
   * A list of entities whose blocks & fields are not needed
   * @var array
   */
  protected $skipEntities = [];

  public function _run(\Civi\Api4\Generic\Result $result) {
    $info = ['entities' => [], 'fields' => [], 'blocks' => []];
    $entities = [];
    $newForm = empty($this->definition['name']);

    if (!$newForm) {
      // Load existing afform if name provided
      $info['definition'] = $this->loadForm($this->definition['name']);
    }
    else {
      // Create new blank afform
      switch ($this->definition['type']) {
        case 'form':
          $info['definition'] = $this->definition + [
            'title' => '',
            'permission' => ['access CiviCRM'],
            'layout' => [
              [
                '#tag' => 'af-form',
                'ctrl' => 'afform',
                '#children' => [],
              ],
            ],
          ];
          break;

        case 'block':
          $info['definition'] = $this->definition + [
            'title' => '',
            'entity_type' => $this->entity,
            'layout' => [],
          ];
          break;

        case 'search':
          $info['definition'] = $this->definition + [
            'title' => '',
            'permission' => ['access CiviCRM'],
            'layout' => [
              [
                '#tag' => 'div',
                'af-fieldset' => '',
                '#children' => [],
              ],
            ],
          ];
          break;
      }
    }

    $getFieldsMode = 'create';

    // Generate list of possibly embedded afform tags to search for
    $allAfforms = \Civi::service('afform_scanner')->findFilePaths();
    foreach ($allAfforms as $name => $path) {
      $allAfforms[$name] = _afform_angular_module_name($name, 'dash');
    }

    /**
     * Find all entities by recursing into embedded afforms
     * @param array $layout
     */
    $scanBlocks = function($layout) use (&$scanBlocks, &$info, &$entities, $allAfforms) {
      // Find declared af-entity tags
      foreach (\CRM_Utils_Array::findAll($layout, ['#tag' => 'af-entity']) as $afEntity) {
        $entities[] = $afEntity['type'];
      }
      $joins = array_column(\CRM_Utils_Array::findAll($layout, 'af-join'), 'af-join');
      $entities = array_unique(array_merge($entities, $joins));
      $blockTags = array_unique(array_column(\CRM_Utils_Array::findAll($layout, function($el) use ($allAfforms) {
        return isset($el['#tag']) && in_array($el['#tag'], $allAfforms);
      }), '#tag'));
      foreach ($blockTags as $blockTag) {
        if (!isset($info['blocks'][$blockTag])) {
          // Load full contents of block used on the form, then recurse into it
          $embeddedForm = Afform::get($this->checkPermissions)
            ->addSelect('*', 'directive_name')
            ->setFormatWhitespace(TRUE)
            ->setLayoutFormat('shallow')
            ->addWhere('directive_name', '=', $blockTag)
            ->execute()->first();
          if ($embeddedForm['type'] === 'block') {
            $info['blocks'][$blockTag] = $embeddedForm;
          }
          if (!empty($embeddedForm['join_entity'])) {
            $entities = array_unique(array_merge($entities, [$embeddedForm['join_entity']]));
          }
          $scanBlocks($embeddedForm['layout']);
        }
      }
    };

    if ($info['definition']['type'] === 'form') {
      if ($newForm) {
        $entities[] = $this->entity;
        $defaultEntity = AfformAdminMeta::getMetadata()['entities'][$this->entity] ?? [];
        if (!empty($defaultEntity['boilerplate'])) {
          $scanBlocks($defaultEntity['boilerplate']);
        }
      }
      else {
        $scanBlocks($info['definition']['layout']);
      }

      // The full contents of blocks used on the form have been loaded. Get basic info about others relevant to these entities.
      $this->loadAvailableBlocks($entities, $info);
    }

    if ($info['definition']['type'] === 'block') {
      $blockEntity = $info['definition']['join_entity'] ?? $info['definition']['entity_type'] ?? NULL;
      if ($blockEntity) {
        $entities[] = $blockEntity;
      }
      $scanBlocks($info['definition']['layout']);
      $this->loadAvailableBlocks($entities, $info);
    }

    if ($info['definition']['type'] === 'search') {
      $getFieldsMode = 'get';
      $displayTags = [];
      if ($newForm) {
        [$searchName, $displayName] = array_pad(explode('.', $this->entity ?? ''), 2, '');
        $displayTags[] = ['search-name' => $searchName, 'display-name' => $displayName];
      }
      else {
        foreach (\Civi\Search\Display::getDisplayTypes(['name']) as $displayType) {
          $displayTags = array_merge($displayTags, \CRM_Utils_Array::findAll($info['definition']['layout'], ['#tag' => $displayType['name']]));
        }
      }
      foreach ($displayTags as $displayTag) {
        if (isset($displayTag['display-name']) && strlen($displayTag['display-name'])) {
          $displayGet = \Civi\Api4\SearchDisplay::get(FALSE)
            ->addWhere('name', '=', $displayTag['display-name'])
            ->addWhere('saved_search_id.name', '=', $displayTag['search-name']);
        }
        else {
          $displayGet = \Civi\Api4\SearchDisplay::getDefault(FALSE)
            ->setSavedSearch($displayTag['search-name']);
        }
        $display = $displayGet
          ->addSelect('*', 'type:name', 'type:icon', 'saved_search_id.name', 'saved_search_id.label', 'saved_search_id.api_entity', 'saved_search_id.api_params', 'saved_search_id.form_values')
          ->execute()->first();
        if (!$display) {
          continue;
        }
        $display['calc_fields'] = \Civi\Search\Meta::getCalcFields($display['saved_search_id.api_entity'], $display['saved_search_id.api_params']);
        $display['filters'] = empty($displayTag['filters']) ? NULL : (\CRM_Utils_JS::getRawProps($displayTag['filters']) ?: NULL);
        $info['search_displays'][] = $display;
        if ($newForm) {
          $info['definition']['layout'][0]['#children'][] = $displayTag + ['#tag' => $display['type:name']];
        }
        $entities[] = $display['saved_search_id.api_entity'];
        foreach ($display['saved_search_id.api_params']['join'] ?? [] as $join) {
          $entities[] = explode(' AS ', $join[0])[0];
          // Add bridge entities (but only if they are tagged searchable e.g. RelationshipCache)
          if (is_string($join[2] ?? NULL) &&
            in_array(CoreUtil::getInfoItem($join[2], 'searchable'), ['primary', 'secondary'])
          ) {
            $entities[] = $join[2];
          }
        }
      }
      if (!$newForm) {
        $scanBlocks($info['definition']['layout']);
      }
      $entities = array_unique($entities);
      $this->loadAvailableBlocks($entities, $info, [['join_entity', 'IS NULL']]);
    }

    foreach (array_diff($entities, $this->skipEntities) as $entity) {
      $info['entities'][$entity] = AfformAdminMeta::getApiEntity($entity);
      $info['fields'][$entity] = AfformAdminMeta::getFields($entity, ['action' => $getFieldsMode]);
      foreach ($info['fields'][$entity] as $key => $field) {
        $info['fields'][$entity][$key]['original_input_type'] = $field['input_type'];
      }
      $behaviors = AfformBehavior::get(FALSE)
        ->addWhere('entities', 'CONTAINS', $entity)
        ->execute();
      foreach ($behaviors as $behavior) {
        $behavior['modes'] = $behavior['modes'][$entity];
        $info['behaviors'][$entity][] = $behavior;
      }
    }
    $info['blocks'] = array_values($info['blocks']);

    $result[] = $info;
  }

  /**
   * @param string $name
   * @return array|null
   */
  private function loadForm($name) {
    return Afform::get($this->checkPermissions)
      ->addSelect('*', 'directive_name')
      ->setFormatWhitespace(TRUE)
      ->setLayoutFormat('shallow')
      ->addWhere('name', '=', $name)
      ->execute()->first();
  }

  /**
   * Get basic info about blocks relevant to these entities.
   *
   * @param array $entities
   * @param array $info
   * @param array $where
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function loadAvailableBlocks($entities, &$info, $where = []) {
    $entities = array_diff($entities, $this->skipEntities);
    if (!$this->skipEntities) {
      $entities[] = '*';
    }
    // A block of type "Contact" also applies to "Individual", "Organization" & "Household".
    if (array_intersect($entities, \CRM_Contact_BAO_ContactType::basicTypes())) {
      $entities[] = 'Contact';
    }
    if ($entities) {
      $blockInfo = Afform::get($this->checkPermissions)
        ->addSelect('name', 'title', 'entity_type', 'join_entity', 'directive_name')
        ->setWhere($where)
        ->addWhere('type', '=', 'block')
        ->addWhere('entity_type', 'IN', $entities)
        ->addWhere('directive_name', 'NOT IN', array_keys($info['blocks']))
        ->execute();
      $info['blocks'] = array_merge(array_values($info['blocks']), (array) $blockInfo);
    }
  }

  /**
   * @return array[]
   */
  public function fields() {
    return [
      [
        'name' => 'definition',
        'data_type' => 'Array',
      ],
      [
        'name' => 'blocks',
        'data_type' => 'Array',
      ],
      [
        'name' => 'entities',
        'data_type' => 'Array',
      ],
      [
        'name' => 'fields',
        'data_type' => 'Array',
      ],
      [
        'name' => 'search_displays',
        'data_type' => 'Array',
      ],
    ];
  }

  /**
   * @return array
   */
  public function getDefinition():array {
    return $this->definition;
  }

  /**
   * @param array $definition
   */
  public function setDefinition(array $definition) {
    $this->definition = $definition;
    return $this;
  }

}
