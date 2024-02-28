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

/**
 *
 * @deprecated since 5.69 will be removed around 5.79
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Form_Date {

  /**
   * Various Date Formats.
   *
   * @deprecated since 5.69 will be removed around 5.79
   */
  const DATE_yyyy_mm_dd = 1, DATE_mm_dd_yy = 2, DATE_mm_dd_yyyy = 4, DATE_Month_dd_yyyy = 8, DATE_dd_mon_yy = 16, DATE_dd_mm_yyyy = 32;

  /**
   * Build the date-format form.
   *
   * @deprecated since 5.69 will be removed around 5.79
   *
   * @param CRM_Core_Form $form
   *   The form object that we are operating on.
   */
  public static function buildAllowedDateFormats(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('function & entire class will be removed');
    $dateOptions = [];

    if (CRM_Utils_System::getClassName($form) == 'CRM_Activity_Import_Form_DataSource') {
      $dateText = ts('yyyy-mm-dd OR yyyy-mm-dd HH:mm OR yyyymmdd OR yyyymmdd HH:mm (1998-12-25 OR 1998-12-25 15:33 OR 19981225 OR 19981225 10:30 OR ( 2008-9-1 OR 2008-9-1 15:33 OR 20080901 15:33)');
    }
    else {
      $dateText = ts('yyyy-mm-dd OR yyyymmdd (1998-12-25 OR 19981225) OR (2008-9-1 OR 20080901)');
    }

    $form->addRadio('dateFormats', ts('Date Format'), [
      self::DATE_yyyy_mm_dd => $dateText,
      self::DATE_mm_dd_yy => ts('mm/dd/yy OR mm-dd-yy (12/25/98 OR 12-25-98) OR (9/1/08 OR 9-1-08)'),
      self::DATE_mm_dd_yyyy => ts('mm/dd/yyyy OR mm-dd-yyyy (12/25/1998 OR 12-25-1998) OR (9/1/2008 OR 9-1-2008)'),
      self::DATE_Month_dd_yyyy => ts('Month dd, yyyy (December 12, 1998)'),
      self::DATE_dd_mon_yy => ts('dd-mon-yy OR dd/mm/yy (25-Dec-98 OR 25/12/98)'),
      self::DATE_dd_mm_yyyy => ts('dd/mm/yyyy (25/12/1998) OR (1/9/2008)'),
    ], [], '<br/>');
    $form->setDefaults(['dateFormats' => self::DATE_yyyy_mm_dd]);
  }

  /**
   * Build the date range array that will provide the form option values.
   *
   * @deprecated since 5.28 will be removed around 5.79
   *
   * It can be - relative or absolute.
   *
   * @param CRM_Core_Form $form
   *   The form object that we are operating on.
   * @param string $fieldName
   * @param int $count
   * @param string $from
   * @param string $to
   * @param string $fromLabel
   * @param bool $required
   * @param array $operators
   *   Additional Operator Selections to add.
   * @param string $dateFormat
   * @param bool $displayTime
   *
   * @return array
   *   Values for Selector
   */
  public static function returnDateRangeSelector(
    &$form, $fieldName, $count = 1,
    $from = '_from', $to = '_to', $fromLabel = 'From:',
    $required = FALSE, $operators = [],
    $dateFormat = 'searchDate', $displayTime = FALSE
  ) {
    CRM_Core_Error::deprecatedFunctionWarning('function has been deprecated since 5.28 & will be removed around 5.79');
    $selector = [
      '' => ts('- any -'),
      0 => ts('Choose Date Range'),
    ];
    // CRM-16195 Pull relative date filters from an option group
    $selector = $selector + CRM_Core_OptionGroup::values('relative_date_filters');

    if (is_array($operators)) {
      $selector = array_merge($selector, $operators);
    }

    $config = CRM_Core_Config::singleton();
    //if fiscal year start on 1 jan then remove fiscal year task
    //form list
    if ($config->fiscalYearStart['d'] == 1 & $config->fiscalYearStart['M'] == 1) {
      unset($selector['this.fiscal_year']);
      unset($selector['previous.fiscal_year']);
    }
    return $selector;
  }

  /**
   * Build the date range - relative or absolute.
   *
   * @deprecated since 5.28 will be removed around 5.79
   *
   * @param CRM_Core_Form $form
   *   The form object that we are operating on.
   * @param string $fieldName
   * @param array $selector
   *   Array of option values to add.
   * @param string $from
   *   Label.
   * @param string $to
   * @param string $fromLabel
   * @param bool $required
   * @param string $dateFormat
   * @param bool $displayTime
   * @param array $attributes
   */
  public static function addDateRangeToForm(
    &$form,
    $fieldName,
    $selector,
    $from,
    $to,
    $fromLabel,
    $required,
    $dateFormat,
    $displayTime,
    $attributes
  ) {
    CRM_Core_Error::deprecatedFunctionWarning('function has been deprecated since 5.28 & will be removed around 5.79');
    $form->add('select',
      "{$fieldName}_relative",
      ts('Relative Date Range'),
      $selector,
      $required,
      $attributes
    );

    $form->addDateRange($fieldName, $from, $to, $fromLabel, $dateFormat, FALSE, $displayTime);
  }

}
