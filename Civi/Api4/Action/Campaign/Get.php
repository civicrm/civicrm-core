<?php
namespace Civi\Api4\Action\Campaign;

/**
 * @inheritDoc
 *
 * Set current = true to get active, non past campaigns.
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {
  use \Civi\Api4\Generic\Traits\IsCurrentTrait;

}
