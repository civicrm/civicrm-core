<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc;

use Civi\Cxn\Rpc\Exception\InvalidMessageException;
use Civi\Cxn\Rpc\Message\InsecureMessage;
use Civi\Cxn\Rpc\Message\RegistrationMessage;
use Civi\Cxn\Rpc\Message\StdMessage;

class Messages {
  protected $appId, $appPrivKey, $cxnStore;

  public function __construct($appId, $appPrivKey, $cxnStore) {
    $this->appId = $appId;
    $this->appPrivKey = $appPrivKey;
    $this->cxnStore = $cxnStore;
  }

  public function decode($formats, $message) {
    $prefixLen = 0;
    foreach ($formats as $format) {
      $prefixLen = max($prefixLen, strlen($format));
    }

    list($prefix) = explode(Constants::PROTOCOL_DELIM, substr($message, 0, $prefixLen + 1));
    if (!in_array($prefix, $formats)) {
      throw new InvalidMessageException("Unexpected message type.");
    }

    switch ($prefix) {
      case StdMessage::NAME:
        return StdMessage::decode($this->cxnStore, $message);

      case InsecureMessage::NAME:
        return InsecureMessage::decode($message);

      case RegistrationMessage::NAME:
        return RegistrationMessage::decode($this->appId, $this->appPrivKey, $message);

      default:
        throw new InvalidMessageException("Unrecognized message type");
    }
  }

}
