<?php

namespace civi\Api4\Action\Afform;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Traits\AfformCrudTrait;
use Civi\Api4\Generic\Result;

class Delete extends \Civi\Api4\Action\Delete {

  use AfformCrudTrait;

  protected $select = ['name'];

  public function _run(Result $result) {
    throw new NotImplementedException("Not supported: Afform.delete"); // FIXME
  }

}
