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

use Civi\FlexMailer\Event\CheckSendableEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Validator
 * @package Civi\FlexMailer
 *
 * The *validator* determines whether a mailing is completely specified
 * (sendable). If not, delivery should be blocked.
 */
class Validator {

  const EVENT_CHECK_SENDABLE = 'civi.flexmailer.checkSendable';

  /**
   * @param array $params
   *   The mailing which may or may not be sendable.
   * @return array
   *   List of error messages.
   */
  public static function createAndRun(array $params): array {
    $mailing = new \CRM_Mailing_BAO_Mailing();
    $mailing->id = $params['id'] ?? NULL;
    if ($mailing->id) {
      $mailing->find(TRUE);
    }
    $mailing->copyValues($params);

    return (new Validator())->run([
      'mailing' => $mailing,
      'attachments' => \CRM_Core_BAO_File::getEntityFile('civicrm_mailing', $mailing->id),
    ]);
  }

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * FlexMailer constructor.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   */
  public function __construct(EventDispatcherInterface $dispatcher = NULL) {
    $this->dispatcher = $dispatcher ?: \Civi::service('dispatcher');
  }

  /**
   * @param array $context
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - attachments: array
   * @return array
   *   List of error messages.
   *   Ex: array('subject' => 'The Subject field is blank').
   *   Example keys: 'subject', 'name', 'from_name', 'from_email', 'body', 'body_html:unsubscribeUrl'.
   */
  public function run($context) {
    $checkSendable = new CheckSendableEvent($context);
    $this->dispatcher->dispatch(static::EVENT_CHECK_SENDABLE, $checkSendable);
    return $checkSendable->getErrors();
  }

}
