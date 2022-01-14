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

namespace Civi\Pipe;

class JsonRpc {

  /**
   * Execute a JSON-RPC request and return a result.
   *
   * This adapter handles decoding, encoding, and conversion of exceptions.
   *
   * @code
   * $input = '{"jsonrpc":"2.0","method":"greet","id":1}';
   * $output = JsonRpc::run($input, function(string $method, array $params) {
   *   if ($method === 'greet') return 'hello world';
   *   else throw new \InvalidArgumentException('Method not found', -32601);
   * });
   * assert $output === '{"jsonrpc":"2.0","result":"hello world","id":1}';
   * @endCode
   *
   * @param string $requestLine
   *   JSON formatted RPC request
   * @param callable $dispatcher
   *   Dispatch function - given a parsed/well-formed request, compute the result.
   *   Signature: function(string $method, mixed $params): mixed
   * @return string
   *   JSON formatted RPC response
   */
  public static function run(string $requestLine, callable $dispatcher): string {
    $parsed = \json_decode($requestLine, TRUE);

    if ($parsed === NULL) {
      throw new \InvalidArgumentException('Parse error', -32700);
    }

    if (isset($parsed[0])) {
      $response = [];
      foreach ($parsed as $request) {
        $response[] = static::handleMethodCall($request, $dispatcher);
      }
    }
    elseif (isset($parsed['method'])) {
      $response = static::handleMethodCall($parsed, $dispatcher);
    }
    else {
      // [sic] 'Invalid Request' title-case is anomalous but dictated by standard.
      throw new \InvalidArgumentException('Invalid Request', -32600);
    }

    return \json_encode($response);
  }

  protected static function handleMethodCall($request, $dispatcher): array {
    try {
      if ($request === NULL) {
        throw new \InvalidArgumentException('Parse error', -32700);
      }
      if (($request['jsonrpc'] ?? '') !== '2.0' || !is_string($request['method'])) {
        // [sic] 'Invalid Request' title-case is anomalous but dictated by standard.
        throw new \InvalidArgumentException('Invalid Request', -32600);
      }
      if (isset($request['params']) && !is_array($request['params'])) {
        throw new \InvalidArgumentException('Invalid params', -32602);
      }

      $result = $dispatcher($request['method'], $request['params'] ?? []);
      return static::createResponseSuccess($request, $result);
    }
    catch (\Throwable $t) {
      return static::createResponseError($request, $t);
    }
  }

  /**
   * Create a response object (successful).
   *
   * @link https://www.jsonrpc.org/specification#response_object
   * @param array{jsonrpc: string, method: string, params: array, id: ?mixed} $request
   * @param mixed $result
   *   The result-value of the method call.
   * @return array{jsonrpc: string, result: mixed, id: ?mixed}
   */
  public static function createResponseSuccess(array $request, $result): array {
    $id = array_key_exists('id', $request) ? ['id' => $request['id']] : [];
    return [
      'jsonrpc' => '2.0',
      'result' => $result,
    ] + $id;
  }

  /**
   * Create a response object (unsuccessful).
   *
   * @link https://www.jsonrpc.org/specification#response_object
   * @param array{jsonrpc: string, method: string, params: array, id: ?mixed} $request
   * @param \Throwable $t
   *   The exception which caused the request to fail.
   * @return array{jsonrpc: string, error: array, id: ?mixed}
   */
  public static function createResponseError(array $request, \Throwable $t): array {
    $isJsonErrorCode = $t->getCode() >= -32999 && $t->getCode() <= -32000;
    $errorData = \CRM_Core_Config::singleton()->debug
      ? ['class' => get_class($t), 'trace' => $t->getTraceAsString()]
      : NULL;
    $id = array_key_exists('id', $request) ? ['id' => $request['id']] : [];
    return [
      'jsonrpc' => $request['jsonrpc'] ?? '2.0',
      'error' => [
        'code' => $isJsonErrorCode ? $t->getCode() : -32099,
        'message' => $t->getMessage(),
        'data' => $errorData,
      ],
    ] + $id;
  }

}
