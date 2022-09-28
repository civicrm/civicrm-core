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
 * Navigation menu items.
 *
 * @searchable none
 * @orderBy weight
 * @groupWeightsBy domain_id,parent_id
 * @since 5.19
 * @package Civi\Api4
 */
class Navigation extends Generic\DAOEntity {
  use Generic\Traits\SortableEntity;
  use Generic\Traits\ManagedEntity;

}
