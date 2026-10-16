<?php
namespace Civi\Api4;

/**
 * Scheduled emails of SearchKit displays.
 *
 * Each record emails the results of one SearchDisplay, as an attachment,
 * to a fixed list of contacts on a recurring schedule.
 *
 * Provided by the Search Kit extension.
 *
 * @since 6.21
 * @package Civi\Api4
 */
class SearchSchedule extends Generic\DAOEntity {

  public static function permissions() {
    $permissions = parent::permissions();
    // Schedules run unattended and email search results to arbitrary contacts,
    // so managing them is restricted to SearchKit administrators.
    $permissions['default'] = ['administer search_kit'];
    return $permissions;
  }

}
