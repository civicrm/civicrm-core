<?php
namespace Civi\Api4\Utils;

/**
 * Class AfformFormatTrait
 * @package Civi\Api4\Utils
 *
 * @method $this setLayoutFormat(string $layoutFormat)
 * @method string getLayoutFormat()
 */
trait AfformFormatTrait {

  /**
   * @var string
   *   Either 'array' or 'html'.
   */
  protected $layoutFormat = 'array';

  /**
   * @param string $html
   * @return mixed
   * @throws \API_Exception
   */
  protected function convertHtmlToOutput($html) {
    switch ($this->layoutFormat) {
      case 'html':
        return $html;

      case 'array':
      case NULL:
        $converter = new \CRM_Afform_ArrayHtml();
        return $converter->convertHtmlToArray($html);

      default:
        throw new \API_Exception("Requested format is unrecognized");
    }
  }

  /**
   * @param mixed $mixed
   * @return string
   * @throws \API_Exception
   */
  protected function convertInputToHtml($mixed) {
    switch ($this->layoutFormat) {
      case 'html':
        return $mixed;

      case 'array':
      case NULL:
        $converter = new \CRM_Afform_ArrayHtml();
        return $converter->convertArrayToHtml($mixed);

      default:
        throw new \API_Exception("Requested format is unrecognized");
    }
  }

}
