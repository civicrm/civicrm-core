<?php
namespace Civi\ActionSchedule\Event;

use Civi\ActionSchedule\MappingInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MailingQueryEvent
 * @package Civi\ActionSchedule\Event
 *
 * This event allows listeners to modify the query which generates mailing data.
 * If you want to fetch extra mail-merge data as part of an initial query, then
 * modify the mailing-query to add extra JOINs/SELECTs.
 *
 * The basic mailing query looks a bit like this (depending on configuration):
 *
 * @code
 * SELECT reminder.id AS reminderID, reminder.contact_id as contactID, ...
 * FROM `civicrm_action_log` reminder
 * ... JOIN `target_entity` e ON e.id = reminder.entity_id ...
 * WHERE reminder.action_schedule_id = #casActionScheduleId
 * @endcode
 *
 * Listeners may modify the query. For example, suppose we want to load
 * additional fields from the related 'foo' entity:
 *
 * @code
 * $event->query->join('foo', '!casMailingJoinType civicrm_foo foo ON foo.myentity_id = e.id')
 *   ->select('foo.bar_value AS bar');
 * @endcode
 *
 * There are several parameters pre-set for use in queries:
 *  - 'casActionScheduleId'
 *  - 'casEntityJoinExpr' - eg 'e.id = reminder.entity_id'
 *  - 'casMailingJoinType' - eg 'LEFT JOIN' or 'INNER JOIN' (depending on configuration)
 *  - 'casMappingId'
 *  - 'casMappingEntity'
 *
 * (Note: When adding more JOINs, it seems typical to use !casMailingJoinType, although
 * some hard-code a LEFT JOIN. Don't have an explanation for why.)
 */
class MailingQueryEvent extends Event {

  /**
   * The schedule record which produced this mailing.
   *
   * @var \CRM_Core_DAO_ActionSchedule
   */
  public $actionSchedule;

  /**
   * The mapping record which produced this mailing.
   *
   * @var MappingInterface
   */
  public $mapping;

  /**
   * The alterable query. For details, see the class description.
   * @var \CRM_Utils_SQL_Select
   * @see MailingQueryEvent
   */
  public $query;

  /**
   * @param \CRM_Core_DAO_ActionSchedule $actionSchedule
   * @param MappingInterface $mapping
   * @param \CRM_Utils_SQL_Select $query
   */
  public function __construct($actionSchedule, $mapping, $query) {
    $this->actionSchedule = $actionSchedule;
    $this->mapping = $mapping;
    $this->query = $query;
  }

}
