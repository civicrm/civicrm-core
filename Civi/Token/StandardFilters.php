<?php

namespace Civi\Token;

/**
 * This class is a collection of token filter functions. For example, consider this message:
 *
 * "Hello {contact.first_name|upper}!"
 *
 * The "upper" filter corresponds to method `upper()`.
 *
 * All public methods should have the same signature (mixed $value, array $filter, string $format).
 */
class StandardFilters {

  /**
   * Convert to uppercase.
   *
   * @param mixed $value
   * @param array $filter
   *   The list of filter criteria, as requested in this template.
   * @param string $format
   *   The format of the active template ('text/plain' or 'text/html').
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   * @noinspection PhpUnusedParameterInspection
   */
  public static function upper($value, array $filter, string $format): string {
    switch ($format) {
      case 'text/plain':
        return mb_strtoupper($value);

      case 'text/html':
        return \CRM_Utils_XML::filterMarkupText((string) $value, 'mb_strtoupper');

      default:
        throw new \CRM_Core_Exception(sprintf('Filter %s does not support format %s', __FUNCTION__, $format));
    }
  }

  /**
   * Convert to lowercase.
   *
   * @param string $value
   * @param array $filter
   * @param string $format
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   * @noinspection PhpUnusedParameterInspection
   */
  public static function lower($value, array $filter, string $format): string {
    switch ($format) {
      case 'text/plain':
        return mb_strtolower($value);

      case 'text/html':
        return \CRM_Utils_XML::filterMarkupText((string) $value, 'mb_strtolower');

      default:
        throw new \CRM_Core_Exception(sprintf('Filter %s does not support format %s', __FUNCTION__, $format));
    }
  }

  /**
   * Convert to title case (each word is capitalized).
   *
   * The use of the verb 'capitalize' is to align with smarty
   * @see https://www.smarty.net/docsv2/en/language.modifiers.tpl#language.modifier.capitalize
   *
   * @param string $value
   * @param array $filter
   * @param string $format
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   * @noinspection PhpUnusedParameterInspection
   */
  public static function capitalize($value, array $filter, string $format): string {
    // Note that Smarty says capitalize equates to ucwords.
    // ucwords is not multibyte proof and this alternative messes a bit with
    // intentionally capitalization (DVD => Dvd) but the trade off seems OK.
    // https://gist.github.com/burakerdem/1007450
    switch ($format) {
      case 'text/plain':
        return mb_convert_case($value, MB_CASE_TITLE);

      case 'text/html':
        return \CRM_Utils_XML::filterMarkupText((string) $value, function($s) {
          return mb_convert_case($s, MB_CASE_TITLE);
        });

      default:
        throw new \CRM_Core_Exception(sprintf('Filter %s does not support format %s', __FUNCTION__, $format));
    }
  }

  /**
   * Convert to boolean.
   *
   * @param string $value
   * @param array $filter
   * @param string $format
   *
   * @return int
   * @noinspection PhpUnusedParameterInspection
   */
  public static function boolean($value, array $filter, string $format): int {
    return (int) ((bool) $value);
  }

  public static function crmDate($value, array $filter, string $format) {
    if ($value instanceof \DateTime) {
      // @todo cludgey.
      require_once 'CRM/Core/Smarty/plugins/modifier.crmDate.php';
      return \smarty_modifier_crmDate($value->format('Y-m-d H:i:s'), $filter[1] ?? NULL);
    }
    if ($value === '') {
      return $value;
    }

    // I don't think this makes sense, but it matches the pre-refactor
    // behavior (where the old `switch()` block would fall-through to `case "default"`.
    return static::default($value, $filter, $format);
  }

  /**
   * Return value, falling back to default.
   *
   * @param $value
   * @param array $filter
   * @param string $format
   *
   * @return mixed
   * @noinspection PhpUnusedParameterInspection
   */
  public static function default($value, array $filter, string $format) {
    if (!$value) {
      return $filter[1];
    }
    return $value;
  }

}
