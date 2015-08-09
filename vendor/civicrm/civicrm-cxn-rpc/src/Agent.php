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
use Civi\Cxn\Rpc\Message\AppMetasMessage;
use Civi\Cxn\Rpc\Message\GarbledMessage;
use Civi\Cxn\Rpc\Message\InsecureMessage;
use Civi\Cxn\Rpc\Message\RegistrationMessage;
use Civi\Cxn\Rpc\Message\StdMessage;
use Psr\Log\NullLogger;

class Agent {

  /**
   * @var AppStore\AppStoreInterface
   */
  protected $appStore;

  /**
   * @var string
   */
  protected $caCert;

  /**
   * @var CxnStore\CxnStoreInterface
   */
  protected $cxnStore;

  /**
   * @var CertificateValidatorInterface
   */
  protected $certValidator;

  /**
   * @var Http\HttpInterface
   */
  protected $http;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $log;

  public function __construct($appStore, $cxnStore) {
    $this->appStore = $appStore;
    $this->cxnStore = $cxnStore;
    $this->certValidator = new DefaultCertificateValidator();
    $this->log = new NullLogger();
    $this->http = new Http\PhpHttp();
  }

  /**
   * @param array|string $formats
   * @param string $blob
   * @return Message
   * @throws InvalidMessageException
   */
  public function decode($formats, $blob) {
    $formats = (array) $formats;
    $prefixLen = 0;
    foreach ($formats as $format) {
      $prefixLen = max($prefixLen, strlen($format));
    }

    list($prefix) = explode(Constants::PROTOCOL_DELIM, substr($blob, 0, $prefixLen + 1));
    if (!in_array($prefix, $formats)) {
      if (in_array(GarbledMessage::NAME, $formats)) {
        return GarbledMessage::decode($blob);
      }
      else {
        throw new InvalidMessageException("Unexpected message type.");
      }
    }

    switch ($prefix) {
      case StdMessage::NAME:
        return StdMessage::decode($this->cxnStore, $blob);

      case InsecureMessage::NAME:
        return InsecureMessage::decode($blob);

      case RegistrationMessage::NAME:
        return RegistrationMessage::decode($this->appStore, $blob);

      case AppMetasMessage::NAME:
        return AppMetasMessage::decode($this->certValidator, $blob);

      default:
        throw new InvalidMessageException("Unrecognized message type.");
    }
  }

  /* ----- boilerplate ----- */

  /**
   * @return AppStore\AppStoreInterface
   */
  public function getAppStore() {
    return $this->appStore;
  }

  /**
   * @param AppStore\AppStoreInterface $appStore
   */
  public function setAppStore($appStore) {
    $this->appStore = $appStore;
    return $this;
  }

  /**
   * @return CertificateValidatorInterface
   */
  public function getCertValidator() {
    return $this->certValidator;
  }

  /**
   * @param CertificateValidatorInterface $certValidator
   */
  public function setCertValidator($certValidator) {
    $this->certValidator = $certValidator;
    return $this;
  }

  /**
   * @return CxnStore\CxnStoreInterface
   */
  public function getCxnStore() {
    return $this->cxnStore;
  }

  /**
   * @param CxnStore\CxnStoreInterface $cxnStore
   */
  public function setCxnStore($cxnStore) {
    $this->cxnStore = $cxnStore;
    return $this;
  }

  /**
   * @return Http\HttpInterface
   */
  public function getHttp() {
    return $this->http;
  }

  /**
   * @param Http\HttpInterface $http
   */
  public function setHttp($http) {
    $this->http = $http;
    return $this;
  }

  /**
   * @return \Psr\Log\LoggerInterface
   */
  public function getLog() {
    return $this->log;
  }

  /**
   * @param \Psr\Log\LoggerInterface $log
   */
  public function setLog($log) {
    $this->log = $log;
    return $this;
  }

  /**
   * @param array $values
   * @return array
   */
  protected function createSuccess($values) {
    return array(
      'is_error' => 0,
      'values' => $values,
    );
  }

  /**
   * @param string $message
   * @return array
   */
  protected function createError($message) {
    return array(
      'is_error' => 1,
      'error_message' => $message,
    );
  }

}
