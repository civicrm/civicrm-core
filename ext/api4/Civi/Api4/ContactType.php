<?php

namespace Civi\Api4;
use Civi\Api4\Generic\AbstractEntity;

/**
 * ContactType entity.
 *
 * With this entity you can create or update any new or existing Contact type or a sub type
 * In case of updating existing ContactType, id of that particular ContactType must
 * be in $params array.
 *
 * Creating a new contact type requires at minimum a label and parent_id.
 *
 * @package Civi\Api4
 */
class ContactType extends AbstractEntity {

}
