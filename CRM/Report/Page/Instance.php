<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for invoking report instances
 */
class CRM_Report_Page_Instance extends CRM_Core_Page {
  /**
   * run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  function run() {
    $instanceId = CRM_Report_Utils_Report::getInstanceID();
    if (!$instanceId) {
      $instanceId = CRM_Report_Utils_Report::getInstanceIDForPath();
    }
    $action    = CRM_Utils_Request::retrieve('action', 'String', $this);
    $optionVal = CRM_Report_Utils_Report::getValueFromUrl($instanceId);
    $reportUrl = CRM_Utils_System::url('civicrm/report/list', "reset=1");

    if ($action & CRM_Core_Action::DELETE) {
      if (!CRM_Core_Permission::check('administer Reports')) {
        $statusMessage = ts('You do not have permission to Delete Report.');
        CRM_Core_Error::statusBounce($statusMessage, $reportUrl);
      }

      $navId = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_Instance', $instanceId, 'navigation_id', 'id');
      CRM_Report_BAO_Instance::delete($instanceId);

      //delete navigation if exists
      if ($navId) {
        CRM_Core_BAO_Navigation::processDelete($navId);
        CRM_Core_BAO_Navigation::resetNavigation();
      }

      CRM_Core_Session::setStatus(ts('Selected report has been deleted.'), ts('Deleted'), 'success');
    }
    else {
      $templateInfo = CRM_Core_OptionGroup::getRowValues('report_template', "{$optionVal}", 'value');
      if (empty($templateInfo)) {
        CRM_Core_Session::setStatus(ts('Could not find template for this report instance.'), ts('Template Not Found'), 'error');
        return;
      }

      $extKey = strpos($templateInfo['name'], '.');

      $reportClass = NULL;

      if ($extKey !== FALSE) {
        $ext = CRM_Extension_System::singleton()->getMapper();
        $reportClass = $ext->keyToClass($templateInfo['name'], 'report');
        $templateInfo['name'] = $reportClass;
      }

      if (strstr($templateInfo['name'], '_Form') || !is_null($reportClass)) {
        $instanceInfo = array();
        CRM_Report_BAO_Instance::retrieve(array('id' => $instanceId), $instanceInfo);

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
    }
    return CRM_Utils_System::redirect($reportUrl);
  }
}

