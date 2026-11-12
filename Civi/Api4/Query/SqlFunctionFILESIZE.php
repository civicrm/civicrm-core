<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Query;

/**
 * Sql function
 *
 * Formats a size in the largest whole binary unit, e.g. "850 MiB", "13 GiB".
 *
 * The sql renders the number unchanged, so sorting and filtering stay numeric;
 * only the output is formatted.
 */
class SqlFunctionFILESIZE extends SqlFunction {

  protected static $category = self::CATEGORY_MATH;

  protected static $dataType = 'String';

  /**
   * Input units accepted by the first param, in increasing powers of 1024.
   */
  private const INPUT_UNITS = ['B', 'KiB', 'MiB', 'GiB'];

  protected static function params(): array {
    return [
      [
        'label' => ts('Unit'),
        'flag_before' => [
          'B' => ts('Bytes'),
          'KiB' => ts('KiB'),
          'MiB' => ts('MiB'),
          'GiB' => ts('GiB'),
        ],
        'max_expr' => 0,
        'optional' => FALSE,
      ],
      [
        'label' => ts('Size'),
        'must_be' => ['SqlField', 'SqlFunction', 'SqlEquation', 'SqlNumber'],
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('File size');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('A size in bytes, KiB, MiB or GiB, shown in the largest whole unit, e.g. 13 GiB.');
  }

  /**
   * Render only the size expression: the unit param is for formatOutputValue, not sql.
   */
  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    $output = '(' . $this->getArgs()[1]['expr'][0]->render($query) . ')';
    return $output . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
  }

  /**
   * @inheritDoc
   */
  public function formatOutputValue(?string &$dataType, array &$values, string $key, ?Api4Query $query = NULL): void {
    $dataType = 'String';
    if (isset($values[$key]) && is_numeric($values[$key])) {
      $power = array_search($this->getArgs()[0]['prefix'][0], self::INPUT_UNITS);
      $values[$key] = self::format((float) $values[$key] * (1024 ** $power));
    }
  }

  /**
   * Format a number of bytes, with one decimal place below 10 (as `ls -h` does).
   *
   * @param float $bytes
   * @return string
   */
  public static function format(float $bytes): string {
    $size = abs($bytes);
    $power = 0;
    // Compare at the precision shown (whole numbers, near 1024), so 1023.6 KiB moves up to 1 MiB rather than showing as 1,024 KiB.
    while (round($size) >= 1024 && $power < 5) {
      $size /= 1024;
      $power++;
    }
    $places = ($power && round($size, 1) < 10) ? 1 : 0;
    $number = \Civi::format()->number(round($size, $places) * ($bytes < 0 ? -1 : 1), NULL, [
      \NumberFormatter::MIN_FRACTION_DIGITS => 0,
      \NumberFormatter::MAX_FRACTION_DIGITS => $places,
    ]);
    return match ($power) {
      0 => ts('%1 B', [1 => $number]),
      1 => ts('%1 KiB', [1 => $number]),
      2 => ts('%1 MiB', [1 => $number]),
      3 => ts('%1 GiB', [1 => $number]),
      4 => ts('%1 TiB', [1 => $number]),
      5 => ts('%1 PiB', [1 => $number]),
    };
  }

}
