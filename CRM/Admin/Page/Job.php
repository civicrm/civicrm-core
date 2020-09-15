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
class CRM_Admin_Page_Job extends CRM_Core_Page_Basic {

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
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::FOLLOWUP => array(
          'name' => ts('View Job Log'),
          'url' => 'civicrm/admin/joblog',
          'qs' => 'jid=%%id%%&reset=1',
          'title' => ts('See log entries for this Scheduled Job'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/job',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Scheduled Job'),
        ),
        CRM_Core_Action::VIEW => array(
          'name' => ts('Execute Now'),
          'url' => 'civicrm/admin/job',
          'qs' => 'action=view&id=%%id%%&reset=1',
          'title' => ts('Execute Scheduled Job Now'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Scheduled Job'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Scheduled Job'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/job',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Scheduled Job'),
        ),
        CRM_Core_Action::COPY => array(
          'name' => ts('Copy'),
          'url' => 'civicrm/admin/job',
          'qs' => 'action=copy&id=%%id%%',
          'title' => ts('Copy Scheduled Job'),
        ),
      );
    }
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
    CRM_Utils_System::setTitle(ts('Settings - Scheduled Jobs'));
    $breadCrumb = array(
      array(
        'title' => ts('Scheduled Jobs'),
        'url' => CRM_Utils_System::url('civicrm/admin',
          'reset=1'
        ),
      ),
    );
    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    $this->_id = CRM_Utils_Request::retrieve('id', 'String',
      $this, FALSE, 0
    );
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 0
    );

    if (($this->_action & CRM_Core_Action::COPY) && (!empty($this->_id))) {
      try {
        $jobResult = civicrm_api3('Job', 'clone', array('id' => $this->_id));
        if ($jobResult['count'] > 0) {
          CRM_Core_Session::setStatus($jobResult['values'][$jobResult['id']]['name'], ts('Job copied successfully'), 'success');
        }
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/job', 'reset=1'));
      }
      catch (Exception $e) {
        CRM_Core_Session::setStatus(ts('Failed to copy job'), 'Error');
      }
    }

    return parent::run();
  }

  /**
   * Browse all jobs.
   */
  public function browse() {
    // check if non-prod mode is enabled.
    if (CRM_Core_Config::environment() != 'Production') {
      CRM_Core_Session::setStatus(ts('Execution of scheduled jobs has been turned off by default since this is a non-production environment. You can override this for particular jobs by adding runInNonProductionEnvironment=TRUE as a parameter.'), ts("Non-production Environment"), "warning", array('expires' => 0));
    }
    else {
      $cronError = Civi\Api4\System::check(FALSE)
        ->addWhere('name', '=', 'checkLastCron')
        ->addWhere('severity_id', '>', 1)
        ->setIncludeDisabled(TRUE)
        ->execute()
        ->first();
      if ($cronError) {
        CRM_Core_Session::setStatus($cronError['message'], $cronError['title'], 'alert', ['expires' => 0]);
      }
    }

    $sj = new CRM_Core_JobManager();
    $rows = $temp = [];
    foreach ($sj->jobs as $job) {
      $action = array_sum(array_keys($this->links()));

      // update enable/disable links.
      // CRM-9868- remove enable action for jobs that should never be run automatically via execute action or runjobs url
      if ($job->api_action == 'process_membership_reminder_date' || $job->api_action == 'update_greeting') {
        $action -= CRM_Core_Action::ENABLE;
        $action -= CRM_Core_Action::DISABLE;
      }
      elseif ($job->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $job->action = CRM_Core_Action::formLink(self::links(), $action,
        array('id' => $job->id),
        ts('more'),
        FALSE,
        'job.manage.action',
        'Job',
        $job->id
      );
      $rows[] = get_object_vars($job);
    }
    $this->assign('rows', $rows);
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
