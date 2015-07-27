<?php
namespace Civi\Cxn\Rpc\Http;

class FakeHttp implements HttpInterface {

  protected $callable;

  public function __construct($callable) {
    $this->callable = $callable;
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
    return call_user_func($this->callable, $verb, $url, $blob, $headers);
  }

}
