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

class JsonRpcSession {

  use LineSessionTrait;

  protected const METHOD_REGEX = ';^[a-z][a-zA-Z0-9_]*$;';

  /**
   * Open-ended object. Any public method will be available during this session.
   *
   * @var object
   */
  protected $methods;

  /**
   * @inheritDoc
   */
  protected function onConnect(): ?string {
    \CRM_Core_Session::useFakeSession();
    $this->methods = new PublicMethods();
    return json_encode(["Civi::pipe" => ['jsonrpc20']]);
  }

  /**
   * @inheritDoc
   */
  protected function onRequest(string $requestLine): ?string {
    $request = \json_decode($requestLine, TRUE);

    if ($request === NULL) {
      throw new \InvalidArgumentException('Parse error', -32700);
    }

    if (!is_array($request)) {
      throw new \InvalidArgumentException('Invalid Request', -32600);
    }

    if (isset($request[0])) {
      $response = array_map([$this, 'handleRequest'], $request);
    }
    else {
      $response = $this->handleRequest($request);
    }

    return \json_encode($response);
  }

  protected function handleRequest($request): array {
    try {
      if ($request === NULL) {
        throw new \InvalidArgumentException('Parse error', -32700);
      }
      if (($request['jsonrpc'] ?? '') !== '2.0') {
        throw new \InvalidArgumentException('Invalid Request', -32600);
      }

      $method = str_replace('.', '_', mb_strtolower($request['method']));
      if (!is_string($method) || !preg_match(self::METHOD_REGEX, $method)) {
        throw new \InvalidArgumentException('Invalid Request', -32600);
      }

      if (!is_callable([$this->methods, $method])) {
        throw new \InvalidArgumentException('Method not found', -32601);
      }

      $result = call_user_func([$this->methods, $method], $this, $request['params'] ?? []);
      $id = array_key_exists('id', $request) ? ['id' => $request['id']] : [];
      return [
        'jsonrpc' => '2.0',
        'result' => $result,
      ] + $id;
    }
    catch (\Throwable $t) {
      return $this->createJsonError($request, $t);
    }
  }

  /**
   * @inheritDoc
   */
  protected function onException(string $requestLine, \Throwable $t): ?string {
    return \json_encode($this->createJsonError(['jsonrpc' => '2.0'], $t));
  }

  protected function createJsonError(array $request, \Throwable $t): array {
    $isJsonErrorCode = $t->getCode() >= -32999 && $t->getCode() <= -32000;
    $errorData = \CRM_Core_Config::singleton()->debug
      ? ['class' => get_class($t), 'trace' => $t->getTraceAsString()]
      : NULL;
    return \CRM_Utils_Array::subset($request, ['jsonrpc', 'id']) + [
      'error' => [
        'code' => $isJsonErrorCode ? $t->getCode() : -32099,
        'message' => $t->getMessage(),
        'data' => $errorData,
      ],
    ];
  }

}
