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

namespace Civi\Api4\Generic\Traits;

/**
 * A sortable entity has a 'weight' column which will be auto-updated by the API.
 *
 * NOTE: In order to use this trait, an entity must declare `@orderBy`
 */
trait SortableEntity {

}
