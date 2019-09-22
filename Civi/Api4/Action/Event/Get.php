<?php
namespace Civi\Api4\Action\Event;

/**
 * @inheritDoc
 *
 * Set current = true to get active, non past events.
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {
  use \Civi\Api4\Generic\Traits\IsCurrentTrait;

}
