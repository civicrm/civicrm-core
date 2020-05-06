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
 * Class TransactionSubscriber
 *
 * Implement transaction management for API calls. Two API options are accepted:
 *  - is_transactional: bool|'nest' - if true, then all work is done inside a
 *    transaction. By default, true for mutator actions (C-UD). 'nest' will
 *    force creation of a nested transaction; otherwise, the default is to
 *    re-use any existing transactions.
 *  - options.force_rollback: bool - if true, all work is done in a nested
 *    transaction which will be rolled back.
 *
 * @package Civi\API\Subscriber
 */
class TransactionSubscriber implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => ['onApiPrepare', Events::W_EARLY],
      'civi.api.respond' => ['onApiRespond', Events::W_MIDDLE],
      'civi.api.exception' => ['onApiException', Events::W_EARLY],
    ];
  }

  /**
   * List of active transaction objects.
   *
   * array(scalar $apiRequestId => CRM_Core_Transaction $tx)
   *
   * @var array
   */
  private $transactions = [];

  /**
   * (Unused?) A list of requests which should be forcibly rolled back to
   * their save points.
   *
   * array (scalar $apiRequestId => bool)
   *
   * @var array
   */
  private $forceRollback = [];

  /**
   * Determine if an API request should be treated as transactional.
   *
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for this request.
   * @param array $apiRequest
   *   The full API request.
   * @return bool
   */
  public function isTransactional($apiProvider, $apiRequest) {
    if ($apiRequest['version'] == 4) {
      return FALSE;
    }
    if ($this->isForceRollback($apiProvider, $apiRequest)) {
      return TRUE;
    }
    if (isset($apiRequest['params']['is_transactional'])) {
      return \CRM_Utils_String::strtobool($apiRequest['params']['is_transactional']) || $apiRequest['params']['is_transactional'] == 'nest';
    }
    return strtolower($apiRequest['action']) == 'create' || strtolower($apiRequest['action']) == 'delete' || strtolower($apiRequest['action']) == 'submit';
  }

  /**
   * Determine if caller wants us to *always* rollback.
   *
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for this request.
   * @param array $apiRequest
   *   The full API request.
   * @return bool
   */
  public function isForceRollback($apiProvider, $apiRequest) {
    if ($apiRequest['version'] == 4) {
      return FALSE;
    }
    // FIXME: When APIv3 uses better parsing, only one check will be needed.
    if (isset($apiRequest['params']['options']['force_rollback'])) {
      return \CRM_Utils_String::strtobool($apiRequest['params']['options']['force_rollback']);
    }
    if (isset($apiRequest['options']['force_rollback'])) {
      return \CRM_Utils_String::strtobool($apiRequest['options']['force_rollback']);
    }
    return FALSE;
  }

  /**
   * Determine if caller wants a nested transaction or a re-used transaction.
   *
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for this request.
   * @param array $apiRequest
   *   The full API request.
   * @return bool
   *   True if a new nested transaction is required; false if active tx may be used
   */
  public function isNested($apiProvider, $apiRequest) {
    if ($this->isForceRollback($apiProvider, $apiRequest)) {
      return TRUE;
    }
    if (isset($apiRequest['params']['is_transactional']) && $apiRequest['params']['is_transactional'] === 'nest') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Open a new transaction instance (if appropriate in the current policy)
   *
   * @param \Civi\API\Event\PrepareEvent $event
   *   API preparation event.
   */
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($this->isTransactional($event->getApiProvider(), $apiRequest)) {
      $this->transactions[$apiRequest['id']] = new \CRM_Core_Transaction($this->isNested($event->getApiProvider(), $apiRequest));
    }
    if ($this->isForceRollback($event->getApiProvider(), $apiRequest)) {
      $this->transactions[$apiRequest['id']]->rollback();
    }
  }

  /**
   * Close any pending transactions.
   *
   * @param \Civi\API\Event\RespondEvent $event
   *   API response event.
   */
  public function onApiRespond(\Civi\API\Event\RespondEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (isset($this->transactions[$apiRequest['id']])) {
      if (civicrm_error($event->getResponse())) {
        $this->transactions[$apiRequest['id']]->rollback();
      }
      unset($this->transactions[$apiRequest['id']]);
    }
  }

  /**
   * Rollback the pending transaction.
   *
   * @param \Civi\API\Event\ExceptionEvent $event
   *   API exception event.
   */
  public function onApiException(\Civi\API\Event\ExceptionEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (isset($this->transactions[$apiRequest['id']])) {
      $this->transactions[$apiRequest['id']]->rollback();
      unset($this->transactions[$apiRequest['id']]);
    }
  }

}
