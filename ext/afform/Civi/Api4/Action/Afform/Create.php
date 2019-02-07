<?php

namespace civi\Api4\Action\Afform;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Traits\AfformCrudTrait;
use Civi\Api4\Generic\Result;

class Create extends \Civi\Api4\Action\Create {

  use AfformCrudTrait;

  public function _run(Result $result) {
    throw new NotImplementedException("Not supported: Afform.create"); // FIXME
  }

}
