<?php

namespace api\v4\Traits;

use CRM_Utils_Array as ArrayHelper;

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

    return ArrayHelper::value('RESULTSEQ', $_DB_DATAOBJECT, 0);
  }

}
