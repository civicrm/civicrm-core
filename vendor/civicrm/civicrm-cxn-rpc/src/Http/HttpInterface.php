<?php
namespace Civi\Cxn\Rpc\Http;

interface HttpInterface {

  /**
   * @param string $verb
   * @param string $url
   * @param string $blob
   * @param array $headers
   *   Array of headers (e.g. "Content-type" => "text/plain").
   * @return array
   *   array($headers, $blob, $code)
   */
  public function send($verb, $url, $blob, $headers = array());

}
