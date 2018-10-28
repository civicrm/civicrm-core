<?php

namespace Civi\Api4;
use Civi\Api4\Generic\AbstractEntity;

/**
 * GroupContact entity - link between groups and contacts.
 *
 * A contact can either be "Added" "Removed" or "Pending" in a group.
 * CiviCRM only considers them to be "in" a group if their status is "Added".
 *
 * @method static Action\GroupContact\Create create
 * @method static Action\GroupContact\Update update
 *
 * @package Civi\Api4
 */
class GroupContact extends AbstractEntity {

}
