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

use Civi\Api4\JobLog;

/**
 * Page for displaying log of jobs.
 */
class CRM_Admin_Page_JobLog extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName(): string {
    return 'CRM_Core_BAO_Job';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links(): array {
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   */
  public function run(): void {
    CRM_Utils_System::setTitle(ts('Settings - Scheduled Jobs Log'));
    CRM_Utils_System::appendBreadCrumb([
      [
        'title' => ts('Administer'),
        'url' => CRM_Utils_System::url('civicrm/admin',
          'reset=1'
        ),
      ],
    ]);
    parent::run();
  }

  /**
   * Browse all jobs.
   *
   * @throws \CRM_Core_Exception
   */
  public function browse(): void {
    $jid = CRM_Utils_Request::retrieve('jid', 'Positive');

    if ($jid) {
      $jobName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Job', $jid);
      $this->assign('jobName', $jobName);
      $jobRunUrl = CRM_Utils_System::url('civicrm/admin/job/edit', 'action=view&reset=1&context=joblog&id=' . $jid);
      $this->assign('jobRunUrl', $jobRunUrl);
    }
    else {
      $this->assign('jobName', FALSE);
      $this->assign('jobRunUrl', FALSE);
    }

    $jobLogsQuery = JobLog::get()
      ->addOrderBy('id', 'DESC')
      ->setLimit(1000);

    if ($jid) {
      $jobLogsQuery->addWhere('job_id', '=', $jid);
    }

    $rows = $jobLogsQuery->execute()->getArrayCopy();
    $this->assign('rows', $rows);
    $this->assign('jobId', $jid);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm(): string {
    return 'CRM_Admin_Form_Job';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName(): string {
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
  public function userContext($mode = NULL): string {
    return 'civicrm/admin/job';
  }

}
