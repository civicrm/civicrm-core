<?php

namespace Civi\Api4\Action\Afform;

use Civi\AfformAdmin\AfformAdminMeta;
use Civi\Api4\Afform;
use Civi\Api4\Utils\CoreUtil;
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
            'entity_type' => $this->entity,
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
      $blockEntity = $info['definition']['join_entity'] ?? $info['definition']['entity_type'];
      if ($blockEntity !== '*') {
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
          ->addSelect('*', 'type:name', 'type:icon', 'saved_search_id.name', 'saved_search_id.api_entity', 'saved_search_id.api_params')
          ->execute()->first();
        $display['calc_fields'] = $this->getCalcFields($display['saved_search_id.api_entity'], $display['saved_search_id.api_params']);
        $info['search_displays'][] = $display;
        if ($newForm) {
          $info['definition']['layout'][0]['#children'][] = $displayTag + ['#tag' => $display['type:name']];
        }
        $entities[] = $display['saved_search_id.api_entity'];
        foreach ($display['saved_search_id.api_params']['join'] ?? [] as $join) {
          $entities[] = explode(' AS ', $join[0])[0];
        }
      }
      if (!$newForm) {
        $scanBlocks($info['definition']['layout']);
      }
      $this->loadAvailableBlocks($entities, $info, [['join_entity', 'IS NULL']]);
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
      $label = CoreUtil::getInfoItem($entityName, 'title');
      $joinMap[$alias] = $label . $num;
    }

    foreach ($apiParams['select'] ?? [] as $select) {
      if (strstr($select, ' AS ')) {
        $expr = SqlExpression::convert($select, TRUE);
        $label = $expr::getTitle();
        foreach ($expr->getFields() as $num => $fieldName) {
          $field = $selectQuery->getField($fieldName);
          $joinName = explode('.', $fieldName)[0];
          $label .= ($num ? ', ' : ': ') . (isset($joinMap[$joinName]) ? $joinMap[$joinName] . ' ' : '') . $field['title'];
        }
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
