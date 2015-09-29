<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
namespace Civi\API\Subscriber;

use Civi\API\Events;
use Civi\API\Event\AuthorizeEvent;
use Civi\API\Event\RespondEvent;
use Civi\API\WhitelistRule;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The WhitelistSubscriber listens to API requests and matches them against
 * a whitelist of allowed API calls. If an API call does NOT appear in the
 * whitelist, then it generates an error.
 *
 * @package Civi
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class WhitelistSubscriber implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return array(
      Events::AUTHORIZE => array('onApiAuthorize', Events::W_EARLY),
      Events::RESPOND => array('onApiRespond', Events::W_MIDDLE),
    );
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
   */
  public function __construct($rules) {
    $this->rules = array();
    foreach ($rules as $rule) {
      /** @var WhitelistRule $rule */
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
   * @param AuthorizeEvent $event
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
   * @param RespondEvent $event
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
