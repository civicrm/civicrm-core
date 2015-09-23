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

use Civi\Cxn\Rpc\Exception\ExpiredCertException;
use Civi\Cxn\Rpc\Exception\InvalidCertException;

class X509Util {
  /**
   * @param string $certPem
   * @param array $keyPairPems
   *   Pair of PEM-encoded keys.
   * @param string $caCertPem
   * @return \File_X509
   */
  public static function loadCert($certPem, $keyPairPems = NULL, $caCertPem = NULL) {
    $certObj = new \File_X509();

    if (isset($caCertPem)) {
      $certObj->loadCA($caCertPem);
    }

    if ($certPem) {
      $certObj->loadX509($certPem);
    }

    if (isset($keyPairPems['privatekey'])) {
      $privKey = new \Crypt_RSA();
      $privKey->loadKey($keyPairPems['privatekey']);
      $certObj->setPrivateKey($privKey);
    }

    if (isset($keyPairPems['publickey'])) {
      $pubKey = new \Crypt_RSA();
      $pubKey->loadKey($keyPairPems['publickey']);
      $pubKey->setPublicKey();
      $certObj->setPublicKey($pubKey);
    }

    return $certObj;
  }

  /**
   * @param string $caCertPem
   *   PEM-encoded.
   * @param array $keyPair
   *   Pair of PEM-encoded keys.
   * @return \File_X509
   * @throws InvalidCertException
   */
  public static function loadCACert($caCertPem, $keyPair = NULL) {
    $certObj = self::loadCert($caCertPem, $keyPair, $caCertPem);
    $keyUsage = $certObj->getExtension('id-ce-keyUsage');
    if (!$keyUsage || !in_array('keyCertSign', $keyUsage)) {
      throw new InvalidCertException("CA certificate is not a CA certificate");
    }
    return $certObj;
  }

  /**
   * @param string $crlCertPem
   *   PEM-encoded.
   * @param array $keyPair
   *   Pair of PEM-encoded keys.
   * @param string $caCertPem
   *   PEM-encoded.
   * @return \File_X509
   * @throws InvalidCertException
   */
  public static function loadCrlDistCert($crlCertPem, $keyPair = NULL, $caCertPem = NULL) {
    $certObj = self::loadCert($crlCertPem, $keyPair, $caCertPem);
    $keyUsage = $certObj->getExtension('id-ce-keyUsage');
    if (!$keyUsage || !in_array('cRLSign', $keyUsage)) {
      throw new InvalidCertException("CRL-signing certificate is not a CRL-signing certificate");
    }
    return $certObj;
  }

}
