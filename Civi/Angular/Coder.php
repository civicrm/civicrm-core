<?php
namespace Civi\Angular;

class Coder {

  /**
   *
   * Determine whether an HTML snippet remains consistent (through an
   * decode/encode loop).
   *
   * Note: Variations in whitespace are permitted.
   *
   * @param string $html
   * @return bool
   */
  public function checkConsistentHtml($html) {
    try {
      $recodedHtml = $this->recode($html);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    $htmlSig = preg_replace('/[ \t\r\n\/]+/', '', $this->cleanup($html));
    $docSig = preg_replace('/[ \t\r\n\/]+/', '', $recodedHtml);
    if ($htmlSig !== $docSig || empty($html) != empty($htmlSig)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Parse an HTML snippet and re-encode is as HTML.
   *
   * This is useful for detecting cases where the parser or encoder
   * have quirks/bugs.
   *
   * @param string $html
   * @return string
   */
  public function recode($html) {
    $doc = \phpQuery::newDocument("$html", 'text/html');
    return $this->encode($doc);
  }

  /**
   * Encode a phpQueryObject as HTML.
   *
   * @param \phpQueryObject $doc
   * @return string
   *   HTML
   */
  public function encode($doc) {
    $doc->document->formatOutput = TRUE;
    return $this->cleanup($doc->markupOuter());
  }

  protected function cleanup($html) {
    $html = preg_replace_callback("/([\\-a-zA-Z0-9]+)=(')([^']*)(')/", [$this, 'cleanupAttribute'], $html);
    $html = preg_replace_callback('/([\-a-zA-Z0-9]+)=(")([^"]*)(")/', [$this, 'cleanupAttribute'], $html);
    return $html;
  }

  /**
   * Angular is not as strict about special characters inside html attributes as the xhtml spec.
   *
   * This unescapes everything that angular expects to be unescaped.
   *
   * @param $matches
   * @return string
   */
  protected function cleanupAttribute($matches) {
    [$full, $attr, $lquote, $value, $rquote] = $matches;

    switch ($attr) {
      case 'href':
        if (str_contains($value, '%7B%7B') && str_contains($value, '%7D%7D')) {
          $value = urldecode($value);
        }
        break;

      default:
        $value = html_entity_decode($value, ENT_NOQUOTES);
        $oppositeQuote = $lquote === '"' ? "'" : '"';
        $value = str_replace(htmlspecialchars($oppositeQuote, ENT_QUOTES), $oppositeQuote, $value);
        break;
    }

    return "$attr=$lquote$value$rquote";
  }

}
