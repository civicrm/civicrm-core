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

namespace Civi\Token;

use Civi\ActionSchedule\Event\MailingQueryEvent;
use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenValueEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AbstractTokenSubscriber
 * @package Civi\Token
 *
 * AbstractTokenSubscriber is a base class which may be extended to
 * implement tokens in a somewhat more concise fashion.
 *
 * To implement a new token handler based on this:
 *   1. Create a subclass.
 *   2. Override the constructor and set values for $entity and $tokenNames.
 *   3. Implement the evaluateToken() method.
 *   4. Optionally, override others:
 *      + checkActive()
 *      + getActiveTokens()
 *      + prefetch()
 *      + alterActionScheduleMailing()
 *   5. Register the new class with the event-dispatcher.
 *
 * Note: There's no obligation to use this base class. You could implement
 * your own class anew -- just subscribe the proper events.
 */
abstract class AbstractTokenSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.token.list' => 'registerTokens',
      'civi.token.eval' => 'evaluateTokens',
      'civi.actionSchedule.prepareMailingQuery' => 'alterActionScheduleQuery',
    ];
  }

  /**
   * @var string
   *   Ex: 'contact' or profile' or 'employer'
   */
  public $entity;

  /**
   * @var array
   *   List of tokens provided by this class
   *   Array(string $fieldName => string $label).
   */
  public $tokenNames;

  /**
   * @var array
   *   List of active tokens - tokens provided by this class and used in the message
   *   Array(string $tokenName);
   */
  public $activeTokens;

  /**
   * @param $entity
   * @param array $tokenNames
   *   Array(string $tokenName => string $label).
   */
  public function __construct($entity, $tokenNames = []) {
    $this->entity = $entity;
    $this->tokenNames = $tokenNames;
  }

  /**
   * Determine whether this token-handler should be used with
   * the given processor.
   *
   * To short-circuit token-processing in irrelevant contexts,
   * override this.
   *
   * @param \Civi\Token\TokenProcessor $processor
   * @return bool
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    return TRUE;
  }

  /**
   * Register the declared tokens.
   *
   * @param \Civi\Token\Event\TokenRegisterEvent $e
   *   The registration event. Add new tokens using register().
   */
  public function registerTokens(TokenRegisterEvent $e) {
    if (!$this->checkActive($e->getTokenProcessor())) {
      return;
    }
    foreach ($this->tokenNames as $name => $label) {
      $e->register([
        'entity' => $this->entity,
        'field' => $name,
        'label' => $label,
      ]);
    }
  }

  /**
   * Alter the query which prepopulates mailing data
   * for scheduled reminders.
   *
   * This is method is not always appropriate, but if you're specifically
   * focused on scheduled reminders, it can be convenient.
   *
   * @param \Civi\ActionSchedule\Event\MailingQueryEvent $e
   *   The pending query which may be modified. See discussion on
   *   MailingQueryEvent::$query.
   */
  public function alterActionScheduleQuery(MailingQueryEvent $e) {
  }

  /**
   * Populate the token data.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *   The event, which includes a list of rows and tokens.
   */
  public function evaluateTokens(TokenValueEvent $e) {
    if (!$this->checkActive($e->getTokenProcessor())) {
      return;
    }

    $this->activeTokens = $this->getActiveTokens($e);
    if (!$this->activeTokens) {
      return;
    }
    $prefetch = $this->prefetch($e);

    foreach ($e->getRows() as $row) {
      foreach ($this->activeTokens as $field) {
        $this->evaluateToken($row, $this->entity, $field, $prefetch);
      }
    }
  }

  /**
   * To handle variable tokens, override this function and return the active tokens.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @return mixed
   */
  public function getActiveTokens(TokenValueEvent $e) {
    $messageTokens = $e->getTokenProcessor()->getMessageTokens();
    if (!isset($messageTokens[$this->entity])) {
      return FALSE;
    }
    return array_intersect($messageTokens[$this->entity], array_keys($this->tokenNames));
  }

  /**
   * To perform a bulk lookup before rendering tokens, override this
   * function and return the prefetched data.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @return mixed
   */
  public function prefetch(TokenValueEvent $e) {
    return NULL;
  }

  /**
   * Evaluate the content of a single token.
   *
   * @param TokenRow $row
   *   The record for which we want token values.
   * @param string $entity
   *   The name of the token entity.
   * @param string $field
   *   The name of the token field.
   * @param mixed $prefetch
   *   Any data that was returned by the prefetch().
   */
  abstract public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL);

}
