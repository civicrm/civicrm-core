<?php
namespace Civi\Core\Html;

use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * A set of elements selected from an HtmlDocument.
 *
 * Getters read the first element in the set; mutators apply to all of them.
 * An empty set is not an error - reading one gives NULL and mutating one does
 * nothing - because callers routinely select markup that a given partial
 * happens not to contain.
 */
class NodeSet implements \IteratorAggregate, \Countable {

  protected HtmlDocument $doc;

  /**
   * @var \DOMElement[]
   */
  protected array $nodes;

  /**
   * @param \DOMElement[] $nodes
   */
  public function __construct(HtmlDocument $doc, array $nodes) {
    $this->doc = $doc;
    $this->nodes = $nodes;
  }

  /**
   * The replacement for phpQuery's pq($element): reach a NodeSet from an
   * element alone, for code that is handed a \DOMElement and nothing else.
   */
  public static function of(\DOMElement ...$nodes): self {
    $doc = HtmlDocument::forDomDocument($nodes[0]->ownerDocument);
    return $doc->newNodeSet($nodes);
  }

  /**
   * Descendants matching a CSS selector, ex: 'af-field[name="x"]' or 'a, button'.
   */
  public function find(string $selector): self {
    $xpath = new \DOMXPath($this->doc->getDomDocument());
    $query = self::toXPath($selector);
    $found = [];
    foreach ($this->nodes as $node) {
      foreach ($xpath->query($query, $node) as $match) {
        // Selector lists can match the same element twice.
        $found[spl_object_id($match)] = $match;
      }
    }
    return $this->doc->newNodeSet(array_values($found));
  }

  /**
   * The nearest ancestor matching $selector.
   *
   * The element itself is never a candidate, so an af-fieldset can look up
   * the af-fieldset it is nested inside.
   */
  public function ancestor(string $selector): self {
    foreach ($this->nodes as $node) {
      for ($parent = $node->parentNode; $parent instanceof \DOMElement; $parent = $parent->parentNode) {
        if (self::nodeMatches($parent, $selector)) {
          return $this->doc->newNodeSet([$parent]);
        }
      }
    }
    return $this->doc->newNodeSet([]);
  }

  public function matches(string $selector): bool {
    return isset($this->nodes[0]) && self::nodeMatches($this->nodes[0], $selector);
  }

  /**
   * @return string|null
   *   NULL if the attribute is absent, which callers distinguish from ''.
   */
  public function getAttr(string $name): ?string {
    $node = $this->nodes[0] ?? NULL;
    return ($node && $node->hasAttribute($name)) ? $node->getAttribute($name) : NULL;
  }

  public function setAttr(string $name, string $value): self {
    foreach ($this->nodes as $node) {
      $node->setAttribute($name, $value);
    }
    return $this;
  }

  public function getHtml(): string {
    $node = $this->nodes[0] ?? NULL;
    if (!$node) {
      return '';
    }
    $dom = $this->doc->getDomDocument();
    $html = '';
    foreach ($node->childNodes as $child) {
      $html .= $dom->saveHTML($child);
    }
    return $html;
  }

  public function setHtml(string $markup): self {
    foreach ($this->nodes as $node) {
      while ($node->firstChild) {
        $node->removeChild($node->firstChild);
      }
      foreach ($this->doc->importMarkup($markup) as $child) {
        $node->appendChild($child);
      }
    }
    return $this;
  }

  public function append(string|self $content): self {
    $source = is_string($content) ? $this->doc->importMarkup($content) : $content->nodes;
    foreach ($this->nodes as $index => $node) {
      foreach ($source as $child) {
        // The first target adopts the nodes; any others get copies.
        $node->appendChild($index ? $child->cloneNode(TRUE) : $child);
      }
    }
    return $this;
  }

  /**
   * Move this set to sit immediately after the first element matching $selector.
   */
  public function insertAfter(string $selector): self {
    $target = $this->doc->find($selector)->get(0);
    if ($target && $target->parentNode) {
      foreach ($this->nodes as $node) {
        $target->parentNode->insertBefore($node, $target->nextSibling);
      }
    }
    return $this;
  }

  /**
   * @param callable $callback
   *   function(\DOMElement $element): void
   */
  public function each(callable $callback): self {
    foreach ($this->nodes as $node) {
      $callback($node);
    }
    return $this;
  }

  public function get(int $index): ?\DOMElement {
    return $this->nodes[$index] ?? NULL;
  }

  public function getIterator(): \ArrayIterator {
    return new \ArrayIterator($this->nodes);
  }

  public function count(): int {
    return count($this->nodes);
  }

  private static function nodeMatches(\DOMElement $node, string $selector): bool {
    $xpath = new \DOMXPath($node->ownerDocument);
    return $xpath->query(self::toXPath($selector, 'self::'), $node)->length > 0;
  }

  private static function toXPath(string $selector, string $prefix = 'descendant-or-self::'): string {
    static $converter;
    $converter ??= new CssSelectorConverter();
    return $converter->toXPath($selector, $prefix);
  }

}
