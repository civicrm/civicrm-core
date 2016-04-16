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
  static $_links = NULL;

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
        CRM_Core_Action::EXPORT => array(
          'name' => ts('Execute Now'),
          'url' => 'civicrm/admin/job',
          'qs' => 'action=export&id=%%id%%&reset=1',
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

    if ($this->_action == 'export') {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/job', 'reset=1'));
    }

    return parent::run();
  }

  /**
   * Browse all jobs.
   *
   * @param null $action
   */
  public function browse($action = NULL) {

    // using Export action for Execute. Doh.
    if ($this->_action & CRM_Core_Action::EXPORT) {
      $jm = new CRM_Core_JobManager();
      $jm->executeJobById($this->_id);

      CRM_Core_Session::setStatus(ts('Selected Scheduled Job has been executed. See the log for details.'), ts("Executed"), "success");
    }

    $sj = new CRM_Core_JobManager();
    $rows = $temp = array();
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
