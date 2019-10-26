<?php

namespace Civi\Afform;

/**
 * AHQ: ArrayHtml Query
 *
 * These are helper functions for searching/digesting a form presented in
 * in ArrayHtml format (shallow or deep).
 */
class AHQ {

  /**
   * Returns all tags with a certain tag name, e.g. 'af-entity'
   *
   * @param array $element
   * @param string $tagName
   * @return array
   */
  public static function getTags($element, $tagName) {
    if ($element === []) {
      return [];
    }
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
