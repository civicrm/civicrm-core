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
 * Class for configuring jobs.
 */
class CRM_Admin_Form_Job extends CRM_Admin_Form {

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  public function preProcess() {
    parent::preProcess();
    $this->setContext();

    if ($this->_action == CRM_Core_Action::DELETE) {
      $this->setTitle(ts('Delete Scheduled Job'));
    }
    elseif ($this->_action == CRM_Core_Action::ADD) {
      $this->setTitle(ts('New Scheduled Job'));
    }
    elseif ($this->_action == CRM_Core_Action::UPDATE) {
      $this->setTitle(ts('Edit Scheduled Job'));
    }
    elseif ($this->_action == CRM_Core_Action::VIEW) {
      $this->setTitle(ts('Execute Scheduled Job'));
    }

    CRM_Utils_System::appendBreadCrumb([
      [
        'title' => ts('Scheduled Jobs'),
        'url' => CRM_Utils_System::url('civicrm/admin/job', 'reset=1'),
      ],
    ]);

    if ($this->_id) {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/job/edit',
        "reset=1&action=update&id={$this->_id}",
        FALSE, NULL, FALSE
      );
    }
    else {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/job/add',
        "reset=1&action=add",
        FALSE, NULL, FALSE
      );
    }

    $this->assign('refreshURL', $refreshURL);
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Job';
  }

  /**
   * Build the form object.
   *
   * @param bool $check
   */
  public function buildQuickForm($check = FALSE) {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->assign('jobName', self::getJobName($this->_id));
      $this->addButtons([
        [
          'type' => 'submit',
          'name' => ts('Execute'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Job');

    $this->add('text', 'name', ts('Name'),
      $attributes['name'], TRUE
    );

    $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', [
      'CRM_Core_DAO_Job',
      $this->_id,
    ]);

    $this->add('text', 'description', ts('Description'),
      $attributes['description']
    );

    $this->add('text', 'api_entity', ts('API Call Entity'),
      $attributes['api_entity'], TRUE
    );

    $this->add('text', 'api_action', ts('API Call Action'),
      $attributes['api_action'], TRUE
    );

    $this->add('select', 'run_frequency', ts('Run frequency'), CRM_Core_SelectValues::getJobFrequency());

    // CRM-17686
    $this->add('datepicker', 'scheduled_run_date', ts('Scheduled Run Date'), NULL, FALSE, ['minDate' => date('Y-m-d')]);

    $this->add('textarea', 'parameters', ts('Command parameters'),
      ['cols' => 50, 'rows' => 6]
    );

    // is this job active ?
    $this->add('checkbox', 'is_active', ts('Is this Scheduled Job active?'));

    $this->addFormRule(['CRM_Admin_Form_Job', 'formRule']);
  }

  /**
   * @param array $fields
   *
   * @return array|bool
   * @throws CRM_Core_Exception
   */
  public static function formRule($fields) {
    $errors = [];

    try {
      $apiParams = CRM_Core_BAO_Job::parseParameters($fields['parameters']);
      /** @var \Civi\API\Kernel $apiKernel */
      $apiKernel = \Civi::service('civi_api_kernel');
      $apiRequest = \Civi\API\Request::create($fields['api_entity'], $fields['api_action'], $apiParams);
      $apiKernel->resolve($apiRequest);
    }
    catch (\Civi\API\Exception\NotImplementedException $e) {
      $errors['api_action'] = ts('Given API command is not defined.');
    }
    catch (CRM_Core_Exception $e) {
      $errors['parameters'] = ts('Parameters must be formatted as key=value on separate lines');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    if (!$this->_id) {
      $defaults['is_active'] = $defaults['is_default'] = 1;
      return $defaults;
    }
    $domainID = CRM_Core_Config::domainID();

    $dao = new CRM_Core_DAO_Job();
    $dao->id = $this->_id;
    $dao->domain_id = $domainID;
    if (!$dao->find(TRUE)) {
      return $defaults;
    }

    CRM_Core_DAO::storeValues($dao, $defaults);

    // CRM-17686
    if (!empty($dao->scheduled_run_date)) {
      $ts = strtotime($dao->scheduled_run_date);
      $defaults['scheduled_run_date'] = date("Y-m-d H:i:s", $ts);
    }

    // Legacy data might use lowercase api entity name, but it should always be CamelCase
    if (!empty($defaults['api_entity'])) {
      $defaults['api_entity'] = CRM_Utils_String::convertStringToCamel($defaults['api_entity']);
    }

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {

    CRM_Utils_System::flushCache();
    $redirectUrl = CRM_Utils_System::url('civicrm/admin/job', 'reset=1');
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_Job::deleteRecord(['id' => $this->_id]);
      CRM_Core_Session::setStatus("", ts('Scheduled Job Deleted.'), "success");
      CRM_Utils_System::redirect($redirectUrl);
      return;
    }

    // using View action for Execute. Doh.
    if ($this->_action & CRM_Core_Action::VIEW) {
      $jm = new CRM_Core_JobManager();
      $jm->executeJobById($this->_id);
      $jobName = self::getJobName($this->_id);
      CRM_Core_Session::setStatus(ts('%1 Scheduled Job has been executed. See the log for details.', [1 => $jobName]), ts("Executed"), "success");
      if ($this->getContext() === 'joblog') {
        // If we were triggered via the joblog form redirect back there when we finish
        $redirectUrl = CRM_Utils_System::url('civicrm/admin/joblog', 'reset=1&jid=' . $this->_id);
      }
      CRM_Utils_System::redirect($redirectUrl);
      return;
    }

    $values = $this->controller->exportValues($this->_name);
    $domainID = CRM_Core_Config::domainID();

    $dao = new CRM_Core_DAO_Job();

    $dao->id = $this->_id;
    $dao->domain_id = $domainID;
    $dao->run_frequency = $values['run_frequency'];
    $dao->parameters = $values['parameters'];
    $dao->name = $values['name'];
    $dao->api_entity = $values['api_entity'];
    $dao->api_action = $values['api_action'];
    $dao->description = $values['description'];
    $dao->is_active = $values['is_active'] ?? 0;

    // CRM-17686
    $ts = strtotime($values['scheduled_run_date']);
    // if a date/time is supplied and not in the past, then set the next scheduled run...
    if ($ts > time()) {
      $dao->scheduled_run_date = CRM_Utils_Date::currentDBDate($ts);
      // warn about monthly/quarterly scheduling, if applicable
      if (($dao->run_frequency === 'Monthly') || ($dao->run_frequency === 'Quarter')) {
        $info = getdate($ts);
        if ($info['mday'] > 28) {
          CRM_Core_Session::setStatus(
            ts('Relative month values are calculated based on the length of month(s) that they pass through.
              The result will land on the same day of the month except for days 29-31 when the target month contains fewer days than the previous month.
              For example, if a job is scheduled to run on August 31st, the following invocation will occur on October 1st, and then the 1st of every month thereafter.
              To avoid this issue, please schedule Monthly and Quarterly jobs to run within the first 28 days of the month.'),
            ts('Warning'), 'info', ['expires' => 0]);
        }
      }
    }
    // ...otherwise, if this isn't a new scheduled job, clear the next scheduled run
    elseif ($dao->id) {
      $job = new CRM_Core_ScheduledJob(['id' => $dao->id]);
      $job->clearScheduledRunDate();
    }

    $dao->save();

    // CRM-11143 - Give warning message if update_greetings is Enabled (is_active) since it generally should not be run automatically via execute action or runjobs url.
    if ($values['api_action'] == 'update_greeting' && ($values['is_active'] ?? NULL) == 1) {
      $docLink = CRM_Utils_System::docURL2("user/initial-set-up/scheduled-jobs/#job_update_greeting");
      $msg = ts('The update greeting job can be very resource intensive and is typically not necessary to run on a regular basis. If you do choose to enable the job, we recommend you do not run it with the force=1 option, which would rebuild greetings on all records. Leaving that option absent, or setting it to force=0, will only rebuild greetings for contacts that do not currently have a value stored. %1', [1 => $docLink]);
      CRM_Core_Session::setStatus($msg, ts('Warning: Update Greeting job enabled'), 'alert');
    }

    CRM_Utils_System::redirect($redirectUrl);
  }

  /**
   * Get the API action aka Job Name for this scheduled job
   * @param int $id - Id of the stored Job
   *
   * @return string
   */
  private static function getJobName($id) {
    $entity = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Job', $id, 'api_entity');
    $action = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Job', $id, 'api_action');
    $name = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Job', $id, 'name');
    return $name . ' (' . $entity . '.' . $action . ')';
  }

  /**
   * Override parent to do nothing - since we don't use this array.
   *
   * @return array
   */
  protected function retrieveValues(): array {
    return [];
  }

}
