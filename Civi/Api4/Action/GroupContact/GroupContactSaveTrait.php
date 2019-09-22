<?php

namespace Civi\Api4\Action\GroupContact;

/**
 * @inheritDoc
 *
 * @method $this setMethod(string $method) Indicate who added/removed the group.
 * @method string getMethod()
 * @method $this setTracking(string $tracking) Specify ip address or other tracking info.
 * @method string getTracking()
 */
trait GroupContactSaveTrait {

  /**
   * String to indicate who added/removed the group.
   *
   * @var string
   */
  protected $method = 'API';

  /**
   * IP address or other tracking info about who performed this group subscription.
   *
   * @var string
   */
  protected $tracking = '';

  /**
   * @inheritDoc
   */
  protected function writeObjects($items) {
    foreach ($items as &$item) {
      $item['method'] = $this->method;
      $item['tracking'] = $this->tracking;
    }
    return parent::writeObjects($items);
  }

}
