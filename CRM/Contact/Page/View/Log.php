<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Contact_Page_View_Log extends CRM_Core_Page {

  /**
   * Called when action is browse.
   *
   * @return null
   */
  public function browse() {
    $loggingReport = CRM_Core_BAO_Log::useLoggingReport();
    $this->assign('useLogging', $loggingReport);

    if ($loggingReport) {
      $this->assign('instanceUrl',
        CRM_Utils_System::url("civicrm/report/instance/{$loggingReport}",
          "reset=1&force=1&snippet=4&section=2&altered_contact_id_op=eq&altered_contact_id_value={$this->_contactId}&cid={$this->_contactId}", FALSE, NULL, FALSE));
      return NULL;
    }

    $log = new CRM_Core_DAO_Log();

    $log->entity_table = 'civicrm_contact';
    $log->entity_id = $this->_contactId;
    $log->orderBy('modified_date desc');
    $log->find();

    $logEntries = [];
    while ($log->fetch()) {
      list($displayName, $contactImage) = CRM_Contact_BAO_Contact::getDisplayAndImage($log->modified_id);
      $logEntries[] = [
        'id' => $log->modified_id,
        'name' => $displayName,
        'image' => $contactImage,
        'date' => $log->modified_date,
      ];
    }

    $this->assign('logCount', count($logEntries));
    $this->ajaxResponse['tabCount'] = count($logEntries);
    $this->ajaxResponse += CRM_Contact_Form_Inline::renderFooter($this->_contactId, FALSE);
    $this->assign_by_ref('log', $logEntries);
  }

  public function preProcess() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
    $this->assign('displayName', $displayName);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);
  }

  /**
   * the main function that is called when the page loads, it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    $this->browse();

    return parent::run();
  }

}
