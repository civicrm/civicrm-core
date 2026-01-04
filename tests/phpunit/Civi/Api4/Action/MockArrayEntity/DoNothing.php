<?php

namespace Civi\Api4\Action\MockArrayEntity;

use Civi\Api4\Generic\Result;

/**
 * Action that does nothing; called via magic method
 */
class DoNothing extends \Civi\Api4\Generic\AbstractAction {

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    // Doing exactly as advertised.
  }

}
