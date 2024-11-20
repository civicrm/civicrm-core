<?php

/**
 * Class CRM_Afform_ArrayHtml
 *
 * FIXME This a quick-and-dirty array<=>html mapping.
 * FIXME: Comment mapping.
 */
class CRM_Afform_ArrayHtml {

  const DEFAULT_TAG = 'div';

  private $indent = -1;

  /**
   * This is a minimalist/temporary placeholder for a schema definition.
   * FIXME: It shouldn't be here or look like this.
   *
   * @var array
   *   Ex: $protoSchema['my-tag']['my-attr'] = 'text';
   */
  private $protoSchema = [
    '*' => [
      '*' => 'text',
      'af-fieldset' => 'text',
      'data' => 'js',
      'actions' => 'js',
    ],
    'af-entity' => [
      '#selfClose' => TRUE,
      'name' => 'text',
      'type' => 'text',
      'security' => 'text',
      'actions' => 'js',
    ],
    'af-field' => [
      '#selfClose' => TRUE,
      'name' => 'text',
      'defn' => 'js',
    ],
    'area' => ['#selfClose' => TRUE],
    'base' => ['#selfClose' => TRUE],
    'br' => ['#selfClose' => TRUE],
    'col' => ['#selfClose' => TRUE],
    'command' => ['#selfClose' => TRUE],
    'embed' => ['#selfClose' => TRUE],
    'hr' => ['#selfClose' => TRUE],
    'iframe' => ['#selfClose' => TRUE],
    'img' => ['#selfClose' => TRUE],
    'input' => ['#selfClose' => TRUE],
    'keygen' => ['#selfClose' => TRUE],
    'link' => ['#selfClose' => TRUE],
    'meta' => ['#selfClose' => TRUE],
    'param' => ['#selfClose' => TRUE],
    'source' => ['#selfClose' => TRUE],
    'track' => ['#selfClose' => TRUE],
    'wbr' => ['#selfClose' => TRUE],
  ];

  /**
   * @var bool
   */
  protected $deepCoding;
  /**
   * @var bool
   */
  protected $formatWhitespace;

  /**
   * CRM_Afform_ArrayHtml constructor.
   * @param bool $deepCoding
   * @param bool $formatWhitespace
   */
  public function __construct($deepCoding = TRUE, $formatWhitespace = FALSE) {
    $this->deepCoding = $deepCoding;
    $this->formatWhitespace = $formatWhitespace;
  }

  /**
   * @param array $array
   *   Ex: ['#tag' => 'div', 'class' => 'greeting', '#children' => ['Hello world']]
   * @return string
   *   Ex: '<div class="greeting">Hello world</div>'
   */
  public function convertArrayToHtml(array $array) {
    if ($array === []) {
      return '';
    }
    $indent = $this->formatWhitespace ? str_repeat('  ', $this->indent) : '';
    $end = $this->formatWhitespace ? "\n" : '';

    if (isset($array['#comment'])) {
      if (strpos($array['#comment'], '-->')) {
        Civi::log()->warning('Afform: Cannot store comment with text "-->". Munging.');
        $array['#comment'] = str_replace('-->', '-- >', $array['#comment']);
      }
      return $indent . sprintf('<!--%s-->', $array['#comment']) . $end;
    }

    if (isset($array['#text'])) {
      return $array['#text'];
    }

    $tag = empty($array['#tag']) ? self::DEFAULT_TAG : $array['#tag'];
    unset($array['#tag']);
    $children = empty($array['#children']) ? [] : $array['#children'];
    unset($array['#children']);

    $buf = $indent . '<' . $tag;
    foreach ($array as $attrName => $attrValue) {
      if ($attrName[0] === '#') {
        continue;
      }
      if (!preg_match('/^[a-zA-Z0-9\-]+$/', $attrName)) {
        throw new \RuntimeException("Malformed HTML attribute $attrName → $attrValue");
      }

      $type = $this->pickAttrType($tag, $attrName);
      $encodedValue = $this->encodeAttrValue($type, $attrValue);
      if ($encodedValue !== NULL) {
        // ENT_COMPAT: Will convert double-quotes and leave single-quotes alone.
        $buf .= sprintf(" %s=\"%s\"", $attrName, htmlentities($encodedValue, ENT_COMPAT | ENT_XHTML));
      }
      else {
        Civi::log()->warning('Afform: Cannot serialize attribute {attrName}', [
          'attrName' => $attrName,
        ]);
      }
    }

    if (isset($array['#markup']) && (!$this->formatWhitespace || strpos($array['#markup'], '<') === FALSE)) {
      $buf .= '>' . $array['#markup'] . '</' . $tag . '>';
    }
    elseif (isset($array['#markup'])) {
      $indent2 = str_repeat('  ', $this->indent + 1);
      $buf .= '>' . $end . $indent2 . str_replace("\n<", "\n$indent2<", $array['#markup']) . $end . $indent . '</' . $tag . '>';
    }
    elseif (empty($children) && $this->isSelfClosing($tag)) {
      $buf .= ' />';
    }
    else {
      $contents = $this->convertArraysToHtml($children);
      // No indentation if contents are only text
      if (!$this->formatWhitespace || strpos($contents, '<') === FALSE) {
        $buf .= '>' . $contents;
      }
      else {
        $buf .= '>' . $end;
        $buf .= $contents . $indent;
      }
      $buf .= '</' . $tag . '>';
    }
    return $buf . $end;
  }

