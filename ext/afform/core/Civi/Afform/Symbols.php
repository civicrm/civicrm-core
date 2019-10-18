<?php
namespace Civi\Afform;

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
        $classes = explode(' ', $node->nodeValue);
        foreach ($classes as $class) {
          self::increment($this->classes, $class);
        }
      }
    }
  }

  private static function increment(&$arr, $key) {
    if (!isset($arr[$key])) {
      $arr[$key] = 0;
    }
    $arr[$key]++;
  }

}
