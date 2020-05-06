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
use Civi\API\Event\AuthorizeEvent;
use Civi\API\Event\RespondEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The WhitelistSubscriber listens to API requests and matches them against
 * a whitelist of allowed API calls. If an API call does NOT appear in the
 * whitelist, then it generates an error.
 *
 * @package Civi
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class WhitelistSubscriber implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.authorize' => ['onApiAuthorize', Events::W_EARLY],
      'civi.api.respond' => ['onApiRespond', Events::W_MIDDLE],
    ];
  }

  /**
   * Array(WhitelistRule).
   *
   * @var array
   */
  protected $rules;

  /**
   * Array (scalar $reqId => WhitelistRule $rule).
   *
   * @var array
   */
  protected $activeRules;

  /**
   * @param array $rules
   *   Array of WhitelistRule.
   * @see WhitelistRule
   * @throws \CRM_Core_Exception
   */
  public function __construct($rules) {
    $this->rules = [];
    foreach ($rules as $rule) {
      /** @var \Civi\API\WhitelistRule $rule */
      if ($rule->isValid()) {
        $this->rules[] = $rule;
      }
      else {
        throw new \CRM_Core_Exception("Invalid rule");
      }
    }
  }

  /**
   * Determine which, if any, whitelist rules apply this request.
   * Reject unauthorized requests.
   *
   * @param \Civi\API\Event\AuthorizeEvent $event
   * @throws \CRM_Core_Exception
   */
  public function onApiAuthorize(AuthorizeEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (empty($apiRequest['params']['check_permissions']) || $apiRequest['params']['check_permissions'] !== 'whitelist') {
      return;
    }
    foreach ($this->rules as $rule) {
      if (TRUE === $rule->matches($apiRequest)) {
        $this->activeRules[$apiRequest['id']] = $rule;
        return;
      }
    }
    throw new \CRM_Core_Exception('The request does not match any active API authorizations.');
  }

  /**
   * Apply any filtering rules based on the chosen whitelist rule.
   * @param \Civi\API\Event\RespondEvent $event
   */
  public function onApiRespond(RespondEvent $event) {
    $apiRequest = $event->getApiRequest();
    $id = $apiRequest['id'];
    if (isset($this->activeRules[$id])) {
      $event->setResponse($this->activeRules[$id]->filter($apiRequest, $event->getResponse()));
      unset($this->activeRules[$id]);
    }
  }

}
