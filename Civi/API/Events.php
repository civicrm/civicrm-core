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
namespace Civi\API;

/**
 * The API kernel dispatches a series of events while processing each API request.
 * For a successful API request, the sequence is RESOLVE => AUTHORIZE => PREPARE => RESPOND.
 * If an exception arises in any stage, then the sequence is aborted and the EXCEPTION
 * event is dispatched.
 *
 * Event subscribers which are concerned about the order of execution should assign
 * a priority to their subscription (such as W_EARLY, W_MIDDLE, or W_LATE).
 */
class Events {

  /**
   * Determine whether the API request is allowed for the current user.
   * For successful execution, at least one listener must invoke
   * $event->authorize().
   *
   * @see AuthorizeEvent
   */
  const AUTHORIZE = 'civi.api.authorize';

  /**
   * Determine which API provider executes the given request. For successful
   * execution, at least one listener must invoke
   * $event->setProvider($provider).
   *
   * @see ResolveEvent
   */
  const RESOLVE = 'civi.api.resolve';

  /**
   * Apply pre-execution logic
   *
   * @see PrepareEvent
   */
  const PREPARE = 'civi.api.prepare';

  /**
   * Apply post-execution logic
   *
   * @see RespondEvent
   */
  const RESPOND = 'civi.api.respond';

  /**
   * Handle any exceptions.
   *
   * @see ExceptionEvent
   */
  const EXCEPTION = 'civi.api.exception';

  /**
   * Priority - Higher numbers execute earlier
   */
  const W_EARLY = 100;

  /**
   * Priority - Middle
   */
  const W_MIDDLE = 0;

  /**
   * Priority - Lower numbers execute later
   */
  const W_LATE = -100;

  /**
   * @return array<string>
   */
  public static function allEvents() {
    return [
      self::AUTHORIZE,
      self::EXCEPTION,
      self::PREPARE,
      self::RESOLVE,
      self::RESPOND,
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::eventDefs
   */
  public static function hookEventDefs($e) {
    $e->inspector->addEventClass(self::AUTHORIZE, 'Civi\API\Event\AuthorizeEvent');
    $e->inspector->addEventClass(self::EXCEPTION, 'Civi\API\Event\ExceptionEvent');
    $e->inspector->addEventClass(self::PREPARE, 'Civi\API\Event\PrepareEvent');
    $e->inspector->addEventClass(self::RESOLVE, 'Civi\API\Event\ResolveEvent');
    $e->inspector->addEventClass(self::RESPOND, 'Civi\API\Event\RespondEvent');
  }

}
