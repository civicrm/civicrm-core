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
use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\Message;
use Civi\Cxn\Rpc\UserError;
use Civi\Cxn\Rpc\Constants;

/**
 * Class RegistrationMessage
 *
 * A registration message is sent from a site to an app when it wants to
 * create or update a connection.
 *
 * The message includes two ciphertext blobs. The first is a single RSA
 * block representing the AES key. The second is AES-CBC+HMAC with the
 * real data. This will allow us to expand the registration data (i.e.
 * passing along more fields) without changing the protocol.
 *
 * Note: Crypt_RSA can encrypt oversized messages using an adhoc block
 * mode that smells like ECB. This doesn't compromise confidentiality,
 * but long messages could have their ciphertext spliced -- compromising
 * integrity.
 *
 * @package Civi\Cxn\Rpc\Message
 */
class RegistrationMessage extends Message {

  const NAME = 'CXN-0.2-RSA';

  protected $appId;
  protected $appPubKey;

  public function __construct($appId, $appPubKey, $data) {
    parent::__construct($data);
    $this->appId = $appId;
    $this->appPubKey = $appPubKey;
  }

  /**
   * @return string
   *   Ciphertext.
   */
  public function encode() {
    $secret = AesHelper::createSecret();

    $rsaCiphertext = self::getRsa($this->appPubKey, 'public')->encrypt($secret);
    if (strlen($rsaCiphertext) !== Constants::RSA_MSG_BYTES) {
      throw new InvalidMessageException("RSA ciphertext has incorrect length");
    }

    list($body, $signature) = AesHelper::encryptThenSign($secret, json_encode($this->data));

    return self::NAME
    . Constants::PROTOCOL_DELIM . $this->appId
    . Constants::PROTOCOL_DELIM . base64_encode($rsaCiphertext) // escape PROTOCOL_DELIM
    . Constants::PROTOCOL_DELIM . $signature
    . Constants::PROTOCOL_DELIM . $body;
  }

  /**
   * @param AppStoreInterface $appStore
   * @param string $blob
   * @return array
   *   Decoded data.
   */
  public static function decode($appStore, $blob) {
    $parts = explode(Constants::PROTOCOL_DELIM, $blob, 5);
    if (count($parts) != 5) {
      throw new InvalidMessageException('Invalid message: insufficient parameters');
    }
    list ($wireProt, $wireAppId, $rsaCiphertextB64, $signature, $body) = $parts;

    if ($wireProt !== self::NAME) {
      throw new InvalidMessageException('Invalid message: wrong protocol name');
    }

    $appPrivKey = $appStore->getPrivateKey($wireAppId);
    if (!$appPrivKey) {
      throw new InvalidMessageException('Received message intended for unknown app.');
    }

    $rsaCiphertext = base64_decode($rsaCiphertextB64);
    if (strlen($rsaCiphertext) !== Constants::RSA_MSG_BYTES) {
      throw new InvalidMessageException("RSA ciphertext has incorrect length");
    }

    $secret = UserError::adapt('Civi\Cxn\Rpc\Exception\InvalidMessageException', function () use ($rsaCiphertext, $appPrivKey) {
      return RegistrationMessage::getRsa($appPrivKey, 'private')->decrypt($rsaCiphertext);
    });
    if (empty($secret)) {
      throw new InvalidMessageException("Invalid message: decryption produced empty secret");
    }

    $plaintext = AesHelper::authenticateThenDecrypt($secret, $body, $signature);
    return json_decode($plaintext, TRUE);
  }

  /**
   * Quasi-private - marked public to work-around PHP 5.3 compat.
   *
   * @param string $key
   * @param string $type
   *   'public' or 'private'
   * @return \Crypt_RSA
   */
  public static function getRsa($key, $type) {
    $rsa = new \Crypt_RSA();
    $rsa->loadKey($key);
    if ($type == 'public') {
      $rsa->setPublicKey();
    }
    $rsa->setEncryptionMode(Constants::RSA_ENC_MODE);
    $rsa->setSignatureMode(Constants::RSA_SIG_MODE);
    $rsa->setHash(Constants::RSA_HASH);
    return $rsa;
  }

}
