<?php

namespace Civi\Afform;

class Utils {

  /**
   * Gets entity metadata and all fields from the form
   *
   * @param array $layout
   * @return array
   */
  public static function getEntities($layout) {
    $entities = array_column(self::getTags($layout, 'af-entity'), NULL, 'name');
    self::getFields($layout, $entities);
    return $entities;
  }

  /**
   * Returns all tags with a certain tag name, e.g. 'af-entity'
   *
   * @param array $collection
   * @param string $tagName
   * @return array
   */
  public static function getTags($collection, $tagName) {
    $results = [];
    if ($collection['#tag'] == $tagName) {
      $results[] = self::getProps($collection);
    }
    foreach ($collection['#children'] ?? [] as $child) {
      $results = array_merge($results, self::getTags($child, $tagName));
    }
    return $results;
  }

  /**
   * @param array $layout
   * @param array $entities
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
   * @param array $collection
   * @return array
   */
  public static function getProps($collection) {
    return array_filter($collection, function($key) {
      return substr($key, 0, 1) !== '#';
    }, ARRAY_FILTER_USE_KEY);
  }

}
