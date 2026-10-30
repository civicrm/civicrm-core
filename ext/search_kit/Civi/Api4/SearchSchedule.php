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
 * @method Action\SearchSchedule\SendDue sendDue(bool $checkPermissions)
 *
 * @since 6.21
 * @package Civi\Api4
 */
class SearchSchedule extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\SearchSchedule\SendDue
   */
  public static function sendDue($checkPermissions = TRUE) {
    return (new Action\SearchSchedule\SendDue(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    $permissions = parent::permissions();
    // Schedules run unattended and email search results to arbitrary contacts,
    // so managing them is restricted to SearchKit administrators.
    $permissions['default'] = ['administer search_kit'];
    return $permissions;
  }

}
