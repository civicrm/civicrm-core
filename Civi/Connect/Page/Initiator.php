<?php

namespace Civi\Connect\Page;

use Psr\Http\Message\ResponseInterface;

/**
 * Begin a workflow to set an API key. This will redirect to the appropriate setup screen.
 *
 * This would canonically build on OAuthClient.authorizationCode() for an existing OAuthClient, but it could be another
 * variant.
 *
 * Usage: /civicrm/ajax/initiator?jwt={JWT(exp: INT_EPOCH, initiator: NAME, initiatorContext: ARRAY)}
 */
class Initiator {

  public static function initiate() {
    static::runJson(function(): array {
      $rawJwt = \CRM_Utils_Request::retrieve('jwt', 'String');
      $jwt = \Civi::service('crypto.jwt')->decode($rawJwt);
      if (empty($jwt['initiator']) || !isset($jwt['initiatorContext'])) {
        throw new \CRM_Core_Exception('Invalid JWT');
      }

      // Caste to array
      $context = json_decode(json_encode($jwt['initiatorContext']), TRUE);

      $initiators = \Civi\Connect\Initiators::create($context);
      $initiator = $initiators->get($jwt['initiator']);
      if ($initiator === NULL) {
        throw new \CRM_Core_Exception('Cannot initialize API key. Unknown initiator.');
      }

      return \Civi\Core\Resolver::singleton()->call($initiator['callback'], [$context, $initiator]);
    });
  }

  /**
   * Run the operation. Format results as JSON. If there are errors, format those as JSON.
   */
  protected static function runJson(callable $callback): void {
    try {
      $response = static::json(200, $callback());
    }
    catch (\Throwable $e) {
      $suffix = \CRM_Core_Config::singleton()->backtrace ? (":" . \CRM_Core_Error::formatBacktrace($e->getTrace())) : '';
      $response = static::json(500, "Internal Server Error{$suffix}");
    }

    \CRM_Utils_System::sendResponse($response);
  }

  protected static function json(int $code, array $data): ResponseInterface {
    return new \GuzzleHttp\Psr7\Response($code,
      ['Content-Type' => 'application/json'],
      json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
  }

}
