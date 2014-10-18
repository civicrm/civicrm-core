<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TransactionSubscriber
 * @package Civi\API\Subscriber
 */
class TransactionSubscriber implements EventSubscriberInterface {
  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return array(
      Events::PREPARE => array('onApiPrepare', Events::W_EARLY),
      Events::RESPOND => array('onApiRespond', Events::W_MIDDLE),
      Events::EXCEPTION => array('onApiException', Events::W_EARLY),
    );
  }

  /**
   * @var array (scalar $apiRequestId => CRM_Core_Transaction $tx)
   */
  private $transactions = array();

  /**
   * Determine if an API request should be treated as transactional
   *
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   * @param array $apiRequest
   * @return bool
   */
  public function isTransactional($apiProvider, $apiRequest) {
    if (isset($apiRequest['params']['is_transactional'])) {
      return \CRM_Utils_String::strtobool($apiRequest['params']['is_transactional']);
    }
    return strtolower($apiRequest['action']) == 'create' || strtolower($apiRequest['action']) == 'delete' || strtolower($apiRequest['action']) == 'submit';
  }

  /**
   * Open a new transaction instance (if appropriate in the current policy)
   *
   * @param \Civi\API\Event\PrepareEvent $event
   */
  function onApiPrepare(\Civi\API\Event\PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($this->isTransactional($event->getApiProvider(), $apiRequest)) {
      $this->transactions[$apiRequest['id']] = new \CRM_Core_Transaction();
    }
  }

  /**
   * Close any pending transactions
   */
  function onApiRespond(\Civi\API\Event\RespondEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (isset($this->transactions[$apiRequest['id']])) {
      if (civicrm_error($event->getResponse())) {
        $this->transactions[$apiRequest['id']]->rollback();
      }
      unset($this->transactions[$apiRequest['id']]);
    }
  }

  /**
   * Rollback the pending transaction
   *
   * @param \Civi\API\Event\ExceptionEvent $event
   */
  function onApiException(\Civi\API\Event\ExceptionEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (isset($this->transactions[$apiRequest['id']])) {
      $this->transactions[$apiRequest['id']]->rollback();
      unset($this->transactions[$apiRequest['id']]);
    }
  }
}
