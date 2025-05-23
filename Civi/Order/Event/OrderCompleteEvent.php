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

namespace Civi\Order\Event;

use Civi\Core\Event\GenericHookEvent;

/**
 * Class OrderCompleteEvent
 *
 * @package Civi\Order\Event
 */
class OrderCompleteEvent extends GenericHookEvent {

  /**
   * The Contribution ID that was Completed
   *
   * @var int
   */
  public int $contributionID;

  /**
   * Optional array of additional parameters that can be passed in.
   * We needed this for the "effectiveDate" parameter because it's currently too hard to work out if it's actually needed
   *   and would otherwise stop us from adding the OrderCompleteEvent.
   * It should be assumed that accepted parameters may change in the future or be removed altogether.
   * All parameters are passed through an internal function and deprecated warnings emitted if unknown parameters are passed in.
   * Currently we ONLY support:
   *   - effective_date: If provided, specify an alternative date to use as "today" calculation of membership dates
   *
   * @var array
   */
  public array $params = [];

  /**
   * Class constructor
   *
   * @param int $contributionID
   * @param array $params
   */
  public function __construct(int $contributionID, array $params = []) {
    $this->contributionID = $contributionID;
    $this->params = $this->validateParams($params);
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->contributionID, $this->params];
  }

  /**
   * Allow us to strictly limit (and deprecate/introduce) accepted parameters
   *
   * @param array $params
   *
   * @return array
   */
  private function validateParams(array $params): array {
    $paramsWhitelist = [
      'effective_date',
    ];

    foreach ($params as $key => $value) {
      if (!in_array($key, $paramsWhitelist)) {
        unset($params[$key]);
        \CRM_Core_Error::deprecatedWarning('OrderCompleteEvent does not support param: ' . $key);
      }
    }
    return $params;
  }

}
