<?php

namespace Civi\Api4\Action\Phone;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

class Call extends AbstractAction {

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
