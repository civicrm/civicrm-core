<?php
namespace Civi\Token\Event;

use Civi\Core\Event\GenericHookEvent;

/**
 * Class TokenListEvent
 * @package Civi\Token\Event
 */
class TokenEvent extends GenericHookEvent {

  protected $tokenProcessor;

  public function __construct($tokenProcessor) {
    $this->tokenProcessor = $tokenProcessor;
  }

  /**
   * @return \Civi\Token\TokenProcessor
   */
  public function getTokenProcessor() {
    return $this->tokenProcessor;
  }

}
