<?php

/**
 * @package CRM
 */
class CRM_Utils_Check_Component_Event extends CRM_Utils_Check_Component {

  /**
   * @inheritDoc
   */
  public function isEnabled() {
    return CRM_Core_Component::isEnabled('CiviEvent');
  }

  /**
   * Check events have timezone set.
   *
   * @return CRM_Utils_Check_Message[]
   * @throws \API_Exception
   */
  public function checkTimezones() {
    $messages = [];

    try {
      $count = \Civi\Api4\Event::get(FALSE)
        ->selectRowCount()
        ->addWhere('event_tz', 'IS EMPTY')
        ->execute()
        ->rowCount;

      if ($count) {
        $msg = new CRM_Utils_Check_Message(
          __FUNCTION__,
          '<p>' . ts('%count Event has no timezone set', ['count' => $count, 'plural' => '%count Events have no timezone set.']) . '</p>',
          ts('Events with Missing Timezone'),
          \Psr\Log\LogLevel::WARNING,
          'fa-calendar'
        );
        $msg->addAction(
          ts('Fix Events with Missing Timezone'),
          ts('This will set the system default timezone "%1" for all Events with no timezone set.', [1 => CRM_Core_Config::singleton()->userSystem->getTimeZoneString()]),
          'api3',
          ['Event', 'addMissingTimezones']
        );
        $messages[] = $msg;
      }
    }
    catch (API_Exception $e) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('API Exception: %1 while checking events for timezones.', ['1' => $e->getMessage()]),
        ts('Event timezone check failed'),
        \Psr\Log\LogLevel::ERROR,
        'fa-calendar'
      );
    }

    return $messages;
  }

}
