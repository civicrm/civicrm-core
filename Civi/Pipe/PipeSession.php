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
   * @var bool|null
   */
  protected $trusted;

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

  /**
   * @param bool $trusted
   * @return PipeSession
   */
  public function setTrusted(bool $trusted): PipeSession {
    if ($this->trusted !== NULL && $this->trusted !== $trusted) {
      throw new \CRM_Core_Exception('Cannot modify PipeSession::$trusted after initialization');
    }
    $this->trusted = $trusted;
    return $this;
  }

  /**
   * @return bool
   */
  public function isTrusted(): bool {
    // If this gets called when the value is NULL, then you are doing it wrong.
    return $this->trusted;
  }

}
