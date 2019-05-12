<?php

namespace Civi\Api4\Action\CustomValue;

/**
 * Update one or more records with new values. Use the where clause to select them.
 */
class Update extends \Civi\Api4\Generic\DAOUpdateAction {
  use \Civi\Api4\Generic\Traits\CustomValueActionTrait;

}
