<?php

namespace Civi\Token;

/**
 * This class is a collection of CiviMail filter functions. For example, consider this message:
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
   * @return mixed
   */
  public static function upper($value, array $filter, string $format) {
    switch ($format) {
      case 'text/plain':
        return mb_strtoupper($value);

      case 'text/html':
        return \CRM_Utils_XML::filterMarkupText((string) $value, 'mb_strtoupper');

      default:
        throw new \CRM_Core_Exception(sprintf('Filter %s does not support format %s', __FUNCTION__, $format));
    }
  }

  public static function lower($value, array $filter, string $format) {
    switch ($format) {
      case 'text/plain':
        return mb_strtolower($value);

      case 'text/html':
        return \CRM_Utils_XML::filterMarkupText((string) $value, 'mb_strtolower');

      default:
        throw new \CRM_Core_Exception(sprintf('Filter %s does not support format %s', __FUNCTION__, $format));
    }
  }

  public static function boolean($value, array $filter, string $format) {
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

  public static function default($value, array $filter, string $format) {
    if (!$value) {
      return $filter[1];
    }
    else {
      return $value;
    }
  }

}
