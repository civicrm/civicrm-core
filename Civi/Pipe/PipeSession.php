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

class PipeSession {

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
    return JsonRpc::run($requestLine, function($method, $params) {
      $method = str_replace('.', '_', $method);
      if (!preg_match(self::METHOD_REGEX, $method)) {
        throw new \InvalidArgumentException('Method not found', -32601);
      }

      if (!is_callable([$this->methods, $method])) {
        throw new \InvalidArgumentException('Method not found', -32601);
      }

      return call_user_func([$this->methods, $method], $this, $params);
    });
  }

  /**
   * @inheritDoc
   */
  protected function onException(string $requestLine, \Throwable $t): ?string {
    $error = JsonRpc::createResponseError([], $t);
    return \json_encode($error);
  }

}
