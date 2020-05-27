<?php
namespace Civi\Token\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class TokenListEvent
 * @package Civi\Token\Event
 */
class TokenEvent extends Event {

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
