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

namespace Civi\Payment;

/**
 * Contract for payment processors that receive webhooks.
 *
 * Implement this on the CRM_Core_Payment subclass (not a helper/IPN class),
 * since it is that instance which CRM_Core_Payment::handlePaymentMethod()
 * dispatches to.
 *
 * @since 6.19
 */
interface PaymentProcessorWebhookInterface {

  /**
   * Handle an inbound webhook HTTP request.
   *
   * Called by CRM_Core_Payment::handleIPN() for requests to
   * civicrm/payment/ipn/<processor_id>. Implementations are responsible for
   * verifying the request's authenticity, parsing it, emitting the HTTP
   * response code, and either processing the event immediately or queuing
   * it for later processing (e.g. via a scheduled job).
   *
   * This method must always return control to the caller rather than
   * calling CRM_Utils_System::civiExit()/exit()/die() itself - handleIPN()
   * is responsible for firing the postIPNProcess hook and exiting once all
   * matching processor instances have run.
   */
  public function handlePaymentNotification(): void;

  /**
   * Process one previously-queued webhook event.
   *
   * Implementations must be idempotent, since a webhook event can be
   * delivered - and therefore queued - more than once. Implementations
   * have responsibility to:
   *
   * - attempt to process the event.
   * - catch expected and unexpected exceptions, and handle appropriately.
   * - update the stored $webhookEvent to error|success, optionally
   *   providing a message.
   * - return TRUE for success, FALSE for error.
   *
   * @param array $webhookEvent
   *   The full PaymentprocessorWebhook entity row (id, payment_processor_id,
   *   event_id, trigger, identifier, status, message, data, ...) - this
   *   shape is prescribed by core; only the "data" key's contents are
   *   processor-specific.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function processWebhookEvent(array $webhookEvent): bool;

}
