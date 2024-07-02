<?php


namespace Civi\API;

/**
 * API Error Log Observer
 *
 * @see \CRM_Core_Error_Log
 * @see \Civi\API\Subscriber\DebugSubscriber
 *
 * @package Civi\API
 */
class LogObserver extends \Log_observer {

  /**
   * @var array
   */
  private static $messages = [];

  /**
   * @see \Log::_announce
   * @param array $event
   */
  public function notify(array $event): void {
    $levels = \CRM_Core_Error_Log::getMap();
    $event['level'] = array_search($event['priority'], $levels);
    // Extract [civi.tag] from message string
    // As noted in \CRM_Core_Error_Log::log() the $context array gets prematurely converted to string with print_r() so we have to un-flatten it here
    if (preg_match('/^(.*)\s*Array\s*\(\s*\[civi\.(\w+)] => (\w+)\s*\)/', $event['message'], $message)) {
      $event['message'] = $message[1];
      $event[$message[2]] = $message[3];
    }
    self::$messages[] = $event;
  }

  /**
   * @return array
   */
  public function getMessages() {
    return self::$messages;
  }

}
