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

namespace Civi\Report;

/**
 *
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class OutputHandlerFactory {

  /**
   * Array of output modes to the respective class
   * @var array
   */
  public $outputHandlers;

  /**
   * Set the relevant OutputHandler to use in Export Forms.
   * @param string $outputMode
   * @param \CRM_Report_Form $reportForm
   * @return \CRM_Report_Output_Interface
   */
  public function getOutputHandler(&$outputMode, $reportForm) {
    $this->buildOutputHandlers();
    return new $this->outputHandlers[$outputMode]($reportForm);
  }

  public function buildOutputHandlers() {
    $outputHandlers = [
      'csv' => 'CRM_Report_Output_Csv',
      'pdf' => 'CRM_Report_Output_Pdf',
      'print' => 'CRM_Report_Output_Print',
    ];
    \CRM_Utils_Hook::outputHandlers($outputHandlers);
    $this->outputHandlers = $outputHandlers;
  }

}
