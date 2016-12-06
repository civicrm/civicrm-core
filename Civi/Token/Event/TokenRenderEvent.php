<?php
namespace Civi\Token\Event;

/**
 * Class TokenRenderEvent
 * @package Civi\Token\Event
 *
 * A TokenRenderEvent is fired after the TokenProcessor has rendered
 * a message.
 *
 * The render event may be used for post-processing the text, but
 * it's very difficult to do substantive work in a secure, robust
 * way within this event. The event primarily exists to facilitate
 * a transition of some legacy code.
 */
class TokenRenderEvent extends TokenEvent {

  /**
   * @var array|\ArrayAccess
   */
  public $context;

  /**
   * @var array|\ArrayAccess
   *
   * The original message template.
   */
  public $message;

  /**
   * @var \Civi\Token\TokenRow
   *
   * The record for which we're generating date
   */
  public $row;

  /**
   * @var string
   *
   * The rendered string, with tokens replaced.
   */
  public $string;

}
