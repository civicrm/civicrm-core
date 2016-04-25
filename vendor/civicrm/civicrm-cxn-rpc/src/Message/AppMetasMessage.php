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

use Civi\Cxn\Rpc\BinHex;
use Civi\Cxn\Rpc\CA;
use Civi\Cxn\Rpc\CertificateValidatorInterface;
use Civi\Cxn\Rpc\Exception\CxnException;
use Civi\Cxn\Rpc\Exception\InvalidMessageException;
use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\Message;
use Civi\Cxn\Rpc\UserError;
use Civi\Cxn\Rpc\Constants;
use Civi\Cxn\Rpc\Time;

/**
 * Class AppMetasMessage
 *
 * A signed collection of AppMetas.
 *
 * @package Civi\Cxn\Rpc\Message
 */
class AppMetasMessage extends Message {

  const NAME = 'CXN-0.2-APPS';

  protected $cert;
  protected $keyPair;

  public function __construct($cert, $keyPair, $data) {
    parent::__construct($data);
    $this->cert = $cert;
    $this->keyPair = $keyPair;
  }

  /**
   * @return string
   *   Ciphertext.
   */
  public function encode() {
    $envelope = json_encode(array(
      'ttl' => Time::getTime() + Constants::REQUEST_TTL,
      'r' => json_encode($this->data),
    ));
    $signature = self::getRsa($this->keyPair['privatekey'], 'private')->sign($envelope);
    if (empty($signature)) {
      throw new CxnException("Failed to compute signature");
    }
    return self::NAME . Constants::PROTOCOL_DELIM
    . $this->cert . Constants::PROTOCOL_DELIM
    . base64_encode($signature) . Constants::PROTOCOL_DELIM
    . $envelope;
  }

  /**
   * @param CertificateValidatorInterface|NULL $certValidator
   * @param string $blob
   * @return AppMetasMessage
   *   Validated message.
   * @throws InvalidMessageException
   */
  public static function decode($certValidator, $blob) {
    $parts = explode(Constants::PROTOCOL_DELIM, $blob, 4);
    if (count($parts) != 4) {
      throw new InvalidMessageException('Invalid message: insufficient parameters');
    }
    list ($wireProt, $wireCert, $wireSig, $wireEnvelope) = $parts;
    if ($wireProt != self::NAME) {
      throw new InvalidMessageException('Invalid message: wrong protocol name');
    }

    if ($certValidator !== NULL) {
      $certValidator->validateCert($wireCert);

      $wireCertX509 = new \File_X509();
      $wireCertX509->loadX509($wireCert);

      $cn = $wireCertX509->getDNProp('CN');
      if (count($cn) != 1 || $cn[0] != Constants::OFFICIAL_APPMETAS_CN) {
        throw new InvalidMessageException('Invalid message: signed by unauthorized party');
      }

      $isValid = UserError::adapt('Civi\Cxn\Rpc\Exception\InvalidMessageException', function () use ($wireCertX509, $wireEnvelope, $wireSig) {
        return AppMetasMessage::getRsaFromCert($wireCertX509)->verify($wireEnvelope, base64_decode($wireSig));
      });
      if (!$isValid) {
        throw new InvalidMessageException("Invalid message: incorrect signature");
      }
    }
    $envelope = json_decode($wireEnvelope, TRUE);
    if (empty($envelope)) {
      throw new InvalidMessageException("Invalid message: malformed envelope");
    }
    if (Time::getTime() > $envelope['ttl']) {
      throw new InvalidMessageException("Invalid message: expired");
    }
    return new AppMetasMessage($wireCert, NULL, json_decode($envelope['r'], TRUE));
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

  /**
   * Quasi-private - marked public to work-around PHP 5.3 compat.
   *
   * @param \File_X509 $x509
   * @return \Crypt_RSA
   */
  public static function getRsaFromCert($x509) {
    $rsa = $x509->getPublicKey();
    if (!$rsa) {
      throw new InvalidMessageException("Invalid message: certificate missing or does not have public key");
    }
    $rsa->setEncryptionMode(Constants::RSA_ENC_MODE);
    $rsa->setSignatureMode(Constants::RSA_SIG_MODE);
    $rsa->setHash(Constants::RSA_HASH);
    return $rsa;
  }

}
