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
