<?php

namespace Civi\Api4\Action\Address;

/**
 * @inheritDoc
 */
class Create extends \Civi\Api4\Generic\DAOCreateAction {
  use AddressSaveTrait;

}
