<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\Traits\AfformCrudTrait;

class Update extends \Civi\Api4\Action\Update {

  use AfformCrudTrait;

  protected $select = ['name'];

}
