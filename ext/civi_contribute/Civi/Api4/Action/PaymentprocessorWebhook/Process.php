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

namespace Civi\Api4\Action\PaymentprocessorWebhook;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\PaymentprocessorWebhook;
use Civi\Payment\PaymentProcessorWebhookInterface;

/**
 * Process queued PaymentprocessorWebhook events. Called by the
 * "Process PaymentProcessor Webhooks" scheduled job.
 */
class Process extends AbstractAction {

  /**
   * Delete records older than this (relative date string). Specify NULL or
   * an empty string to disable.
   *
   * @var string|null
   */
  protected ?string $deleteOld = '-3 month';

  /**
   * Force processing of a single record by ID, ignoring the status/
   * processed_date fields. For debugging.
   *
   * @var int|null
   */
  protected ?int $id = NULL;

  /**
   * Force processing of a single record by event ID, ignoring the status/
   * processed_date fields. For debugging.
   *
   * @var string|null
   */
  protected ?string $eventId = NULL;

  /**
   * Stop processing once this many seconds have elapsed since the job
   * started. Useful if your cron is http initiated.
   *
   * @var int
   */
  protected int $timeLimit = 3600;

  /**
   * Maximum number of webhook events to process in one run. Too many events
   * can cause memory issues and lock the database for too long.
   *
   * @var int
   */
  protected int $queueLimit = 1000;

  public function _run(Result $result) {
    $deletedCount = 0;
    if (!empty($this->deleteOld)) {
      // Delete all locally recorded webhooks older than this.
      $deletedCount = PaymentprocessorWebhook::get(FALSE)
        ->selectRowCount()
        ->addWhere('payment_processor_id.domain_id', '=', \CRM_Core_Config::domainID())
        ->addWhere('created_date', '<', $this->deleteOld)
        ->execute()
        ->count();
      if (!empty($deletedCount)) {
        PaymentprocessorWebhook::delete(FALSE)
          ->addWhere('payment_processor_id.domain_id', '=', \CRM_Core_Config::domainID())
          ->addWhere('created_date', '<', $this->deleteOld)
          ->execute();
      }
    }

    // Get the Webhook Events to process.
    // This is domain specific (as entities such as membershipType are domain-specific we must process per-domain).
    $paymentProcessorWebhooks = PaymentprocessorWebhook::get(FALSE)
      ->addWhere('payment_processor_id.domain_id', '=', \CRM_Core_Config::domainID());

    if (!empty($this->id)) {
      // Allow to force processing of a single record.
      $paymentProcessorWebhooks->addWhere('id', '=', $this->id);
    }
    elseif (!empty($this->eventId)) {
      $paymentProcessorWebhooks->addWhere('event_id', '=', $this->eventId);
    }
    else {
      $paymentProcessorWebhooks
        ->addWhere('processed_date', 'IS NULL')
        ->addWhere('status', '=', 'new')
        ->setLimit($this->queueLimit);
    }
    $paymentProcessorWebhooksResult = $paymentProcessorWebhooks->execute();

    $results = [
      'queue_count' => $paymentProcessorWebhooksResult->count(),
      'deleted' => $deletedCount,
      'processed' => 0,
      'successes' => 0,
      'errors' => 0,
    ];
    $eventsToProcess = [];
    if ($results['queue_count'] > 0) {
      $eventsToProcess = $paymentProcessorWebhooksResult->column('id');
      PaymentprocessorWebhook::update(FALSE)
        ->addWhere('id', 'IN', $eventsToProcess)
        ->addValue('status', 'processing')
        ->execute();
    }

    // When should we stop processing?
    $timeLimit = $this->timeLimit + microtime(TRUE);

    foreach ($paymentProcessorWebhooksResult as $webhookEvent) {
      $paymentProcessor = \Civi\Payment\System::singleton()
        ->getById($webhookEvent['payment_processor_id']);

      $implementsInterface = $paymentProcessor instanceof PaymentProcessorWebhookInterface;
      if ($paymentProcessor && ($implementsInterface || method_exists($paymentProcessor, 'processWebhookEvent'))) {
        // See PaymentProcessorWebhookInterface::processWebhookEvent() for what
        // implementations are responsible for.
        $eventResult = $paymentProcessor->processWebhookEvent($webhookEvent);
        $results[$eventResult ? 'successes' : 'errors']++;
      }
      else {
        // $paymentProcessor can be NULL if payment_processor_id no longer resolves to a
        // loadable processor (e.g. it, or its extension, was removed after this event was queued).
        \Civi::log()->warning('Not processing webhook event because payment processor could not be loaded or does not implement processWebhookEvent. Details: ' . print_r($webhookEvent, TRUE));
      }

      $results['processed']++;
      if ($results['processed'] < $results['queue_count'] && microtime(TRUE) > $timeLimit) {
        $results['note'] = 'Stopped processing as time limit exceeded.';
        // Release the 'processing' status for any that we did not complete.
        PaymentprocessorWebhook::update(FALSE)
          ->addWhere('id', 'IN', $eventsToProcess)
          ->addWhere('status', '=', 'processing')
          ->addValue('status', 'new')
          ->execute();
        break;
      }
    }

    $result->exchangeArray($results);
  }

}
