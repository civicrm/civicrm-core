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
 * MembershipStatus entity.
 *
 * @searchable secondary
 * @orderBy weight
 * @since 5.42
 * @package Civi\Api4
 */
class MembershipStatus extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;
  use Generic\Traits\SortableEntity;

}
