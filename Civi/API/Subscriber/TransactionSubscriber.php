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

class TransactionSubscriber implements EventSubscriberInterface {
  public static function getSubscribedEvents() {
    return array(
      Events::PREPARE => array('onApiPrepare', Events::W_EARLY),
      Events::RESPOND => array('onApiRespond', Events::W_MIDDLE),
      Events::EXCEPTION => array('onApiException', Events::W_EARLY),
    );
  }

  /**
   * @var array<\CRM_Core_Transaction>
   */
  private $stack = array();

  /**
   * Open a new transaction instance (if appropriate in the current policy)
   *
   * @param \Civi\API\Event\Event $event
   */
  function onApiPrepare(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();
    if (strtolower($apiRequest['action']) == 'create' || strtolower($apiRequest['action']) == 'delete' || strtolower($apiRequest['action']) == 'submit') {
      $apiRequest['is_transactional'] = 1;

      $this->stack[] = new \CRM_Core_Transaction();
    } else {
      $this->stack[] = NULL;
    }
  }

  /**
   * Close any pending transactions
   */
  function onApiRespond() {
    array_pop($this->stack);
  }

  /**
   * Rollback the pending transaction
   *
   * @param \Civi\API\Event\ExceptionEvent $event
   */
  function onApiException(\Civi\API\Event\ExceptionEvent $event) {
    $transaction = array_pop($this->stack);
    if ($transaction !== NULL) {
      $transaction->rollback();
    }
  }
}