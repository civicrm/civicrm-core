<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc\Http;

class PhpHttp implements HttpInterface {

  /**
   * @param string $verb
   * @param string $url
   * @param string $blob
   * @param array $headers
   *   Array of headers (e.g. "Content-type" => "text/plain").
   * @return array
   *   array($headers, $blob, $code)
   */
  public function send($verb, $url, $blob, $headers = array()) {
    $opts = $this->createStreamOpts($verb, $url, $blob, $headers);
    $context = stream_context_create($opts);
    $respBlob = file_get_contents($url, FALSE, $context);
    $code = NULL;
    $headers = array();
    foreach ($http_response_header as $line) {
      if (preg_match('/^HTTP\/[0-9\.]+[^0-9]+([0-9]+)/', $line, $matches)) {
        $code = $matches[1];
      }
      elseif (preg_match(';^([a-zA-Z0-9\-]+):[ \t](.*);', $line, $matches)) {
        $headers[$matches[1]] = $matches[2];
      }
    }
    return array($headers, $respBlob, $code);
  }

  /**
   * @param $verb
   * @param $blob
   * @param $headers
   * @return array
   */
  protected function createStreamOpts($verb, $url, $blob, $headers) {
    $opts = array(
      'http' => array(
        'method' => $verb,
        'content' => $blob,
      ),
    );
    if (!empty($headers)) {
      $encodedHeaders = '';
      foreach ($headers as $k => $v) {
        $encodedHeaders .= $k . ": " . $v . "\r\n";
      }
      $opts['http']['header'] = $encodedHeaders;
      return $opts;
    }
    return $opts;
  }

}
