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
class CRM_Contact_Page_View_Log extends CRM_Core_Page {

  /**
   * @var int
   * @internal
   */
  public $_contactId;

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
    $modifiers = [];

    $log->entity_table = 'civicrm_contact';
    $log->entity_id = $this->_contactId;
    $log->orderBy('modified_date desc');
    $log->find();

    $logEntries = [];
    while ($log->fetch()) {
      if ($log->modified_id && !isset($modifiers[$log->modified_id])) {
        $displayInfo = CRM_Contact_BAO_Contact::getDisplayAndImage($log->modified_id);
        $modifiers[$log->modified_id] = ['name' => $displayInfo[0] ?? '', 'image' => $displayInfo[1] ?? ''];
      }

      $logEntries[] = [
        'id' => $log->modified_id,
        'name' => $modifiers[$log->modified_id]['name'] ?? '',
        'image' => $modifiers[$log->modified_id]['image'] ?? '',
        'date' => $log->modified_date,
      ];
    }

    $this->assign('logCount', count($logEntries));
    $this->ajaxResponse['tabCount'] = count($logEntries);
    $this->ajaxResponse += CRM_Contact_Form_Inline::renderFooter($this->_contactId, FALSE);
    $this->assign('log', $logEntries);
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
