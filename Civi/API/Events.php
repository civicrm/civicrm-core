<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
namespace Civi\API;

/**
 * The API kernel dispatches a series of events while processing each API request.
 * For a successful API request, the sequence is RESOLVE => AUTHORIZE => PREPARE => RESPOND.
 * If an exception arises in any stage, then the sequence is aborted and the EXCEPTION
 * event is dispatched.
 *
 * Event subscribers which are concerned about the order of execution should assign
 * a weight to their subscription (such as W_EARLY, W_MIDDLE, or W_LATE).
 * W_LATE).
 */
class Events {

  /**
   * Determine whether the API request is allowed for the current user.
   * For successful execution, at least one listener must invoke
   * $event->authorize().
   *
   * @see AuthorizeEvent
   */
  const AUTHORIZE = 'api.authorize';

  /**
   * Determine which API provider executes the given request. For successful
   * execution, at least one listener must invoke
   * $event->setProvider($provider).
   *
   * @see ResolveEvent
   */
  const RESOLVE = 'api.resolve';

  /**
   * Apply pre-execution logic
   *
   * @see PrepareEvent
   */
  const PREPARE = 'api.prepare';

  /**
   * Apply post-execution logic
   *
   * @see RespondEvent
   */
  const RESPOND = 'api.respond';

  /**
   * Handle any exceptions.
   *
   * @see ExceptionEvent
   */
  const EXCEPTION = 'api.exception';

  /**
   * Weight - Early
   */
  const W_EARLY = 100;

  /**
   * Weight - Middle
   */
  const W_MIDDLE = 0;

  /**
   * Weight - Late
   */
  const W_LATE = -100;

  /**
   * @return array<string>
   */
  public static function allEvents() {
    return array(
      self::AUTHORIZE,
      self::EXCEPTION,
      self::PREPARE,
      self::RESOLVE,
      self::RESPOND,
    );
  }

}