  /**
   * Converts a subset of items into html markup
   *
   * @param array $children
   * @return string html
   */
  public function convertArraysToHtml($children) {
    $buf = '';
    $this->indent++;

    foreach ($children as $child) {
      if (is_string($child)) {
        $buf .= htmlentities($child);
      }
      elseif (is_array($child)) {
        $buf .= $this->convertArrayToHtml($child);
      }
    }

    $this->indent--;
    return $buf;
  }

  /**
   * Converts a full array of items into html markup
   *
   * @param array $tree
   * @return string html
   */
  public function convertTreeToHtml($tree) {
    $this->indent = -1;
    return $this->replaceUnicodeChars($this->convertArraysToHtml($tree));
  }

  /**
   * @param string $html
   *   Ex: '<div class="greeting">Hello world</div>'
   * @return array
   *   Ex: ['#tag' => 'div', 'class' => 'greeting', '#children' => ['Hello world']]
   */
  public function convertHtmlToArray($html) {
    if ($html === '') {
      return [];
    }

    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = !$this->formatWhitespace;
    // Angular/js isn't fussy about arbitrary tags but libxml is for html data, so ignore errors.
    // See also Civi\Afform\Symbols::scan()
    $oldErrorStatus = libxml_use_internal_errors(TRUE);
    $doc->loadHTML("<?xml encoding=\"utf-8\" ?><html><body>$html</body></html>");
    libxml_clear_errors();
    libxml_use_internal_errors($oldErrorStatus);

    // FIXME: Validate expected number of child nodes

    foreach ($doc->childNodes as $htmlNode) {
      if ($htmlNode instanceof DOMElement && $htmlNode->tagName === 'html') {
        return $this->convertNodesToArray($htmlNode->firstChild->childNodes);
      }
    }

    return NULL;
  }

  /**
   * @param \DOMNode $node
   * @return array|string
   */
  public function convertNodeToArray($node) {
    if ($node instanceof DOMElement) {
      $arr = ['#tag' => $node->tagName];
      foreach ($node->attributes as $attribute) {
        $txt = $attribute->textContent;
        $type = $this->pickAttrType($node->tagName, $attribute->name);
        $arr[$attribute->name] = $this->decodeAttrValue($type, $txt);
      }
      if ($node->childNodes->length > 0) {
        // In shallow mode, return markup as-is if node isn't supported by the gui editor
        if (!$this->deepCoding && !$this->isNodeEditable($arr)) {
          $arr['#markup'] = '';
          foreach ($node->childNodes as $child) {
            $arr['#markup'] .= $this->replaceUnicodeChars($child->ownerDocument->saveXML($child));
          }
        }
        else {
          $arr['#children'] = $this->convertNodesToArray($node->childNodes);
        }
      }
      // Empty containers should still get a #children attribute
      elseif (in_array($node->tagName, ['div', 'fieldset'], TRUE)) {
        $arr['#children'] = [];
      }
      return $arr;
    }
    elseif ($node instanceof DOMText) {
      return ['#text' => $this->replaceUnicodeChars($node->textContent)];
    }
    elseif ($node instanceof DOMComment) {
      return ['#comment' => $node->nodeValue];
    }
    else {
      throw new \RuntimeException("Unrecognized DOM node");
    }
  }

