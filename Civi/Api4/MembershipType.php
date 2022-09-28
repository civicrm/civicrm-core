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
 * MembershipType entity.
 *
 * @searchable secondary
 * @orderBy weight
 * @groupWeightsBy domain_id
 * @since 5.27
 * @package Civi\Api4
 */
class MembershipType extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;
  use Generic\Traits\SortableEntity;

}
