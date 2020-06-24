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
   * @see AuthorizeEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const AUTHORIZE = 'civi.api.authorize';

  /**
   * @see ResolveEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const RESOLVE = 'civi.api.resolve';

  /**
   * @see PrepareEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const PREPARE = 'civi.api.prepare';

  /**
   * Apply post-execution logic
   *
   * @see RespondEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const RESPOND = 'civi.api.respond';

  /**
   * Handle any exceptions.
   *
   * @see ExceptionEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
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
      'civi.api.authorize',
      'civi.api.exception',
      'civi.api.prepare',
      'civi.api.resolve',
      'civi.api.respond',
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::eventDefs
   */
  public static function hookEventDefs($e) {
    $e->inspector->addEventClass('civi.api.authorize', 'Civi\API\Event\AuthorizeEvent');
    $e->inspector->addEventClass('civi.api.exception', 'Civi\API\Event\ExceptionEvent');
    $e->inspector->addEventClass('civi.api.prepare', 'Civi\API\Event\PrepareEvent');
    $e->inspector->addEventClass('civi.api.resolve', 'Civi\API\Event\ResolveEvent');
    $e->inspector->addEventClass('civi.api.respond', 'Civi\API\Event\RespondEvent');
  }

}
