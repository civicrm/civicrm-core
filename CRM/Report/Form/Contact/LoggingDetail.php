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
class CRM_Report_Form_Contact_LoggingDetail extends CRM_Logging_ReportDetail {

  /**
   */
  public function __construct() {
    $this->log_conn_id = CRM_Utils_Request::retrieve('log_conn_id', 'String');
    $this->log_date = CRM_Utils_Request::retrieve('log_date', 'String');
    $this->setTablesToContactRelatedTables();
    $this->calculateContactDiffs();
    $this->detail = 'logging/contact/detail';
    $this->summary = 'logging/contact/summary';

    parent::__construct();
  }

  public function buildQuickForm() {
    $layout = CRM_Utils_Request::retrieve('layout', 'String', $this);
    $this->assign('layout', $layout);

    parent::buildQuickForm();

    if ($this->cid) {
      // link back to contact summary
      $this->assign('backURL', CRM_Utils_System::url('civicrm/contact/view', "reset=1&selectedChild=log&cid={$this->cid}", FALSE, NULL, FALSE));
      $this->assign('revertURL', self::$_template->getTemplateVars('revertURL') . "&cid={$this->cid}");
    }
    else {
      // link back to summary report
      $this->assign('backURL', CRM_Report_Utils_Report::getNextUrl('logging/contact/summary', 'reset=1', FALSE, TRUE));
    }
  }

}
