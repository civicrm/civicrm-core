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
 */

/**
 * Page for invoking report templates.
 */
class CRM_Report_Page_Report extends CRM_Core_Page {

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    if (!CRM_Core_Permission::check('administer Reports')) {
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/report/list', 'reset=1'));
    }

    $optionVal = CRM_Report_Utils_Report::getValueFromUrl();

    $templateInfo = CRM_Core_OptionGroup::getRowValues('report_template', "{$optionVal}", 'value',
      'String', FALSE, TRUE
    );

    $extKey = strpos($templateInfo['name'] ?? '', '.');

    $reportClass = NULL;

    if ($extKey !== FALSE) {
      $ext = CRM_Extension_System::singleton()->getMapper();
      $reportClass = $ext->keyToClass($templateInfo['name'], 'report');
      $templateInfo['name'] = $reportClass;
    }

    if (str_contains($templateInfo['name'] ?? '', '_Form') || !is_null($reportClass)) {
      CRM_Utils_System::setTitle(ts('%1 - Template', [1 => $templateInfo['label']]));
      $this->assign('reportTitle', $templateInfo['label']);

      $session = CRM_Core_Session::singleton();
      $session->set('reportDescription', $templateInfo['description']);

      $wrapper = new CRM_Utils_Wrapper();

      return $wrapper->run($templateInfo['name'], NULL, NULL);
    }

    if ($optionVal) {
      CRM_Core_Session::setStatus(ts('Could not find the report template. Make sure the report template is registered and / or url is correct.'), ts('Template Not Found'), 'error');
    }
    return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/report/list', 'reset=1'));
  }

}
