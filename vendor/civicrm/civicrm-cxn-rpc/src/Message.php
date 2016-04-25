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

abstract class Message {

  protected $code = 200;
  protected $headers = array();
  protected $data;

  public function __construct($data) {
    $this->data = $data;
  }

  /**
   * @return string
   *   Encoded message.
   */
  abstract public function encode();

  /**
   * @return int
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * @param int $code
   * @return static
   */
  public function setCode($code) {
    $this->code = $code;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getData() {
    return $this->data;
  }

  /**
   * @param mixed $data
   * @return static
   */
  public function setData($data) {
    $this->data = $data;
    return $this;
  }

  /**
   * @return array
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * @param array $headers
   * @return static
   */
  public function setHeaders($headers) {
    $this->headers = $headers;
    return $this;
  }

  /**
   * Extract the necessary parts to return this
   * message as an HTTP response.
   *
   * @return array
   *   array($headers, $blob, $code)
   */
  public function toHttp() {
    return array($this->headers, $this->encode(), $this->code);
  }

  /**
   * Send this message immediately.
   */
  public function send() {
    list ($headers, $blob, $code) = $this->toHttp();
    header('Content-Type: ' . Constants::MIME_TYPE);
    header("X-PHP-Response-Code: $code", TRUE, $code);
    foreach ($headers as $n => $v) {
      header("$n: $v");
    }
    echo $blob;
  }

  /**
   * Convert this message a Symfony "Response" object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function toSymfonyResponse() {
    $headers = array_merge(
      array('Content-Type' => Constants::MIME_TYPE),
      $this->getHeaders()
    );
    return new \Symfony\Component\HttpFoundation\Response(
      $this->encode(),
      $this->code,
      $headers
    );
  }

}
