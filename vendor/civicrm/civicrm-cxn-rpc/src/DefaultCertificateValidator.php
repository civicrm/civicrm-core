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
use Civi\Cxn\Rpc\Http\HttpInterface;
use Civi\Cxn\Rpc\Http\PhpHttp;

/**
 * Class DefaultCertificateValidator
 * @package Civi\Cxn\Rpc
 *
 * The default certificate validator will:
 *  - Check that the certificate is signed by canonical CA.
 *  - Check that the certificate has not been revoked by the canonical CA
 *    (using the CRL URL of the CA).
 *
 * Validating the CRL requires issuing HTTP requests. To improve performance,
 * consider replacing the default $http instance (PhpHttp) with something
 * that supports caching.
 */
class DefaultCertificateValidator implements CertificateValidatorInterface {

  /**
   * Specify that content should be auto-loaded via HTTP.
   */
  const AUTOLOAD = '*auto*';

  /**
   * @var string
   *   The CA certificate (PEM-encoded).
   *   Use DefaultCertificateValidator::AUTOLOAD to use the bundled CiviRootCA.
   */
  protected $caCert;

  /**
   * @var string
   *   The URL for downloading the CRL.
   *   Use DefaultCertificateValidator::AUTOLOAD to extract from $caCert.
   */
  protected $crlUrl = DefaultCertificateValidator::AUTOLOAD;

  /**
   * @var string
   *   The CRL data.
   *   Use DefaultCertificateValidator::AUTOLOAD to download via HTTP.
   */
  protected $crl;

  /**
   * @var string
   *   The certificate which signs CRLs (PEM-encoded).
   *   Use DefaultCertificateValidator::AUTOLOAD to download via HTTP.
   */
  protected $crlDistCert;

  /**
   * @var HttpInterface|string
   *   The service to use when autoloading data.
   *   Use DefaultCertificateValidator::AUTOLOAD to download via HTTP.
   */
  protected $http;

  /**
   * @param string $caCertPem
   * @param string $crlDistCertPem
   * @param string $crlPem
   * @param HttpInterface|string $http
   */
  public function __construct(
    $caCertPem = DefaultCertificateValidator::AUTOLOAD,
    $crlDistCertPem = DefaultCertificateValidator::AUTOLOAD,
    $crlPem = DefaultCertificateValidator::AUTOLOAD,
    $http = DefaultCertificateValidator::AUTOLOAD) {

    $this->caCert = $caCertPem;
    $this->crlDistCert = $crlDistCertPem;
    $this->crl = $crlPem;
    $this->http = $http;
  }

  /**
   * Determine whether an X.509 certificate is currently valid.
   *
   * @param string $certPem
   *   PEM-encoded certificate.
   * @throws InvalidCertException
   *   Invalid certificates are reported as exceptions.
   */
  public function validateCert($certPem) {
    if ($this->getCaCert()) {
      self::validate($certPem, $this->getCaCert(), $this->getCrl(), $this->getCrlDistCert());
    }
  }

  protected static function validate($certPem, $caCertPem, $crlPem = NULL, $crlDistCertPem = NULL) {
    $caCertObj = X509Util::loadCACert($caCertPem);

    $certObj = new \File_X509();
    $certObj->loadCA($caCertPem);

    if ($crlPem !== NULL) {
      $crlObj = new \File_X509();
      if ($crlDistCertPem) {
        $crlDistCertObj = X509Util::loadCrlDistCert($crlDistCertPem, NULL, $caCertPem);
        if ($crlDistCertObj->getSubjectDN(FILE_X509_DN_STRING) !== $caCertObj->getSubjectDN(FILE_X509_DN_STRING)) {
          throw new InvalidCertException(sprintf("CRL distributor (%s) does not act on behalf of this CA (%s)",
            $crlDistCertObj->getSubjectDN(FILE_X509_DN_STRING),
            $caCertObj->getSubjectDN(FILE_X509_DN_STRING)
            ));
        }
        try {
          self::validate($crlDistCertPem, $caCertPem);
        }
        catch (InvalidCertException $ie) {
          throw new InvalidCertException("CRL distributor has an invalid certificate", 0, $ie);
        }
        $crlObj->loadCA($crlDistCertPem);
      }
      $crlObj->loadCA($caCertPem);
      $crlObj->loadCRL($crlPem);
      if (!$crlObj->validateSignature()) {
        throw new InvalidCertException("CRL signature is invalid");
      }
    }

    $parsedCert = $certObj->loadX509($certPem);
    if ($crlPem !== NULL) {
      if (empty($parsedCert)) {
        throw new InvalidCertException("Identity is invalid. Empty certificate.");
      }
      if (empty($parsedCert['tbsCertificate']['serialNumber'])) {
        throw new InvalidCertException("Identity is invalid. No serial number.");
      }
      $revoked = $crlObj->getRevoked($parsedCert['tbsCertificate']['serialNumber']->toString());
      if (!empty($revoked)) {
        throw new InvalidCertException("Identity is invalid. Certificate revoked.");
      }
    }

    if (!$certObj->validateSignature()) {
      throw new InvalidCertException("Identity is invalid. Certificate is not signed by proper CA.");
    }
    if (!$certObj->validateDate(Time::getTime())) {
      throw new ExpiredCertException("Identity is invalid. Certificate expired.");
    }
  }

