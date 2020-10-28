<?php
namespace Civi\Afform;

/**
 * Class Symbols
 * @package Civi\Afform
 *
 * This class repesents a list of key symbols used by an
 * HTML document, such as element (tag) names, attribute
 * names, and CSS class names.
 */
class Symbols {

  /**
   * @var array
   *   Array(string $element => int $count).
   */
  public $elements = [];

  /**
   * @var array
   *   Array(string $class => int $count).
   */
  public $classes = [];

  /**
   * @var array
   *   Array(string $attr => int $count).
   */
  public $attributes = [];

  /**
   * @param string $html
   * @return static
   */
  public static function scan($html) {
    $symbols = new static();
    $doc = new \DOMDocumentWrapper($html, 'text/html');
    $symbols->scanNode($doc->root);
    return $symbols;
  }

  protected function scanNode(\DOMNode $node) {
    if ($node instanceof \DOMElement) {

      self::increment($this->elements, $node->tagName);

      foreach ($node->childNodes as $childNode) {
        $this->scanNode($childNode);
      }

      foreach ($node->attributes as $attribute) {
        $this->scanNode($attribute);
      }
    }

    elseif ($node instanceof \DOMAttr) {
      self::increment($this->attributes, $node->nodeName);

      if ($node->nodeName === 'class') {
        $classes = $this->parseClasses($node->nodeValue);
        foreach ($classes as $class) {
          self::increment($this->classes, $class);
        }
      }
    }
  }

  /**
   * @param string $expr
   *   Ex: 'crm-icon fa-mail'
   * @return array
   *   Ex: ['crm-icon', 'fa-mail']
   */
  protected function parseClasses($expr) {
    if ($expr === '' || $expr === NULL || $expr === FALSE) {
      return [];
    }
    if (strpos($expr, '{{') === FALSE) {
      return explode(' ', $expr);
    }
    if (preg_match_all(';([a-zA-Z\-_]+|\{\{.*\}\}) ;U', "$expr ", $m)) {
      return $m[1];
    }
    error_log("Failed to parse CSS classes: $expr");
    return [];
  }

  private static function increment(&$arr, $key) {
    if (!isset($arr[$key])) {
      $arr[$key] = 0;
    }
    $arr[$key]++;
  }

}
