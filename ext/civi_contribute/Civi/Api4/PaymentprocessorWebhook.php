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
namespace Civi\Api4;

/**
 * Payment Processor Webhook entity.
 *
 * Tracks inbound payment processor webhook events queued for asynchronous
 * processing by the Job.process_paymentprocessor_webhooks scheduled job.
 *
 * @searchable secondary
 * @since 6.19
 * @package Civi\Api4
 */
class PaymentprocessorWebhook extends Generic\DAOEntity {

}
