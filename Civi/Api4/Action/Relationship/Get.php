<?php
namespace Civi\Api4\Action\Relationship;

/**
 * @inheritDoc
 *
 * Set current = true to get active, non past relationships.
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {
  use \Civi\Api4\Generic\Traits\IsCurrentTrait;

}
