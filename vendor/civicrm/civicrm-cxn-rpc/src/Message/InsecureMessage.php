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
use Civi\Cxn\Rpc\Constants;
use Civi\Cxn\Rpc\Message;

/**
 * Class InsecureMessage
 *
 * An insecure message is one that cannot be authenticated. It may be useful
 * for reporting low-level connection errors (e.g. due to misconfigured
 * crypto). Clients which receive InsecureMessages should treat them with
 * suspicion because they can be forged by a man-in-the-middle.
 *
 * @package Civi\Cxn\Rpc\Message
 */
class InsecureMessage extends Message {
  const NAME = 'CXN-0.2-INSECURE';

  /**
   * @return string
   */
  public function encode() {
    return self::NAME . Constants::PROTOCOL_DELIM . json_encode($this->data);
  }

  /**
   * @param string $message
   * @return InsecureMessage
   * @throws InvalidMessageException
   */
  public static function decode($message) {
    list ($parsedProt, $parsedJson) = explode(Constants::PROTOCOL_DELIM, $message, 2);
    if ($parsedProt != self::NAME) {
      throw new InvalidMessageException('Incorrect coding. Expected: ' . self::NAME);
    }
    $data = json_decode($parsedJson, TRUE);
    if (!$data) {
      throw new InvalidMessageException("Invalid message");
    }
    return new InsecureMessage($data);
  }

}
