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
 * Dashboard entity.
 *
 * A "dashboard" record represents an item that can be displayed on a user's home screen.
 * E.g. the "News" or "Getting Started" dashboard items.
 *
 * Dashboards can also be created from CiviReports, and some extensions provide dashboards as well.
 * Displaying an item to a user is done with the `DashboardContact` entity.
 *
 * @see \Civi\Api4\DashboardContact
 * @since 5.25
 * @package Civi\Api4
 */
class Dashboard extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;

}
