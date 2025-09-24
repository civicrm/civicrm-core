<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */


use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Handle requests for civicrm/dev/rtf
 */
class CRM_Core_Page_RemoteTestFunction extends CRM_Core_Page {

  public function run() {
    $result = static::handleJwt(CRM_Utils_Request::retrieve('t', 'String'));
    if (is_string($result) || is_numeric($result)) {
      echo $result;
    }
    elseif ($result instanceof \Psr\Http\Message\ResponseInterface) {
      CRM_Utils_System::sendResponse($result);
    }
    else {
      throw new \LogicException("Malformed response");
    }
  }

  /**
   * Decode a JWT token and call the requested RTF.
   *
   * @internal
   * @param string $token
   * @return \GuzzleHttp\Psr7\Response|string
   */
  public static function handleJwt(string $token) {
    try {
      /** @var \Civi\Crypto\CryptoJwt $jwt */
      $jwt = Civi::service('crypto.jwt');
      $claims = $jwt->decode($token);
    }
    catch (\Exception $e) {
      $claims = [];
    }

    if (empty($claims['exp'])) {
      throw new \LogicException("All JWT signatures must include an expiration time.");
    }

    if (empty($claims['civi.remote-test-function']->id)) {
      return new Response(200, ['Content-Type' => 'text/plain'], 'Hello world');
    }

    $responseType = $claims['civi.remote-test-function']->{'response-type'} ?? NULL;
    $responder = static::getResponders()[$responseType] ?? NULL;
    if (!$responder) {
      throw new \LogicException("Invalid response type");
    }

    // Ensure args use JSON-array rather than JSON-stdClass.
    $args = json_decode(json_encode($claims['civi.remote-test-function']->args ?? []), TRUE);

    $rtf = \Civi\Test\RemoteTestFunction::byId($claims['civi.remote-test-function']->id);
    $result = $rtf->_run($args);
    return $responder($result);
  }

  protected static function getResponders(): array {
    return [
      'application/json' => function ($data) {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
      },
      'application/php-serialized' => function ($data) {
        return new Response(200, ['Content-Type' => 'application/php-serialized'], serialize($data));
      },
      'text/html' => function ($html) {
        if (is_string($html)) {
          return $html;
        }
        else {
          throw new \LogicException("Expected output of type HTML. Received: " . gettype($html));
        }
      },
      'psr7' => function ($data) {
        if ($data instanceof \Psr\Http\Message\ResponseInterface) {
          return $data;
        }
        else {
          throw new \LogicException("Expected output of type \Psr\Http\Message\ResponseInterface. Received: " . gettype($data));
        }
      },
    ];
  }

  public static function convertResponseToArray($response) {
    if ($response instanceof ResponseInterface) {
      return [
        'code' => $response->getStatusCode(),
        'body' => $response->getBody()->getContents(),
        'headers' => $response->getHeaders(),
      ];
    }
    elseif (is_string($response)) {
      return [
        'code' => 200,
        'body' => "<html><body>$response</body></html>",
        'headers' => ['Content-Type' => 'text/html'],
      ];
    }
    else {
      throw new \RuntimeException("Unrecognized response type: " . gettype($response));
    }
  }

  public static function convertArrayToResponse(array $array): ResponseInterface {
    return new Response($array['code'], $array['headers'], $array['body']);
  }

}
