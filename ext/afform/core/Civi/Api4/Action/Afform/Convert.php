<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * Convert afform layouts between different representations, e.g. from
 * a deep array to HTML.
 *
 * @method setLayout(mixed $layout)
 * @method getLayout(): mixed
 * @method setFrom(string $layoutFormat)
 * @method getFrom(): string
 * @method setTo(string $layoutFormat)
 * @method getTo(): string
 * @method setFormatWhitespace(string $layoutFormat)
 * @method getFormatWhitespace(): string
 *
 * @package Civi\Api4\Action\Afform
 */
class Convert extends AbstractAction {

  /**
   * @var string|array
   */
  protected $layout;

  /**
   * How is the input `$layout` formatted?
   *
   * @var string
   * @options html,shallow,deep
   */
  protected $from = NULL;

  /**
   * How should the `$layout` be returned?
   *
   * @var string
   * @options html,shallow,deep
   */
  protected $to = NULL;

  /**
   * Normalize whitespace?
   *
   * @var bool
   */
  protected $formatWhitespace = FALSE;

  public function _run(Result $result) {
    // Normalize to HTML
    if ($this->from === 'html') {
      $interimHtml = $this->layout;
    }
    else {
      $converter = new \CRM_Afform_ArrayHtml($this->from !== 'shallow', $this->formatWhitespace);
      $interimHtml = $converter->convertTreeToHtml($this->layout);
    }

    // And go to preferred format
    if ($this->to === 'html') {
      $final = $interimHtml;
    }
    else {
      $converter = new \CRM_Afform_ArrayHtml($this->to !== 'shallow', $this->formatWhitespace);
      $final = $converter->convertHtmlToArray($interimHtml);
    }

    $result[] = [
      'layout' => $final,
    ];
  }

}
