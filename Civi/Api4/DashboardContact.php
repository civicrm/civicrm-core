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
 * DashboardContact entity.
 *
 * This places a dashboard item on a user's home screen.
 *
 * @searchable bridge
 * @see \Civi\Api4\Dashboard
 * @searchable none
 * @since 5.25
 * @package Civi\Api4
 */
class DashboardContact extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

}
