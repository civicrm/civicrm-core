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
 * UFField entity - aka profile fields.
 *
 * @see \Civi\Api4\UFGroup
 * @orderBy weight
 * @groupWeightsBy uf_group_id
 * @since 5.19
 * @package Civi\Api4
 */
class UFField extends Generic\DAOEntity {
  use Generic\Traits\SortableEntity;
  use Generic\Traits\ManagedEntity;

}
