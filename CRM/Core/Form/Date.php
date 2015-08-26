<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_Form_Date {

  /**
   * Various Date Formats.
   */
  const DATE_yyyy_mm_dd = 1, DATE_mm_dd_yy = 2, DATE_mm_dd_yyyy = 4, DATE_Month_dd_yyyy = 8, DATE_dd_mon_yy = 16, DATE_dd_mm_yyyy = 32;

  /**
   * build the date-format form
   *
   * @param CRM_Core_Form $form
   *   The form object that we are operating on.
   *
   */
  public static function buildAllowedDateFormats(&$form) {

    $dateOptions = array();

    if (CRM_Utils_System::getClassName($form) == 'CRM_Activity_Import_Form_DataSource') {
      $dateText = ts('yyyy-mm-dd OR yyyy-mm-dd HH:mm OR yyyymmdd OR yyyymmdd HH:mm (1998-12-25 OR 1998-12-25 15:33 OR 19981225 OR 19981225 10:30 OR ( 2008-9-1 OR 2008-9-1 15:33 OR 20080901 15:33)');
    }
    else {
      $dateText = ts('yyyy-mm-dd OR yyyymmdd (1998-12-25 OR 19981225) OR (2008-9-1 OR 20080901)');
    }

    $dateOptions[] = $form->createElement('radio', NULL, NULL, $dateText, self::DATE_yyyy_mm_dd);

    $dateOptions[] = $form->createElement('radio', NULL, NULL, ts('mm/dd/yy OR mm-dd-yy (12/25/98 OR 12-25-98) OR (9/1/08 OR 9-1-08)'), self::DATE_mm_dd_yy);
    $dateOptions[] = $form->createElement('radio', NULL, NULL, ts('mm/dd/yyyy OR mm-dd-yyyy (12/25/1998 OR 12-25-1998) OR (9/1/2008 OR 9-1-2008)'), self::DATE_mm_dd_yyyy);
    $dateOptions[] = $form->createElement('radio', NULL, NULL, ts('Month dd, yyyy (December 12, 1998)'), self::DATE_Month_dd_yyyy);
    $dateOptions[] = $form->createElement('radio', NULL, NULL, ts('dd-mon-yy OR dd/mm/yy (25-Dec-98 OR 25/12/98)'), self::DATE_dd_mon_yy);
    $dateOptions[] = $form->createElement('radio', NULL, NULL, ts('dd/mm/yyyy (25/12/1998) OR (1/9/2008)'), self::DATE_dd_mm_yyyy);
    $form->addGroup($dateOptions, 'dateFormats', ts('Date Format'), '<br/>');
    $form->setDefaults(array('dateFormats' => self::DATE_yyyy_mm_dd));
  }


  /**
   * retrieve the date range - relative or absolute
   * and assign it to the form
   *
   * @param CRM_Core_Form $form
   *   The form the dates should be added to.
   * @param string $fieldName
   * @param int $count
   * @param string $from
   * @param string $to
   * @param string $fromLabel
   * @param bool $required
   * @param array $operators
   *   Additional value pairs to add.
   * @param string $dateFormat
   * @param bool|string $displayTime
   *
   */
  public static function buildDateRange(
    &$form, $fieldName, $count = 1,
    $from = '_from', $to = '_to', $fromLabel = 'From:',
    $required = FALSE, $operators = array(),
    $dateFormat = 'searchDate', $displayTime = FALSE
  ) {
    $selector
      = CRM_Core_Form_Date::returnDateRangeSelector(
        $form, $fieldName, $count,
        $from, $to, $fromLabel,
        $required, $operators,
        $dateFormat, $displayTime
      );
    CRM_Core_Form_Date::addDateRangeToForm(
      $form, $fieldName, $selector,
      $from, $to, $fromLabel,
      $required, $dateFormat, $displayTime
    );
  }

  /**
   * build the date range array that will provide the form option values.
   * It can be - relative or absolute
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
   * @return array
   *   Values for Selector
   */
  public static function returnDateRangeSelector(
    &$form, $fieldName, $count = 1,
    $from = '_from', $to = '_to', $fromLabel = 'From:',
    $required = FALSE, $operators = array(),
    $dateFormat = 'searchDate', $displayTime = FALSE
  ) {
    $selector = array(
      '' => ts('- any -'),
      0 => ts('Choose Date Range'),
      'previous_2.year' => ts('Previous 2 calendar years'),
      'previous_2.quarter' => ts('Previous 2 quarters'),
      'previous_2.month' => ts('Previous 2 calendar months'),
      'previous_2.week' => ts('Previous 2 weeks'),
      'previous_2.day' => ts('Previous 2 days'),
      'previous_before.year' => ts('Year prior to previous calendar year'),
      'previous_before.quarter' => ts('Quarter prior to previous quarter'),
      'previous_before.month' => ts('Month prior to previous calendar month'),
      'previous_before.week' => ts('Week prior to previous week'),
      'previous_before.day' => ts('Day prior to previous day'),
      'previous.year' => ts('Previous calendar year'),
      'previous.fiscal_year' => ts('Previous fiscal year'),
      'previous.quarter' => ts('Previous quarter'),
      'previous.month' => ts('Previous calendar month'),
      'previous.week' => ts('Previous week'),
      'earlier.year' => ts('To end of previous calendar year'),
      'earlier.quarter' => ts('To end of previous quarter'),
      'earlier.month' => ts('To end of previous calendar month'),
      'earlier.week' => ts('To end of previous week'),
      'earlier.day' => ts('To end of previous day'),
      'greater_previous.year' => ts('From end of previous calendar year'),
      'greater_previous.quarter' => ts('From end of previous quarter'),
      'greater_previous.month' => ts('From end of previous calendar month'),
      'greater_previous.week' => ts('From end of previous week'),
      'greater.year' => ts('From start of current calendar year'),
      'greater.quarter' => ts('From start of current quarter'),
      'greater.month' => ts('From start of current calendar month'),
      'greater.week' => ts('From start of current week'),
      'greater.day' => ts('From start of current day'),
      'current.year' => ts('Current calendar year to-date'),
      'current.quarter' => ts('Current quarter to-date'),
      'current.month' => ts('Current calendar month to-date'),
      'current.week' => ts('Current week to-date'),
      'ending_3.year' => ts('Last 3 years including today'),
      'ending_2.year' => ts('Last 2 years including today'),
      'ending.year' => ts('Last 12 months including today'),
      'ending.quarter' => ts('Last 3 months including today'),
      'ending.month' => ts('Last 30 days including today'),
      'ending.week' => ts('Last 7 days including today'),
      'previous.day' => ts('Yesterday'),
      'this.year' => ts('This calendar year'),
      'this.fiscal_year' => ts('This fiscal year'),
      'this.quarter' => ts('This quarter'),
      'this.month' => ts('This calendar month'),
      'this.week' => ts('This week'),
      'this.day' => ts('Today'),
      'starting.day' => ts('Tomorrow'),
      'starting.week' => ts('Next 7 days including today'),
      'starting.month' => ts('Next month including today'),
      'starting.year' => ts('Next 12 months including today'),
      'less.year' => ts('To end of current calendar year'),
      'less.quarter' => ts('To end of current quarter'),
      'less.month' => ts('To end of current calendar month'),
      'less.week' => ts('To end of current week'),
      'next.week' => ts('Next week'),
      'next.month' => ts('Next calendar month'),
      'next.quarter' => ts('Next quarter'),
      'next.fiscal_year' => ts('Next fiscal year'),
      'next.year' => ts('Next calendar year'),
    );

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
   * build the date range - relative or absolute
   *
   * @param CRM_Core_Form $form
   *   The form object that we are operating on.
   * @param string $fieldName
   * @param array $selector
   *   Array of option values to add.
   * @param string $from
   *   Label.
   * @param string|\stringe $to
   * @param string $fromLabel
   * @param bool $required
   * @param string $dateFormat
   * @param bool $displayTime
   */
  public static function addDateRangeToForm(&$form, $fieldName, $selector, $from = '_from', $to = '_to', $fromLabel = 'From:', $required = FALSE, $dateFormat = 'searchDate', $displayTime = FALSE) {
    $form->add('select',
      "{$fieldName}_relative",
      ts('Relative Date Range'),
      $selector,
      $required,
      array('class' => 'crm-select2')
    );

    $form->addDateRange($fieldName, $from, $to, $fromLabel, $dateFormat, FALSE, $displayTime);
  }

}
