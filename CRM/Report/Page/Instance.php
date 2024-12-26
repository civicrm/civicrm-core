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
 * Page for invoking report instances
 */
class CRM_Report_Page_Instance extends CRM_Core_Page {

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    $instanceId = CRM_Report_Utils_Report::getInstanceID();
    if (!$instanceId) {
      $instanceId = CRM_Report_Utils_Report::getInstanceIDForPath();
    }

    $action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $reportUrl = CRM_Utils_System::url('civicrm/report/list', "reset=1");

    if ($action & CRM_Core_Action::DELETE) {
      CRM_Report_BAO_ReportInstance::doFormDelete($instanceId, $reportUrl);
      return CRM_Utils_System::redirect($reportUrl);
    }

    if (is_numeric($instanceId)) {
      $instanceURL = CRM_Utils_System::url("civicrm/report/instance/{$instanceId}", 'reset=1');
      CRM_Core_Session::singleton()->replaceUserContext($instanceURL);
    }
    $optionVal = CRM_Report_Utils_Report::getValueFromUrl($instanceId);
    $templateInfo = CRM_Core_OptionGroup::getRowValues('report_template', "{$optionVal}", 'value');
    if (empty($templateInfo)) {
      CRM_Core_Session::singleton()->replaceUserContext(CRM_Utils_System::url('civicrm/report/list', 'reset=1'));
      CRM_Core_Error::statusBounce(ts('You have tried to access a report that does not exist.'));
    }

    $extKey = strpos($templateInfo['name'], '.');

    $reportClass = NULL;

    if ($extKey !== FALSE) {
      $ext = CRM_Extension_System::singleton()->getMapper();
      $reportClass = $ext->keyToClass($templateInfo['name'], 'report');
      $templateInfo['name'] = $reportClass;
    }

    if (str_contains($templateInfo['name'], '_Form') || !is_null($reportClass)) {
      $instanceInfo = [];
      CRM_Report_BAO_ReportInstance::retrieve(['id' => $instanceId], $instanceInfo);

      if (!empty($instanceInfo['title'])) {
        CRM_Utils_System::setTitle($instanceInfo['title']);
        $this->assign('reportTitle', $instanceInfo['title']);
      }
      else {
        CRM_Utils_System::setTitle($templateInfo['label']);
        $this->assign('reportTitle', $templateInfo['label']);
      }

      $wrapper = new CRM_Utils_Wrapper();
      return $wrapper->run($templateInfo['name'], NULL, NULL);
    }

    CRM_Core_Session::setStatus(ts('Could not find template for the instance.'), ts('Template Not Found'), 'error');

    return CRM_Utils_System::redirect($reportUrl);
  }

}