  /**
   * @param array|DOMNodeList $nodes
   *   List of DOMNodes
   * @return array
   */
  protected function convertNodesToArray($nodes) {
    $children = [];
    foreach ($nodes as $childNode) {
      $childArray = $this->convertNodeToArray($childNode);
      // Remove extra whitespace
      if ($this->formatWhitespace && isset($childArray['#text'])) {
        $childArray['#text'] = trim($childArray['#text']);
      }
      if (!isset($childArray['#text']) || strlen($childArray['#text'])) {
        $children[] = $childArray;
      }
    }
    return $children;
  }

  /**
   * @param string $tag
   *   Ex: 'img', 'div'
   * @return bool
   *   TRUE if the tag should look like '<img/>'.
   *   FALSE if the tag should look like '<div></div>'.
   */
  protected function isSelfClosing($tag) {
    return $this->protoSchema[$tag]['#selfClose'] ?? FALSE;
  }

  /**
   * Determine the type of data that is stored in an attribute.
   *
   * @param string $tag
   *   Ex: 'af-entity'
   * @param string $attrName
   *   Ex: 'label'
   * @return string
   *   Ex: 'text' or 'js'
   */
  protected function pickAttrType($tag, $attrName) {
    if (!$this->deepCoding) {
      return 'text';
    }

    return $this->protoSchema[$tag][$attrName] ?? $this->protoSchema['*'][$attrName] ?? $this->protoSchema['*']['*'];
  }

  /**
   * Given an array (deep) representation of an attribute, determine the
   * attribute's content.
   *
   * @param string $type
   *   Ex A: 'text'
   *   Ex B: 'js'
   * @param mixed $mixedAttrValue
   *   The mixed (string/array/int) representation of the value in deep format
   *   Ex A: 'hello'
   *   Ex B: ['hello' => 123]
   * @return string
   *   An equivalent HTML attribute content (text).
   *   Ex A: 'hello'
   *   Ex B: '{hello: 123}'
   */
  protected function encodeAttrValue($type, $mixedAttrValue) {
    switch ($type) {
      case 'text':
        return $mixedAttrValue;

      case 'js':
        $v = CRM_Utils_JS::writeObject($mixedAttrValue, TRUE);
        return $v;

      default:
        return NULL;
    }
  }

  /**
   * Given a string representation of an attribute value, determine the
   * equivalent array (deep) representation.
   *
   * @param string $type
   *   Ex A: 'text'
   *   Ex B: 'js'
   * @param string $txtAttrValue
   *   The textual representation of the value from HTML notation.
   *   Ex A: 'hello'
   *   Ex B: '{hello: 123}'
   * @return mixed
   *   The mixed (string/array/int) rerepresentation of the value in deep format
   *   Ex A: 'hello'
   *   Ex B: ['hello' => 123]
   */
  protected function decodeAttrValue($type, $txtAttrValue) {
    if ($type == 'js') {
      $attrValue = CRM_Utils_JS::decode($txtAttrValue);
      return $attrValue;
    }
    else {
      $attrValue = $txtAttrValue;
      return $attrValue;
    }
  }

  /**
   * Convert non-breaking space character to html notation.
   *
   * Makes html files easier to read.
   *
   * Note: This function does NOT convert all html entities (< to &lt;, etc.)
   * as the input string is assumed to already be valid markup.
   *
   * @param string $markup - some html
   * @return string
   */
  public function replaceUnicodeChars($markup) {
    $replace = [
      ["\xc2\xa0", '&nbsp;'],
    ];
    return str_replace(array_column($replace, 0), array_column($replace, 1), $markup);
  }

  /**
   * Determine if a node is recognized by the gui editor.
   *
   * @param array $item
   * @return bool
   */
  public function isNodeEditable(array $item) {
    if ($item['#tag'] === 'af-field' || $item['#tag'] === 'af-form' || isset($item['af-fieldset']) || isset($item['af-join'])) {
      return TRUE;
    }
    $editableClasses = ['af-container', 'af-text', 'af-button'];
    $classes = explode(' ', $item['class'] ?? '');
    return (bool) array_intersect($editableClasses, $classes);
  }

}
