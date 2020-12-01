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
 * A bridge is a small table that provides an intermediary link between two other tables.
 *
 * The API can automatically incorporate a Bridge into a join expression.
 *
 * Note: at time of writing this trait does nothing except affect the "type" shown in Entity::get() metadata.
 */
trait EntityBridge {

}
