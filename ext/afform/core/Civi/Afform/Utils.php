<?php

namespace Civi\Afform;

class Utils {

  /**
   * Gets entity metadata and all fields from the form
   *
   * @param array $layout
   *   The root element of the layout, in shallow/deep format.
   * @return array
   *   A list of entities, keyed by named.
   *   Ex: $entities['spouse']['type'] = 'Contact';
   */
  public static function getEntities($layout) {
    $entities = array_column(self::getTags($layout, 'af-entity'), NULL, 'name');
    self::getFields($layout, $entities);
    return $entities;
  }

  /**
   * Returns all tags with a certain tag name, e.g. 'af-entity'
   *
   * @param array $element
   * @param string $tagName
   * @return array
   */
  public static function getTags($element, $tagName) {
    $results = [];
    if ($element['#tag'] == $tagName) {
      $results[] = self::getProps($element);
    }
    foreach ($element['#children'] ?? [] as $child) {
      $results = array_merge($results, self::getTags($child, $tagName));
    }
    return $results;
  }

  /**
   * @param array $layout
   *   The root element of the layout, in shallow/deep format.
   * @param array $entities
   *   A list of entities, keyed by named.
   *   This will be updated to include 'fields'.
   *   Ex: $entities['spouse']['type'] = 'Contact';
   */
  protected static function getFields($layout, &$entities) {
    foreach ($layout['#children'] as $child) {
      if ($child['#tag'] == 'af-fieldset' && !empty($child['#children'])) {
        $entities[$child['model']]['fields'] = array_merge($entities[$child['model']]['fields'] ?? [], self::getTags($child, 'af-field'));
      }
      elseif (!empty($child['#children'])) {
        self::getFields($child['#children'], $entities);
      }
    }
  }

  /**
   * Returns all the real properties of a collection,
   * filtering out any array keys that start with a hashtag
   *
   * @param array $element
   * @return array
   */
  public static function getProps($element) {
    return array_filter($element, function($key) {
      return substr($key, 0, 1) !== '#';
    }, ARRAY_FILTER_USE_KEY);
  }

}
