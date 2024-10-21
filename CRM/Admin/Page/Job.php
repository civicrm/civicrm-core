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
  public static $_links;

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
      self::$_links = [
        CRM_Core_Action::FOLLOWUP => [
          'name' => ts('View Job Log'),
          'url' => 'civicrm/admin/joblog',
          'qs' => 'jid=%%id%%&reset=1',
          'title' => ts('See log entries for this Scheduled Job'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        CRM_Core_Action::VIEW => [
          'name' => ts('Execute'),
          'url' => 'civicrm/admin/job/edit',
          'qs' => 'action=view&id=%%id%%&reset=1',
          'title' => ts('Execute Scheduled Job Now'),
          'weight' => -15,
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/job/edit',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Scheduled Job'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Scheduled Job'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Scheduled Job'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/job/edit',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Scheduled Job'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
        CRM_Core_Action::COPY => [
          'name' => ts('Copy'),
          'url' => 'civicrm/admin/job/edit',
          'qs' => 'action=copy&id=%%id%%&qfKey=%%key%%',
          'title' => ts('Copy Scheduled Job'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::COPY),
        ],
      ];
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
    CRM_Utils_System::setTitle(ts('Settings - Scheduled Jobs'));
    CRM_Utils_System::appendBreadCrumb([
      [
        'title' => ts('Administer'),
        'url' => CRM_Utils_System::url('civicrm/admin', 'reset=1'),
      ],
    ]);

    $this->_id = CRM_Utils_Request::retrieve('id', 'String',
      $this, FALSE, 0
    );
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 0
    );

    if (($this->_action & CRM_Core_Action::COPY) && (!empty($this->_id))) {
      $key = $_POST['qfKey'] ?? $_GET['qfKey'] ?? $_REQUEST['qfKey'] ?? NULL;
      $k = CRM_Core_Key::validate($key, CRM_Utils_System::getClassName($this));
      if (!$k) {
        $this->invalidKey();
      }
      try {
        $jobResult = civicrm_api3('Job', 'clone', ['id' => $this->_id]);
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
      CRM_Core_Session::setStatus(ts('Execution of scheduled jobs has been turned off by default since this is a non-production environment. You can override this for particular jobs by adding runInNonProductionEnvironment=TRUE as a parameter. Note: this will send emails if your <a %1>outbound email</a> is enabled.', [1 => 'href="' . CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1') . '"']), ts('Non-production Environment'), 'warning', ['expires' => 0]);
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

    $rows = [];
    foreach ($this->getJobs() as $job) {
      $action = array_sum(array_keys($this->links()));

      // update enable/disable links.
      // CRM-9868- remove enable action for jobs that should never be run automatically via execute action or runjobs url
      if ($job->api_action === 'process_membership_reminder_date' || $job->api_action === 'update_greeting') {
        $action -= CRM_Core_Action::ENABLE;
        $action -= CRM_Core_Action::DISABLE;
      }
      elseif ($job->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $job->action = CRM_Core_Action::formLink($this->links(), $action,
        ['id' => $job->id, 'key' => CRM_Core_Key::get(CRM_Utils_System::getClassName($this))],
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
   * Retrieves the list of jobs from the database,
   * populates class param.
   *
   * @fixme: Copied from JobManager. We should replace with API
   *
   * @return array
   *   ($id => CRM_Core_ScheduledJob)
   */
  private function getJobs(): array {
    $jobs = [];
    $dao = new CRM_Core_DAO_Job();
    $dao->orderBy('name');
    $dao->domain_id = CRM_Core_Config::domainID();
    $dao->find();
    while ($dao->fetch()) {
      $temp = ['class' => NULL, 'parameters' => NULL, 'last_run' => NULL];
      CRM_Core_DAO::storeValues($dao, $temp);
      $jobs[$dao->id] = new CRM_Core_ScheduledJob($temp);
    }
    return $jobs;
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
