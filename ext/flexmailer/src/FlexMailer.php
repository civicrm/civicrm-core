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
namespace Civi\FlexMailer;

use Civi\FlexMailer\Event\ComposeBatchEvent;
use Civi\FlexMailer\Event\RunEvent;
use Civi\FlexMailer\Event\SendBatchEvent;
use Civi\FlexMailer\Event\WalkBatchesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class FlexMailer
 * @package Civi\FlexMailer
 *
 * The FlexMailer is a mail-blaster which supports batching and events.
 * Specifically, there are five key events:
 *   - WalkBatchesEvent: Examine the recipient list and pull out a subset
 *     for whom you want to send email.
 *   - ComposeBatchEvent: Given a batch of recipients, prepare an email message
 *     for each.
 *   - SendBatchEvent: Given a batch of recipients and their  messages, send
 *     the messages out.
 *   - RunEvent: Execute the main-loop (with all of the above steps).
 *
 * The events are based on Symfony's EventDispatcher. You may register event
 * listeners using hook_civicrm_container, e.g.
 *
 * function mymod_civicrm_container(ContainerBuilder $container) {
 *    $container
 *       ->setDefinition('mymod_subscriber', new Definition('MymodSubscriber', array()))
 *       ->addTag('kernel.event_subscriber');
 * }
 *
 * FlexMailer includes default listeners for all of these events. They
 * behaves in basically the same way as CiviMail's traditional BAO-based
 * delivery system (respecting mailerJobSize, mailThrottleTime,
 * mailing_backend, hook_civicrm_alterMailParams, etal). However, you
 * can replace any of the major functions, e.g.
 *
 *  - If you send large blasts across multiple servers, then you may
 *    prefer a different algorithm for splitting the recipient list.
 *    Listen for WalkBatchesEvent.
 *  - If you want to compose messages in a new way (e.g. a different
 *    templating language), then listen for ComposeBatchEvent.
 *  - If you want to deliver messages through a different medium
 *    (such as web-services or batched SMTP), listen for SendBatchEvent.
 *
 * In all cases, your function can listen to the event and then decide what
 * to do. If your listener does the work required for the event, then
 * you can disable the default listener by calling `$event->stopPropagation()`.
 *
 * @link http://symfony.com/doc/current/components/event_dispatcher.html
 */
class FlexMailer {

  const WEIGHT_START = 2000;
  const WEIGHT_PREPARE = 1000;
  const WEIGHT_MAIN = 0;
  const WEIGHT_ALTER = -1000;
  const WEIGHT_END = -2000;

  const EVENT_RUN = 'civi.flexmailer.run';
  const EVENT_WALK = 'civi.flexmailer.walk';
  const EVENT_COMPOSE = 'civi.flexmailer.compose';
  const EVENT_SEND = 'civi.flexmailer.send';

  /**
   * @return array
   *   Array(string $event => string $class).
   */
  public static function getEventTypes() {
    return [
      self::EVENT_RUN => 'Civi\\FlexMailer\\Event\\RunEvent',
      self::EVENT_WALK => 'Civi\\FlexMailer\\Event\\WalkBatchesEvent',
      self::EVENT_COMPOSE => 'Civi\\FlexMailer\\Event\\ComposeBatchEvent',
      self::EVENT_SEND => 'Civi\\FlexMailer\\Event\\SendBatchEvent',
    ];
  }

  /**
   * @var array
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - job: \CRM_Mailing_BAO_MailingJob
   *     - attachments: array
   *     - is_preview: bool
   *
   * Additional options may be passed. To avoid naming conflicts, use prefixing.
   */
  public $context;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * Create a new FlexMailer instance, using data available in the CiviMail runJobs().
   *
   * @param \CRM_Mailing_BAO_MailingJob $job
   * @param object $deprecatedMessageMailer
   * @param array $deprecatedTestParams
   * @return bool
   *   TRUE if delivery completed.
   */
  public static function createAndRun($job, $deprecatedMessageMailer, $deprecatedTestParams) {
    $flexMailer = new \Civi\FlexMailer\FlexMailer([
      'mailing' => \CRM_Mailing_BAO_Mailing::findById($job->mailing_id),
      'job' => $job,
      'attachments' => \CRM_Core_BAO_File::getEntityFile('civicrm_mailing', $job->mailing_id),
      'deprecatedMessageMailer' => $deprecatedMessageMailer,
      'deprecatedTestParams' => $deprecatedTestParams,
    ]);
    return $flexMailer->run();
  }

  /**
   * FlexMailer constructor.
   * @param array $context
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - job: \CRM_Mailing_BAO_MailingJob
   *     - attachments: array
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   */
  public function __construct($context = [], EventDispatcherInterface $dispatcher = NULL) {
    $this->context = $context;
    $this->dispatcher = $dispatcher ?: \Civi::service('dispatcher');
  }

  /**
   * @return bool
   *   TRUE if delivery completed.
   * @throws \CRM_Core_Exception
   */
  public function run() {
    // PHP 5.3
    $flexMailer = $this;

    if (count($this->validate()) > 0) {
      throw new \CRM_Core_Exception("FlexMailer cannot execute: invalid context");
    }

    $run = $this->fireRun();
    if ($run->isPropagationStopped()) {
      return $run->getCompleted();
    }

    $walkBatches = $this->fireWalkBatches(function ($tasks) use ($flexMailer) {
      $flexMailer->fireComposeBatch($tasks);
      $sendBatch = $flexMailer->fireSendBatch($tasks);
      return $sendBatch->getCompleted();
    });

    return $walkBatches->getCompleted();
  }

  /**
   * @return array
   *   List of error messages
   */
  public function validate() {
    $errors = [];
    if (empty($this->context['mailing'])) {
      $errors['mailing'] = 'Missing \"mailing\"';
    }
    if (empty($this->context['job'])) {
      $errors['job'] = 'Missing \"job\"';
    }
    return $errors;
  }

  /**
   * @return \Civi\FlexMailer\Event\RunEvent
   */
  public function fireRun() {
    $event = new RunEvent($this->context);
    $this->dispatcher->dispatch(self::EVENT_RUN, $event);
    return $event;
  }

  /**
   * @param callable $onVisitBatch
   * @return \Civi\FlexMailer\Event\WalkBatchesEvent
   */
  public function fireWalkBatches($onVisitBatch) {
    $event = new WalkBatchesEvent($this->context, $onVisitBatch);
    $this->dispatcher->dispatch(self::EVENT_WALK, $event);
    return $event;
  }

  /**
   * @param array<FlexMailerTask> $tasks
   * @return \Civi\FlexMailer\Event\ComposeBatchEvent
   */
  public function fireComposeBatch($tasks) {
    // This isn't a great place for this, but it ensures consistent cleanup.
    $mailing = $this->context['mailing'];
    if (property_exists($mailing, 'language') && $mailing->language && $mailing->language != 'en_US') {
      $swapLang = \CRM_Utils_AutoClean::swap('call://i18n/getLocale', 'call://i18n/setLocale', $mailing->language);
    }

    $event = new ComposeBatchEvent($this->context, $tasks);
    $this->dispatcher->dispatch(self::EVENT_COMPOSE, $event);
    return $event;
  }

  /**
   * @param array<FlexMailerTask> $tasks
   * @return \Civi\FlexMailer\Event\SendBatchEvent
   */
  public function fireSendBatch($tasks) {
    $event = new SendBatchEvent($this->context, $tasks);
    $this->dispatcher->dispatch(self::EVENT_SEND, $event);
    return $event;
  }

}
