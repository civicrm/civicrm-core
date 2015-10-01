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

    $extKey = strpos(CRM_Utils_Array::value('name', $templateInfo), '.');

    $reportClass = NULL;

    if ($extKey !== FALSE) {
      $ext = CRM_Extension_System::singleton()->getMapper();
      $reportClass = $ext->keyToClass($templateInfo['name'], 'report');
      $templateInfo['name'] = $reportClass;
    }

    if (strstr(CRM_Utils_Array::value('name', $templateInfo), '_Form') || !is_null($reportClass)) {
      CRM_Utils_System::setTitle(ts('%1 - Template', array(1 => $templateInfo['label'])));
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
