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
 * ActivityContact BridgeEntity.
 *
 * This connects a contact to an activity.
 *
 * The record_type_id field determines the contact's role in the activity (source, target, or assignee).
 * @ui_join_filters record_type_id
 *
 * @searchable bridge
 * @see \Civi\Api4\Activity
 * @since 5.19
 * @package Civi\Api4
 */
class ActivityContact extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

}
