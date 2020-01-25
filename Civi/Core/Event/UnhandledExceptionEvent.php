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

namespace Civi\Core\Event;

/**
 * Class UnhandledExceptionEvent
 * @package Civi\API\Event
 */
class UnhandledExceptionEvent extends GenericHookEvent {

  /**
   * @var \Exception
   */
  public $exception;

  /**
   * Reserved for future use.
   *
   * @var mixed
   */
  public $request;

  /**
   * @param $e
   * @param $request
   */
  public function __construct($e, $request) {
    $this->request = $request;
    $this->exception = $e;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->exception, $this->request];
  }

}
