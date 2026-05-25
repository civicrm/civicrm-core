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
    foreach ($rows as &$row) {
      $row['resultValues'] = '';
      $row['resultIsMultiline'] = FALSE;
      $message = $row['description'];

      // Determine initial messageType as one of info|success|error based on extracting logLevel.
      $row['messageType'] = 'info';
      // .text-* class: one of: primary|secondary|success|info|warning|danger|muted
      $row['statusClass'] = '';
      $row['logLevel'] = 'debug';
      if (!empty($row['data'])) {
        $data = json_decode($row['data'], TRUE);
        if (!empty($data['logLevel'])) {
          $row['statusClass'] = [
            'error' => 'text-danger',
            'success' => 'text-success',
          ][$data['logLevel']] ?? '';
        }
        $row['logLevel'] = $data['logLevel'];
        $message = $data['message'] ?? $message;
      }

      // Handle special log entries.
      if (preg_match('/^Finished execution. (Error|Success): (.*)$/s', $message, $matches)) {
        if ($matches[1] === 'Success') {
          $resultValues = $matches[2];
          $row['description'] = ts('Finished execution successfully.');
          // Successful decode. Split the values out from the description (text)
          $row['messageType'] = 'success';
          $row['statusClass'] = 'text-success';
          // Some code generates HTML log output. Legacy messages are not JSON encoded.
          $decoded = json_decode($resultValues) ?? $resultValues;
          if (is_string($decoded) && preg_match(';(<br|<p|\r|\n);', $decoded)) {
            // Looks like it could do with some massaging.
            $decoded = trim(strtr($decoded, [
              '<br>' => "\n",
              '<br />' => "\n",
              '<br/>' => "\n",
              '\r' => "",
            ]));
            $resultValues = $decoded;
          }
          $row['resultValues'] = $resultValues;
          $row['resultIsMultiline'] = str_contains($resultValues, "\n");
        }
        else {
          $row['messageType'] = 'error';
          $row['statusClass'] = 'text-danger';
          $row['description'] = ts('Finished execution. Failure. Error message: %1', [1 => $matches[2]]);
        }
      }
      elseif (preg_match('/^(Could not|Error while)/', $row['description'] ?? '')) {
        $row['messageType'] = 'error';
      }
      // Other messages left alone. They include:
      // 'Starting executing...'
      // 'Starting scheduled jobs execution'
      // 'Could not authenticate...'
      // 'Error while executing...'
    }
    unset($row);

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
