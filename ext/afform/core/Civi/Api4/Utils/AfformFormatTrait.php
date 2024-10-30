<?php
namespace Civi\Api4\Utils;

/**
 * Class AfformFormatTrait
 * @package Civi\Api4\Utils
 *
 * @method $this setLayoutFormat(string $layoutFormat)
 * @method string getLayoutFormat()
 * @method $this setFormatWhitespace(string $formatWhitespace)
 * @method string getFormatWhitespace()
 */
trait AfformFormatTrait {

  /**
   * Controls the return format of the "layout" property
   *  - "html" will return layout html as-is.
   *  - "shallow" will convert most html to an array, but leave tag attributes and af-markup containers alone.
   *  - "deep" will attempt to convert all html to an array, including tag attributes.
   *
   * @var string
   * @options html,shallow,deep
   */
  protected $layoutFormat = 'deep';

  /**
   * Optionally manage whitespace for the "layout" property
   *
   * This option will strip whitepace from the returned layout array for `get` actions,
   * and will auto-indent the aff.html for `save` actions.
   *
   * Note: Has no effect on `get` with "html" return format, which returns html as-is.
   *
   * @var bool
   */
  protected $formatWhitespace = FALSE;

  /**
   * @param string $html
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  protected function convertHtmlToOutput($html) {
    if ($this->layoutFormat === 'html') {
      return $html;
    }
    $converter = new \CRM_Afform_ArrayHtml($this->layoutFormat !== 'shallow', $this->formatWhitespace);
    return $converter->convertHtmlToArray($html);
  }

  /**
   * @param mixed $mixed
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function convertInputToHtml($mixed) {
    if (is_string($mixed)) {
      return $mixed;
    }
    $converter = new \CRM_Afform_ArrayHtml($this->layoutFormat !== 'shallow', $this->formatWhitespace);
    return $converter->convertTreeToHtml($mixed);
  }

}
