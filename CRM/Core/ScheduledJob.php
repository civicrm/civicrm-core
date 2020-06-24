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
 * This interface defines methods that need to be implemented
 * by every scheduled job (cron task) in CiviCRM.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */
class CRM_Core_ScheduledJob {

  public $version = 3;

  public $name = NULL;

  public $apiParams = [];

  public $remarks = [];

  /**
   * @param array $params
   */
  public function __construct($params) {
    foreach ($params as $name => $param) {
      $this->$name = $param;
    }

    // version is set to 3 by default - if different number
    // defined in params, it's replaced later on, however,
    // it's practically useles, since it seems none of api v2
    // will work properly in cron job setup. It might become
    // useful when/if api v4 starts to emerge and will need
    // testing in the cron job setup. To permanenty require
    // hardcoded api version, it's enough to move below line
    // under following if block.
    $this->apiParams = ['version' => $this->version];

    if (!empty($this->parameters)) {
      $lines = explode("\n", $this->parameters);

      foreach ($lines as $line) {
        $pair = explode("=", $line);
        if ($pair === FALSE || count($pair) != 2 || trim($pair[0]) == '' || trim($pair[1]) == '') {
          $this->remarks[] .= 'Malformed parameters!';
          break;
        }
        $this->apiParams[trim($pair[0])] = trim($pair[1]);
      }
    }
  }

  /**
   * @param null $date
   */
  public function saveLastRun($date = NULL) {
    $dao = new CRM_Core_DAO_Job();
    $dao->id = $this->id;
    $dao->last_run = ($date == NULL) ? CRM_Utils_Date::currentDBDate() : CRM_Utils_Date::currentDBDate($date);
    $dao->save();
  }

  /**
   * @return void
   */
  public function clearScheduledRunDate() {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_job SET scheduled_run_date = NULL WHERE id = %1',
      [
        '1' => [$this->id, 'Integer'],
      ]);
  }

  /**
   * @return bool
   */
  public function needsRunning() {

    // CRM-17686
    // check if the job has a specific scheduled date/time
    if (!empty($this->scheduled_run_date)) {
      if (strtotime($this->scheduled_run_date) <= time()) {
        $this->clearScheduledRunDate();
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    // run if it was never run
    if (empty($this->last_run)) {
      return TRUE;
    }

    // run_frequency check
    switch ($this->run_frequency) {
      case 'Always':
        return TRUE;

      // CRM-17669
      case 'Yearly':
        $offset = '+1 year';
        break;

      case 'Quarter':
        $offset = '+3 months';
        break;

      case 'Monthly':
        $offset = '+1 month';
        break;

      case 'Weekly':
        $offset = '+1 week';
        break;

      case 'Daily':
        $offset = '+1 day';
        break;

      case 'Hourly':
        $offset = '+1 hour';
        break;
    }

    $now = strtotime(CRM_Utils_Date::currentDBDate());
    $lastTime = strtotime($this->last_run);
    $nextTime = strtotime($offset, $lastTime);

    return ($now >= $nextTime);
  }

  public function __destruct() {
  }

}
