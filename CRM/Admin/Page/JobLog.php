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

/**
 * Page for displaying list of jobs.
 */
class CRM_Admin_Page_JobLog extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_Job';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   */
  public function run() {
    // set title and breadcrumb
    CRM_Utils_System::setTitle(ts('Settings - Scheduled Jobs Log'));
    $breadCrumb = array(
      array(
        'title' => ts('Administration'),
        'url' => CRM_Utils_System::url('civicrm/admin',
          'reset=1'
        ),
      ),
    );
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    return parent::run();
  }

  /**
   * Browse all jobs.
   *
   * @param null $action
   */
  public function browse($action = NULL) {

    $jid = CRM_Utils_Request::retrieve('jid', 'Positive', $this);

    $sj = new CRM_Core_JobManager();

    $jobName = NULL;
    if ($jid) {
      $jobName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Job', $jid);
    }

    $this->assign('jobName', $jobName);

    $dao = new CRM_Core_DAO_JobLog();
    $dao->orderBy('id desc');

    // limit to last 1000 records
    $dao->limit(1000);

    if ($jid) {
      $dao->job_id = $jid;
    }
    $dao->find();

    $rows = array();
    while ($dao->fetch()) {
      unset($row);
      CRM_Core_DAO::storeValues($dao, $row);
      $rows[$dao->id] = $row;
    }
    $this->assign('rows', $rows);

    $this->assign('jobId', $jid);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_Job';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Scheduled Jobs';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/job';
  }

}
