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


namespace api\v4\Traits;

trait QueryCounterTrait {

  /**
   * @var int
   */
  protected $startCount = 0;

  /**
   * Start the query counter
   */
  protected function beginQueryCount() {
    $this->startCount = $this->getCurrentGlobalQueryCount();
  }

  /**
   * @return int
   *   The number of queries since the counter was started
   */
  protected function getQueryCount() {
    return $this->getCurrentGlobalQueryCount() - $this->startCount;
  }

  /**
   * @return int
   * @throws \Exception
   */
  private function getCurrentGlobalQueryCount() {
    global $_DB_DATAOBJECT;

    if (!$_DB_DATAOBJECT) {
      throw new \Exception('Database object not set so cannot count queries');
    }

    return $_DB_DATAOBJECT['RESULTSEQ'] ?? 0;
  }

}
