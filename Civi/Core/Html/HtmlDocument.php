<?php
namespace Civi\Core\Html;

/**
 * An HTML document or fragment, parsed into a DOMDocument.
 *
 * Angular partials and Afform markup arrive as bare fragments. Parsing one
 * means wrapping it in enough scaffolding for DOMDocument to accept it, and
 * serializing means taking that scaffolding back off, so the two are defined
 * together here.
 */
class HtmlDocument {

  /**
   * Fragments are wrapped in this doctype before parsing.
   *
   * It decides how DOMDocument escapes attribute values, so partials are
   * round-tripped against it by \Civi\Angular\PartialSyntaxTest.
   */
  private const FRAGMENT_DOCTYPE = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';

  /**
   * Lets pq() find the wrapper for an element's owning document.
   *
   * @var \WeakMap<\DOMDocument, self>
   */
  private static \WeakMap $registry;

  private \DOMDocument $dom;

  private bool $isFragment;

  public function __construct(\DOMDocument $dom, bool $isFragment) {
    $this->dom = $dom;
    $this->isFragment = $isFragment;
    self::$registry ??= new \WeakMap();
    self::$registry[$dom] = $this;
  }

  public static function forDomDocument(\DOMDocument $dom): self {
    return self::$registry[$dom] ?? new self($dom, FALSE);
  }

  /**
   * @param string $html
   *   Either a whole document or a bare fragment, ex: '<div af-fieldset></div>'.
   * @throws \CRM_Core_Exception
   */
  public static function parse(string $html): static {
    $isFragment = stripos($html, '<html') === FALSE && stripos($html, '<!doctype') === FALSE;
    $source = $isFragment
      ? self::FRAGMENT_DOCTYPE . '<html><head>'
        . '<meta http-equiv="Content-Type" content="text/html;charset=utf-8">'
        . '</head><body>' . $html . '</body></html>'
      : $html;

    $dom = new \DOMDocument('1.0', 'UTF-8');
    if (!@$dom->loadHTML($source, LIBXML_NOERROR | LIBXML_NOWARNING)) {
      throw new \CRM_Core_Exception('Failed to parse HTML document');
    }
    return new static($dom, $isFragment);
  }

  public function getDomDocument(): \DOMDocument {
    return $this->dom;
  }

  /**
   * The node that selectors and appends are relative to.
   */
  public function getRoot(): \DOMElement {
    return $this->dom->getElementsByTagName('body')->item(0) ?: $this->dom->documentElement;
  }

  public function nodes(): NodeSet {
    return $this->newNodeSet([$this->getRoot()]);
  }

  /**
   * Every NodeSet this document hands out is built here, so a subclass can
   * substitute its own.
   *
   * @param \DOMElement[] $nodes
   */
  public function newNodeSet(array $nodes): NodeSet {
    return new NodeSet($this, $nodes);
  }

  public function find(string $selector): NodeSet {
    return $this->nodes()->find($selector);
  }

  public function append(string|NodeSet $content): NodeSet {
    return $this->nodes()->append($content);
  }

  /**
   * Build a detached element owned by this document.
   *
   * Ex: createNode('<af-field></af-field>')
   */
  public function createNode(string $markup): NodeSet {
    $nodes = array_filter($this->importMarkup($markup), fn($node) => $node instanceof \DOMElement);
    return $this->newNodeSet(array_values($nodes));
  }

  /**
   * Parse $markup and return detached nodes owned by this document.
   *
   * Unlike createNode() this keeps text nodes, so it can carry a translated
   * string that may or may not contain any markup.
   *
   * @return \DOMNode[]
   * @throws \CRM_Core_Exception
   */
  public function importMarkup(string $markup): array {
    $nodes = [];
    foreach (self::parse($markup)->getRoot()->childNodes as $child) {
      $nodes[] = $this->dom->importNode($child, TRUE);
    }
    return $nodes;
  }

  /**
   * Serialize back to the shape this was parsed from: a fragment comes back
   * without the scaffolding that was added to parse it.
   */
  public function saveHtml(): string {
    $markup = $this->dom->saveHTML();
    $start = $this->isFragment ? strpos($markup, '<body>') : FALSE;
    if ($start === FALSE) {
      return $markup;
    }
    $start += strlen('<body>');
    $end = strrpos($markup, '</body>');
    return substr($markup, $start, $end - $start);
  }

}
