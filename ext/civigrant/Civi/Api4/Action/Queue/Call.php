<?php

namespace Civi\Api4\Action\Queue;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * @method string getStartTimestamp()
 * @method $this setStartDateTime(string $startDateTime)
 */
class Call extends AbstractAction {

  /**
   * Start Date Time in strtotime format.
   *
   * @var string
   */
  protected string $startDateTime = '';

  /**
   * Number.
   *
   * @var int
   */
  protected $number;

  public function _run(Result $result) {
    $result[] = ['called' => TRUE];
  }

}
