<?php

namespace Civi\Api4\Action\Afform;

use Civi\AfformAdmin\AfformAdminMeta;
use Civi\Api4\Afform;
use Civi\Api4\Entity;
use Civi\Api4\Query\SqlExpression;

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
            'permission' => 'access CiviCRM',
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
            'block' => $this->entity,
            'layout' => [],
          ];
          break;

        case 'search':
          $info['definition'] = $this->definition + [
            'title' => '',
            'permission' => 'access CiviCRM',
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
        // Convert "Contact" to "Individual", "Organization" or "Household"
        if ($afEntity['type'] === 'Contact' && !empty($afEntity['data'])) {
          $data = \CRM_Utils_JS::decode($afEntity['data']);
          $entities[] = $data['contact_type'] ?? $afEntity['type'];
        }
        else {
          $entities[] = $afEntity['type'];
        }
      }
      $joins = array_column(\CRM_Utils_Array::findAll($layout, 'af-join'), 'af-join');
      $entities = array_unique(array_merge($entities, $joins));
      $blockTags = array_unique(array_column(\CRM_Utils_Array::findAll($layout, function($el) use ($allAfforms) {
        return in_array($el['#tag'], $allAfforms);
      }), '#tag'));
      foreach ($blockTags as $blockTag) {
        if (!isset($info['blocks'][$blockTag])) {
          // Load full contents of block used on the form, then recurse into it
          $embeddedForm = Afform::get($this->checkPermissions)
            ->setFormatWhitespace(TRUE)
            ->setLayoutFormat('shallow')
            ->addWhere('directive_name', '=', $blockTag)
            ->execute()->first();
          if ($embeddedForm['type'] === 'block') {
            $info['blocks'][$blockTag] = $embeddedForm;
          }
          if (!empty($embeddedForm['join'])) {
            $entities = array_unique(array_merge($entities, [$embeddedForm['join']]));
          }
          $scanBlocks($embeddedForm['layout']);
        }
      }
    };

    if ($info['definition']['type'] === 'form') {
      if ($newForm) {
        $entities[] = $this->entity;
        $defaultEntity = AfformAdminMeta::getAfformEntity($this->entity);
        if (!empty($defaultEntity['boilerplate'])) {
          $scanBlocks($defaultEntity['boilerplate']);
        }
      }
      else {
        $scanBlocks($info['definition']['layout']);
      }

      if (array_intersect($entities, ['Individual', 'Household', 'Organization'])) {
        $entities[] = 'Contact';
      }

      // The full contents of blocks used on the form have been loaded. Get basic info about others relevant to these entities.
      $this->loadAvailableBlocks($entities, $info);
    }

    if ($info['definition']['type'] === 'block') {
      $blockEntity = $info['definition']['join'] ?? $info['definition']['block'];
      if ($blockEntity !== '*') {
        $entities[] = $blockEntity;
      }
      $scanBlocks($info['definition']['layout']);
      $this->loadAvailableBlocks($entities, $info);
    }

    if ($info['definition']['type'] === 'search') {
      $getFieldsMode = 'search';
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
        $display = \Civi\Api4\SearchDisplay::get(FALSE)
          ->addWhere('name', '=', $displayTag['display-name'])
          ->addWhere('saved_search.name', '=', $displayTag['search-name'])
          ->addSelect('*', 'type:name', 'type:icon', 'saved_search.name', 'saved_search.api_entity', 'saved_search.api_params')
          ->execute()->first();
        $display['calc_fields'] = $this->getCalcFields($display['saved_search.api_entity'], $display['saved_search.api_params']);
        $info['search_displays'][] = $display;
        if ($newForm) {
          $info['definition']['layout'][0]['#children'][] = $displayTag + ['#tag' => $display['type:name']];
        }
        $entities[] = $display['saved_search.api_entity'];
        foreach ($display['saved_search.api_params']['join'] ?? [] as $join) {
          $entities[] = explode(' AS ', $join[0])[0];
        }
      }
      if (!$newForm) {
        $scanBlocks($info['definition']['layout']);
      }
      $this->loadAvailableBlocks($entities, $info, [['join', 'IS NULL']]);
    }

    // Optimization - since contact fields are a combination of these three,
    // we'll combine them client-side rather than sending them via ajax.
    elseif (array_intersect($entities, ['Individual', 'Household', 'Organization'])) {
      $entities = array_diff($entities, ['Contact']);
    }

    foreach (array_diff($entities, $this->skipEntities) as $entity) {
      $info['entities'][$entity] = AfformAdminMeta::getApiEntity($entity);
      $info['fields'][$entity] = AfformAdminMeta::getFields($entity, ['action' => $getFieldsMode]);
    }
    $info['blocks'] = array_values($info['blocks']);

    $result[] = $info;
  }

  private function loadForm($name) {
    return Afform::get($this->checkPermissions)
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
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function loadAvailableBlocks($entities, &$info, $where = []) {
    $entities = array_diff($entities, $this->skipEntities);
    if (!$this->skipEntities) {
      $entities[] = '*';
    }
    if ($entities) {
      $blockInfo = Afform::get($this->checkPermissions)
        ->addSelect('name', 'title', 'block', 'join', 'directive_name', 'repeat')
        ->setWhere($where)
        ->addWhere('type', '=', 'block')
        ->addWhere('block', 'IN', $entities)
        ->addWhere('directive_name', 'NOT IN', array_keys($info['blocks']))
        ->execute();
      $info['blocks'] = array_merge(array_values($info['blocks']), (array) $blockInfo);
    }
  }

  /**
   * @param string $apiEntity
   * @param array $apiParams
   * @return array
   */
  private function getCalcFields($apiEntity, $apiParams) {
    $calcFields = [];
    $api = \Civi\API\Request::create($apiEntity, 'get', $apiParams);
    $selectQuery = new \Civi\Api4\Query\Api4SelectQuery($api);
    $joinMap = $joinCount = [];
    foreach ($apiParams['join'] ?? [] as $join) {
      [$entityName, $alias] = explode(' AS ', $join[0]);
      $num = '';
      if (!empty($joinCount[$entityName])) {
        $num = ' ' . (++$joinCount[$entityName]);
      }
      else {
        $joinCount[$entityName] = 1;
      }
      $label = Entity::get(FALSE)
        ->addWhere('name', '=', $entityName)
        ->addSelect('title')
        ->execute()->first()['title'];
      $joinMap[$alias] = $label . $num;
    }

    foreach ($apiParams['select'] ?? [] as $select) {
      if (strstr($select, ' AS ')) {
        $expr = SqlExpression::convert($select, TRUE);
        $field = $expr->getFields() ? $selectQuery->getField($expr->getFields()[0]) : NULL;
        $joinName = explode('.', $expr->getFields()[0] ?? '')[0];
        $label = $expr::getTitle() . ': ' . (isset($joinMap[$joinName]) ? $joinMap[$joinName] . ' ' : '') . $field['title'];
        $calcFields[] = [
          '#tag' => 'af-field',
          'name' => $expr->getAlias(),
          'defn' => [
            'label' => $label,
            'input_type' => 'Text',
          ],
        ];
      }
    }
    return $calcFields;
  }

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
