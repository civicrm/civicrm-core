<?php

namespace Civi\Afform;

/**
 * Class FormDataModel
 * @package Civi\Afform
 *
 * The FormDataModel examines a form and determines the list of entities/fields
 * which are used by the form.
 */
class FormDataModel {

  /**
   * @var array
   *   Ex: $entities['spouse']['type'] = 'Contact';
   */
  protected $entities;

  /**
   * Gets entity metadata and all blocks & fields from the form
   *
   * @param array $layout
   *   The root element of the layout, in shallow/deep format.
   * @return static
   *   Parsed summary of the entities used in a given form.
   */
  public static function create($layout) {
    $root = AHQ::makeRoot($layout);
    $entities = array_column(AHQ::getTags($root, 'af-entity'), NULL, 'name');
    foreach (array_keys($entities) as $entity) {
      $entities[$entity]['fields'] = $entities[$entity]['blocks'] = [];
    }
    self::parseFields($layout, $entities);

    $self = new static();
    $self->entities = $entities;
    return $self;
  }

  /**
   * @param array $nodes
   * @param array $entities
   *   A list of entities, keyed by name.
   *     This will be updated to populate 'fields' and 'blocks'.
   *     Ex: $entities['spouse']['type'] = 'Contact';
   * @param string $entity
   */
  protected static function parseFields($nodes, &$entities, $entity = NULL) {
    foreach ($nodes as $node) {
      if (!is_array($node) || !isset($node['#tag'])) {
        //nothing
      }
      elseif (!empty($node['af-fieldset'])) {
        self::parseFields($node['#children'], $entities, $node['af-fieldset']);
      }
      elseif ($entity && $node['#tag'] === 'af-field') {
        $entities[$entity]['fields'][$node['name']] = AHQ::getProps($node);
      }
      elseif ($entity && !empty($node['af-block'])) {
        $entities[$entity]['blocks'][$node['af-block']] = AHQ::getProps($node);
      }
      elseif (!empty($node['#children'])) {
        self::parseFields($node['#children'], $entities, $entity);
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
