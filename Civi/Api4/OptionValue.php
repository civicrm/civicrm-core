<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
namespace Civi\Api4;

/**
 * OptionValue entity.
 *
 * @see \Civi\Api4\OptionGroup
 * @searchable secondary
 * @orderBy weight
 * @groupWeightsBy option_group_id
 * @matchFields option_group_id,name,value
 * @since 5.19
 * @package Civi\Api4
 */
class OptionValue extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;
  use Generic\Traits\SortableEntity;

}
