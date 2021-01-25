<?php

namespace Civi\Api4\Action\Afform;

use Civi\AfformAdmin\AfformAdminMeta;
use Civi\Api4\Afform;

/**
 * This action is used by the Afform Admin extension to load metadata for the Admin GUI.
 *
 * @package Civi\Api4\Action\Afform
 */
class LoadAdminData extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Any properties already known about the afform
   * @var array
   */
  protected $definition;

  /**
   * Entity type when creating a new form
   * @var string
   */
  protected $entity;

  public function _run(\Civi\Api4\Generic\Result $result) {
    $info = ['fields' => [], 'blocks' => []];
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
            'layout' => [],
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
    // Find all entities by recursing into embedded afforms
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
      $blockInfo = Afform::get($this->checkPermissions)
        ->addSelect('name', 'title', 'block', 'join', 'directive_name')
        ->addWhere('type', '=', 'block')
        ->addWhere('block', 'IN', $entities)
        ->addWhere('directive_name', 'NOT IN', array_keys($info['blocks']))
        ->execute();
      $info['blocks'] = array_merge(array_values($info['blocks']), (array) $blockInfo);
    }

    if ($info['definition']['type'] === 'block') {
      $entities[] = $info['definition']['join'] ?? $info['definition']['block'];
    }

    // Optimization - since contact fields are a combination of these three,
    // we'll combine them client-side rather than sending them via ajax.
    if (array_intersect($entities, ['Individual', 'Household', 'Organization'])) {
      $entities = array_diff($entities, ['Contact']);
    }

    foreach ($entities as $entity) {
      $info['entities'][$entity] = AfformAdminMeta::getApiEntity($entity);
      $info['fields'][$entity] = AfformAdminMeta::getFields($entity, ['action' => $getFieldsMode]);
    }

    $result[] = $info;
  }

  private function loadForm($name) {
    return Afform::get($this->checkPermissions)
      ->setFormatWhitespace(TRUE)
      ->setLayoutFormat('shallow')
      ->addWhere('name', '=', $name)
      ->execute()->first();
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
