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

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class XDebugSubscriber
 * @package Civi\API\Subscriber
 */
class XDebugSubscriber implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      Events::RESPOND => ['onApiRespond', Events::W_LATE],
    ];
  }

  /**
   * @param \Civi\API\Event\RespondEvent $event
   *   API response event.
   */
  public function onApiRespond(\Civi\API\Event\RespondEvent $event) {
    $apiRequest = $event->getApiRequest();
    $result = $event->getResponse();
    if (
      function_exists('xdebug_time_index')
      && !empty($apiRequest['params']['debug'])
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
      $debug['peakMemory'] = xdebug_peak_memory_usage();
      $debug['memory'] = xdebug_memory_usage();
      $debug['timeIndex'] = xdebug_time_index();
      $event->setResponse($result);
    }
  }

}
