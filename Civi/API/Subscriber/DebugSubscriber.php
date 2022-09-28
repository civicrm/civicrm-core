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

namespace Civi\API\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class XDebugSubscriber
 * @package Civi\API\Subscriber
 */
class DebugSubscriber implements EventSubscriberInterface {

  /**
   * @var \Civi\API\LogObserver
   */
  private $debugLog;

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => ['onApiPrepare', 999],
      'civi.api.respond' => ['onApiRespond', -999],
    ];
  }

  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (!isset($this->debugLog)
      && !empty($apiRequest['params']['debug'])
      && (empty($apiRequest['params']['check_permissions']) || \CRM_Core_Permission::check('view debug output'))
    ) {
      $this->debugLog = new \Civi\API\LogObserver();
      \CRM_Core_Error::createDebugLogger()->attach($this->debugLog);
    }
  }

  /**
   * @param \Civi\API\Event\RespondEvent $event
   *   API response event.
   */
  public function onApiRespond(\Civi\API\Event\RespondEvent $event) {
    $apiRequest = $event->getApiRequest();
    $result = $event->getResponse();
    if (!empty($apiRequest['params']['debug'])
      && (empty($apiRequest['params']['check_permissions']) || \CRM_Core_Permission::check('view debug output'))
    ) {
      if (is_a($result, '\Civi\Api4\Generic\Result')) {
        $result->debug = $result->debug ?? [];
        $debug =& $result->debug;
      }
      // result would not be an array for api3 getvalue
      elseif (is_array($result)) {
        $result['xdebug'] = $result['xdebug'] ?? [];
        $debug =& $result['xdebug'];
      }
      else {
        return;
      }
      if (isset($this->debugLog) && $this->debugLog->getMessages()) {
        $debug['log'] = $this->debugLog->getMessages();
      }
      if (function_exists('xdebug_time_index')) {
        $debug['peakMemory'] = xdebug_peak_memory_usage();
        $debug['memory'] = xdebug_memory_usage();
        $debug['timeIndex'] = xdebug_time_index();
      }
      $event->setResponse($result);
    }
  }

}
