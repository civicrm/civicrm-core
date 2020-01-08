<?php

namespace Civi\Afform;

use Civi\Api4\Afform;

/**
 * Class FormDataModel
 * @package Civi\Afform
 *
 * Examines a form and determines the entities, fields & joins in use.
 */
class FormDataModel {

  /**
   * @var array
   *   Ex: $entities['spouse']['type'] = 'Contact';
   */
  protected $entities;

  /**
   * @var array
   */
  protected $blocks = [];

  public function __construct($layout) {
    $root = AHQ::makeRoot($layout);
    $this->entities = array_column(AHQ::getTags($root, 'af-entity'), NULL, 'name');
    foreach (array_keys($this->entities) as $entity) {
      $this->entities[$entity]['fields'] = $this->entities[$entity]['joins'] = [];
    }
    // Pre-load full list of afforms in case this layout embeds other afform directives
    $this->blocks = (array) Afform::get()->setCheckPermissions(FALSE)->setSelect(['name', 'directive_name'])->execute()->indexBy('directive_name');
    $this->parseFields($layout);
  }

  /**
   * @param array $nodes
   * @param string $entity
   * @param string $join
   */
  protected function parseFields($nodes, $entity = NULL, $join = NULL) {
    foreach ($nodes as $node) {
      if (!is_array($node) || !isset($node['#tag'])) {
        continue;
      }
      elseif (!empty($node['af-fieldset']) && !empty($node['#children'])) {
        $this->parseFields($node['#children'], $node['af-fieldset'], $join);
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
        $this->entities[$entity]['joins'][$node['af-join']] = AHQ::getProps($node);
        $this->parseFields($node['#children'] ?? [], $entity, $node['af-join']);
      }
      elseif (!empty($node['#children'])) {
        $this->parseFields($node['#children'], $entity, $join);
      }
      // Recurse into embedded blocks
      if (isset($this->blocks[$node['#tag']])) {
        if (!isset($this->blocks[$node['#tag']]['layout'])) {
          $this->blocks[$node['#tag']] = Afform::get()->setCheckPermissions(FALSE)->setSelect(['name', 'layout'])->addWhere('name', '=', $this->blocks[$node['#tag']]['name'])->execute()->first();
        }
        if (!empty($this->blocks[$node['#tag']]['layout'])) {
          $this->parseFields($this->blocks[$node['#tag']]['layout'], $entity, $join);
        }
      }
    }
  }

  /**
   * @return array
   *   Ex: $entities['spouse']['type'] = 'Contact';
   */
  public function getEntities() {
    return $this->entities;
  }

}
