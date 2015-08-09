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

use Civi\Cxn\Rpc\AesHelper;
use Civi\Cxn\Rpc\Exception\InvalidMessageException;
use Civi\Cxn\Rpc\Message;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Civi\Cxn\Rpc\Constants;

class StdMessage extends Message {
  const NAME = 'CXN-0.2-AES-CBC-HMAC';

  protected $cxnId;
  protected $secret;

  /**
   * @param string $cxnId
   * @param string $secret
   *   Base64-encoded secret.
   * @param mixed $data
   *   Serializable data.
   */
  public function __construct($cxnId, $secret, $data) {
    parent::__construct($data);
    $this->cxnId = $cxnId;
    $this->secret = $secret;
  }

  /**
   * @return string
   * @throws InvalidMessageException
   */
  public function encode() {
    list($body, $signature) = AesHelper::encryptThenSign($this->secret, json_encode($this->data));
    return self::NAME // unsignable; determines decoder
    . Constants::PROTOCOL_DELIM . $this->cxnId // unsignable; determines key
    . Constants::PROTOCOL_DELIM . $signature
    . Constants::PROTOCOL_DELIM . $body;
  }

  /**
   * @param CxnStoreInterface $cxnStore
   *   A repository that contains shared secrets.
   * @param string $message
   *   Ciphertext.
   * @return static
   * @throws InvalidMessageException
   */
  public static function decode($cxnStore, $message) {
    list ($parsedProt, $parsedCxnId, $parsedHmac, $parsedBody) = explode(Constants::PROTOCOL_DELIM, $message, 4);
    if ($parsedProt != self::NAME) {
      throw new InvalidMessageException('Incorrect coding. Expected: ' . self::NAME);
    }
    $cxn = $cxnStore->getByCxnId($parsedCxnId);
    if (empty($cxn)) {
      throw new InvalidMessageException('Unknown connection ID');
    }

    $jsonPlaintext = AesHelper::authenticateThenDecrypt($cxn['secret'], $parsedBody, $parsedHmac);

    return new StdMessage($parsedCxnId, $cxn['secret'], json_decode($jsonPlaintext, TRUE));
  }

  /**
   * @return string
   */
  public function getCxnId() {
    return $this->cxnId;
  }

  /**
   * @param string $cxnId
   */
  public function setCxnId($cxnId) {
    $this->cxnId = $cxnId;
  }

  /**
   * @return string
   */
  public function getSecret() {
    return $this->secret;
  }

  /**
   * @param string $secret
   */
  public function setSecret($secret) {
    $this->secret = $secret;
  }


}
