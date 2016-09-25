<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
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
      $this->assign('revertURL', self::$_template->get_template_vars('revertURL') . "&cid={$this->cid}");
    }
    else {
      // link back to summary report
      $this->assign('backURL', CRM_Report_Utils_Report::getNextUrl('logging/contact/summary', 'reset=1', FALSE, TRUE));
    }
  }

}
