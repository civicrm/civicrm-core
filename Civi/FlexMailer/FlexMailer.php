<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
namespace Civi\FlexMailer;
use Civi\FlexMailer\Event\AlterBatchEvent;
use Civi\FlexMailer\Event\ComposeBatchEvent;
use Civi\FlexMailer\Event\RunEvent;
use Civi\FlexMailer\Event\SendBatchEvent;
use Civi\FlexMailer\Event\WalkBatchesEvent;
use Civi\FlexMailer\Listener\DefaultBatcher;
use Civi\FlexMailer\Listener\DefaultComposer;
use Civi\FlexMailer\Listener\DefaultSender;
use Civi\FlexMailer\Listener\HookAdapter;
use Civi\FlexMailer\Listener\OpenTracker;
use \Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
 *   - AlterBatchEvent: Given a batch of recipients and their messages, change the
 *     content of the messages.
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
 *  - If you want to add extra email headers or tracking codes,
 *    then listen for AlterBatchEvent.
 *  - If you want to deliver messages through a different medium
 *    (such as web-services or batched SMTP), listen for SendBatchEvent.
 *
 * In all cases, your function can listen to the event and then decide what
 * to do. If your listener does the work required for the event, then
 * you can disable the default listener by calling `$event->stopPropagation()`.
 *
 * @see CRM_Utils_Hook::container
 * @see Civi\Core\Container
 * @see DefaultBatcher
 * @see DefaultComposer
 * @see DefaultSender
 * @see HookAdapter
 * @see OpenTracker
 * @link http://symfony.com/doc/current/components/event_dispatcher.html
 */
class FlexMailer {

  const EVENT_RUN = 'civi.flexmailer.run';
  const EVENT_WALK = 'civi.flexmailer.walk';
  const EVENT_COMPOSE = 'civi.flexmailer.compose';
  const EVENT_ALTER = 'civi.flexmailer.alter';
  const EVENT_SEND = 'civi.flexmailer.send';

  /**
   * @return array
   *   Array(string $event => string $class).
   */
  public static function getEventTypes() {
    return array(
      self::EVENT_RUN => 'Civi\\FlexMailer\\Event\\RunEvent',
      self::EVENT_WALK => 'Civi\\FlexMailer\\Event\\WalkBatchesEvent',
      self::EVENT_COMPOSE => 'Civi\\FlexMailer\\Event\\ComposeBatchEvent',
      self::EVENT_ALTER => 'Civi\\FlexMailer\\Event\\AlterBatchEvent',
      self::EVENT_SEND => 'Civi\\FlexMailer\\Event\\SendBatchEvent',
    );
  }

  /**
   * @var array
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - job: \CRM_Mailing_BAO_MailingJob
   *     - attachments: array
   *
   * Additional options may be passed. To avoid naming conflicts, use prefixing.
   */
  public $context;

  /**
   * @var EventDispatcherInterface
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
    $flexMailer = new \Civi\FlexMailer\FlexMailer(array(
      'mailing' => \CRM_Mailing_BAO_Mailing::findById($job->mailing_id),
      'job' => $job,
      'attachments' => \CRM_Core_BAO_File::getEntityFile('civicrm_mailing', $job->mailing_id),
      'deprecatedMessageMailer' => $deprecatedMessageMailer,
      'deprecatedTestParams' => $deprecatedTestParams,
    ));
    return $flexMailer->run();
  }

  /**
   * FlexMailer constructor.
   * @param array $context
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - job: \CRM_Mailing_BAO_MailingJob
   *     - attachments: array
   * @param EventDispatcherInterface $dispatcher
   */
  public function __construct($context = array(), EventDispatcherInterface $dispatcher = NULL) {
    $this->context = $context;
    $this->dispatcher = $dispatcher ? $dispatcher : \Civi::service('dispatcher');
  }

  /**
   * @return bool
   *   TRUE if delivery completed.
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $flexMailer = $this; // PHP 5.3

    if (count($this->validate()) > 0) {
      throw new \CRM_Core_Exception("FlexMailer cannot execute: invalid context");
    }

    $run = $this->fireRun();
    if ($run->isPropagationStopped()) {
      return $run->getCompleted();
    }

    $walkBatches = $this->fireWalkBatches(function ($tasks) use ($flexMailer) {
      $flexMailer->fireComposeBatch($tasks);
      $flexMailer->fireAlterBatch($tasks);
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
    $errors = array();
    if (empty($this->context['mailing'])) {
      $errors['mailing'] = 'Missing \"mailing\"';
    }
    if (empty($this->context['job'])) {
      $errors['job'] = 'Missing \"job\"';
    }
    return $errors;
  }

  /**
   * @return RunEvent
   */
  public function fireRun() {
    $event = new RunEvent($this->context);
    $this->dispatcher->dispatch(self::EVENT_RUN, $event);
    return $event;
  }

  /**
   * @param callable $onVisitBatch
   * @return WalkBatchesEvent
   */
  public function fireWalkBatches($onVisitBatch) {
    $event = new WalkBatchesEvent($this->context, $onVisitBatch);
    $this->dispatcher->dispatch(self::EVENT_WALK, $event);
    return $event;
  }

  /**
   * @param array<FlexMailerTask> $tasks
   * @return ComposeBatchEvent
   */
  public function fireComposeBatch($tasks) {
    $event = new ComposeBatchEvent($this->context, $tasks);
    $this->dispatcher->dispatch(self::EVENT_COMPOSE, $event);
    return $event;
  }

  /**
   * @param array<FlexMailerTask> $tasks
   * @return AlterBatchEvent
   */
  public function fireAlterBatch($tasks) {
    $event = new AlterBatchEvent($this->context, $tasks);
    $this->dispatcher->dispatch(self::EVENT_ALTER, $event);
    return $event;
  }

  /**
   * @param array<FlexMailerTask> $tasks
   * @return SendBatchEvent
   */
  public function fireSendBatch($tasks) {
    $event = new SendBatchEvent($this->context, $tasks);
    $this->dispatcher->dispatch(self::EVENT_SEND, $event);
    return $event;
  }

}
