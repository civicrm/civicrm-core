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

/**
 * Class ViaPortHttp
 * @package Civi\Cxn\Rpc\Http
 *
 * This decorator allows you to modify an existing HttpClient to redirect
 * all requests through an alternate host/port. However, the HTTP request
 * itself (the path and vhost) remain unchanged.
 *
 * @code
 * $http = new ViaPortHttp(new PhpHttp(), 'proxy.example.com:1234');
 * $http->send('http://example.net/');
 * @endcode
 *
 * Terminology: Standard HTTP proxies support a header called "Via:", which
 * is a different thing. This is just regular HTTP directed through an
 * alternative host/port (as in "NAT with port-forwarding"). To keep it
 * distinct, this functionality is referred to as "ViaPort".
 */
class ViaPortHttp implements HttpInterface {

  /**
   * @var \Civi\Cxn\Rpc\Http\HttpInterface
   */
  private $http;

  /**
   * @var string
   *   Ex: "123.123.123.123:456".
   *   Ex: "proxy.example.com:789"
   *   Ex: "dhcp123.isp.example.net:456"
   */
  private $viaPort;

  /**
   * ViaPortHttp constructor.
   * @param string $viaPort
   */
  public function __construct(HttpInterface $http, $viaPort) {
    $this->http = $http;
    $this->viaPort = $viaPort;
  }

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
    if ($this->getViaPort()) {
      list($verb, $url, $blob, $headers) = $this->modifyRequest($verb, $url, $blob, $headers);
      return $this->getHttp()->send($verb, $url, $blob, $headers);
    }
    else {
      return $this->getHttp()->send($verb, $url, $blob, $headers);
    }
  }

  /**
   * @param string $url
   * @return array
   *   Array($verb, $url, $blob, $headers)
   */
  public function modifyRequest($verb, $url, $blob, $headers) {
    $parsedUrl = parse_url($url);
    list ($viaHost, $viaPort) = explode(':', $this->getViaPort());

    $newUrl = $parsedUrl['scheme'] . '://' . $viaHost;
    if ($viaPort) {
      $newUrl .= ':' . $viaPort;
    }
    if (isset($parsedUrl['path'])) {
      $newUrl .= $parsedUrl['path'];
    }
    if (isset($parsedUrl['query'])) {
      $newUrl .= '?' . $parsedUrl['query'];
    }

    $headers['Host'] = $parsedUrl['host'];

    return array($verb, $newUrl, $blob, $headers);

  }

  /**
   * @return string
   *   Ex: "123.123.123.123:456".
   *   Ex: "proxy.example.com:789"
   *   Ex: "dhcp123.isp.example.net:456"
   */
  public function getViaPort() {
    return $this->viaPort;
  }

  /**
   * @param string $viaPort
   *   Ex: "123.123.123.123:456".
   *   Ex: "proxy.example.com:789"
   *   Ex: "dhcp123.isp.example.net:456"
   * @return $this
   */
  public function setViaPort($viaPort) {
    $this->viaPort = $viaPort;
    $modified = array();
    return $this;
  }

  /**
   * @return HttpInterface
   */
  public function getHttp() {
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

  /**
   * Determine whether $viaPort expression is well-formed.
   *
   * @param string $viaPort
   *   Ex: "123.123.123.123:456".
   *   Ex: "proxy.example.com:789"
   *   Ex: "dhcp123.isp.example.net:456"
   * @return bool
   *   TRUE if valid. FALSE otherwise.
   */
  public static function validate($viaPort) {
    if (preg_match('/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}:\d+$/', $viaPort)) {
      return TRUE;
    }
    if (preg_match('/([0-9a-fA-F\.:]+):\d+/', $viaPort, $matches)) {
      return filter_var($matches[1], FILTER_VALIDATE_IP) !== FALSE;
    }
    return FALSE;
  }

}
