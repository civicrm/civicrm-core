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
 * This class determines what fields are allowed for a request
 * and validates that the fields are provided correctly.
 */
class APIv3SchemaAdapter implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => [
        ['onApiPrepare', Events::W_MIDDLE],
        ['onApiPrepare_validate', Events::W_LATE],
      ],
    ];
  }

  /**
   * @param \Civi\API\Event\PrepareEvent $event
   *   API preparation event.
   *
   * @throws \CRM_Core_Exception
   */
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] > 3) {
      return;
    }

    $apiRequest['fields'] = _civicrm_api3_api_getfields($apiRequest);

    _civicrm_api3_swap_out_aliases($apiRequest, $apiRequest['fields']);
    if (strtolower($apiRequest['action']) != 'getfields') {
      if (empty($apiRequest['params']['id'])) {
        $apiRequest['params'] = array_merge($this->getDefaults($apiRequest['fields']), $apiRequest['params']);
      }
      // Note: If 'id' is set then verify_mandatory will only check 'version'.
      civicrm_api3_verify_mandatory($apiRequest['params'], NULL, $this->getRequired($apiRequest['fields']));
    }

    $event->setApiRequest($apiRequest);
  }

  /**
   * @param \Civi\API\Event\Event $event
   *   API preparation event.
   *
   * @throws \Exception
   */
  public function onApiPrepare_validate(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] > 3) {
      return;
    }
    // Not sure why this is omitted for generic actions. It would make sense
    // to omit 'getfields', but that's only one generic action.

    if (isset($apiRequest['function']) && !$apiRequest['is_generic'] && isset($apiRequest['fields'])) {
      _civicrm_api3_validate_fields($apiRequest['entity'], $apiRequest['action'], $apiRequest['params'], $apiRequest['fields']);
      $event->setApiRequest($apiRequest);
    }
  }

  /**
   * Return array of defaults for the given API (function is a wrapper on getfields).
   * @param $fields
   * @return array
   */
  public function getDefaults($fields) {
    $defaults = [];

    foreach ($fields as $field => $values) {
      if (isset($values['api.default'])) {
        $defaults[$field] = $values['api.default'];
      }
    }
    return $defaults;
  }

  /**
   * Return array of required fields for the given API (function is a wrapper on getfields).
   * @param $fields
   * @return array
   */
  public function getRequired($fields) {
    $required = ['version'];

    foreach ($fields as $field => $values) {
      if (!empty($values['api.required'])) {
        $required[] = $field;
      }
    }
    return $required;
  }

}
