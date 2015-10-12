<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This interface defines methods that need to be implemented
 * by every scheduled job (cron task) in CiviCRM.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_ScheduledJob {

  var $version = 3;

  var $name = NULL;

  var $apiParams = array();

  var $remarks = array();

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
    $this->apiParams = array('version' => $this->version);

    if (!empty($this->parameters)) {
      $lines = explode("\n", $this->parameters);

      foreach ($lines as $line) {
        $pair = explode("=", $line);
        if (empty($pair[0]) || empty($pair[1])) {
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
   * @return bool
   */
  public function needsRunning() {
    // run if it was never run
    if (empty($this->last_run)) {
      return TRUE;
    }

    // run_frequency check
    switch ($this->run_frequency) {
      case 'Always':
        return TRUE;

      case 'Hourly':
        $format = 'YmdH';
        break;

      case 'Daily':
        $format = 'Ymd';
        break;
    }

    $now = CRM_Utils_Date::currentDBDate();
    $lastTime = date($format, strtotime($this->last_run));
    $thisTime = date($format, strtotime($now));

    return ($lastTime <> $thisTime);
  }

  public function __destruct() {
  }

}
