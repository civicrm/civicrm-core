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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * CRM_Report_Output_Interface implements helper functions to determine what to
 * do with the report data, such as displaying on the screen, printing, pdf,
 * csv, etc. Extensions can also provide their own implementations, such as the
 * civiexportexcel extension.
 */
interface CRM_Report_Output_Interface {

  /**
   * Class constructor.
   *
   * @param CRM_Report_Form $report
   */
  public function __construct(&$report);

  /**
   * Checks if the output method supports the SQL developer tab.
   *
   * @return bool
   */
  public function supportsSqlDeveloperTab();

  /**
   * Generate the output.
   *
   * @param array $rows
   */
  public function generateOutput(&$rows = NULL);

}
