<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc\Message;

use Civi\Cxn\Rpc\Exception\InvalidMessageException;
use Civi\Cxn\Rpc\Message;

/**
 * Class GarbledMessage
 *
 * A garbled message is one that lacks a proper prefix.
 *
 * This is common if the other end is using PHP and encounters a PHP error;
 * PHP's debug output gets plopped into our pretty data stream.
 *
 * It may actually be possible to disregard PHP's error output by
 * searching for prefix+delimiter... another day...
 *
 * @package Civi\Cxn\Rpc\Message
 */
class GarbledMessage extends Message {
  const NAME = 'CXN-0.2-GARBLED';

  public function encode() {
    throw new \RuntimeException("Why would you intentionally encode a garbled message this way?");
  }

  /**
   * @param string $message
   * @return InsecureMessage
   * @throws InvalidMessageException
   */
  public static function decode($message) {
    return new GarbledMessage($message);
  }

}
