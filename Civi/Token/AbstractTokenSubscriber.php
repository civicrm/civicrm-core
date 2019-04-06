<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
      Events::TOKEN_REGISTER => 'registerTokens',
      Events::TOKEN_EVALUATE => 'evaluateTokens',
      \Civi\ActionSchedule\Events::MAILING_QUERY => 'alterActionScheduleQuery',
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
   * @return mixed
   */
  abstract public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL);

}