  /**
   * @return string
   */
  public function getCaCert() {
    if ($this->caCert === self::AUTOLOAD) {
      $this->caCert = file_get_contents(Constants::getCert());
    }
    return $this->caCert;
  }

  /**
   * @param string $caCert
   * @return $this
   */
  public function setCaCert($caCert) {
    $this->caCert = $caCert;
    return $this;
  }

  /**
   * Determine the CRL URL which corresponds to this CA.
   */
  public function getCrlUrl() {
    if ($this->crlUrl === self::AUTOLOAD) {
      $this->crlUrl = NULL; // Default if we can't find something else.
      $caCertObj = X509Util::loadCACert($this->getCaCert());
      // There can be multiple DPs, but in practice CiviRootCA only has one.
      $crlDPs = $caCertObj->getExtension('id-ce-cRLDistributionPoints');
      if (is_array($crlDPs)) {
        foreach ($crlDPs as $crlDP) {
          foreach ($crlDP['distributionPoint']['fullName'] as $fullName) {
            if (isset($fullName['uniformResourceIdentifier'])) {
              $this->crlUrl = $fullName['uniformResourceIdentifier'];
              break 2;
            }
          }
        }
      }
    }
    return $this->crlUrl;
  }

  /**
   * @param string $crlUrl
   * @return $this
   */
  public function setCrlUrl($crlUrl) {
    $this->crlUrl = $crlUrl;
    return $this;
  }

  /**
   * @return string
   */
  public function getCrlDistCert() {
    if ($this->crlDistCert === self::AUTOLOAD) {
      if ($this->getCrlUrl()) {
        $url = preg_replace('/\.crl/', '/dist.crt', $this->getCrlUrl());
        list ($headers, $blob, $code) = $this->getHttp()->send('GET', $url, '');
        if ($code != 200) {
          throw new \RuntimeException("Certificate validation failed. Cannot load CRL distribution certificate: $url");
        }
        $this->crlDistCert = $blob;
      }
      else {
        $this->crlDistCert = NULL;
      }
    }
    return $this->crlDistCert;
  }

  /**
   * @param string $crlDistCert
   * @return $this
   */
  public function setCrlDistCert($crlDistCert) {
    $this->crlDistCert = $crlDistCert;
    return $this;
  }

  /**
   * @return string
   */
  public function getCrl() {
    if ($this->crl === self::AUTOLOAD) {
      $url = $this->getCrlUrl();
      if ($url) {
        list ($headers, $blob, $code) = $this->getHttp()->send('GET', $url, '');
        if ($code != 200) {
          throw new \RuntimeException("Certificate validation failed. Cannot load CRL: $url");
        }
        $this->crl = $blob;
      }
      else {
        $this->crl = NULL;
      }
    }
    return $this->crl;
  }

  /**
   * @param string $crl
   * @return $this
   */
  public function setCrl($crl) {
    $this->crl = $crl;
    return $this;
  }

  /**
   * @return HttpInterface
   */
  public function getHttp() {
    if ($this->http === self::AUTOLOAD) {
      $this->http = new PhpHttp();
    }
    return $this->http;
  }

  /**
   * @param HttpInterface $http
   * @return $this
   */
  public function setHttp($http) {
    $this->http = $http;
    return $this;
  }

}
