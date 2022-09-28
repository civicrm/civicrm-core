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
   * Open-ended object. Any public method from this object will be available during this session.
   *
   * @var object
   * @see \Civi\Pipe\PublicMethods
   */
  protected $methods;

  /**
   * @var bool|null
   */
  protected $trusted;

  /**
   * @inheritDoc
   */
  protected function onConnect(string $negotiationFlags): ?string {
    \CRM_Core_Session::useFakeSession();
    $this->methods = new PublicMethods();

    // Convention: Every negotiation-flag should produce exactly one output in the header line.
    foreach (str_split($negotiationFlags) as $flag) {
      switch ($flag) {
        case 'v':
          $flags[$flag] = \CRM_Utils_System::version();
          break;

        case 'j':
          $flags[$flag] = ['jsonrpc-2.0'];
          break;

        case 'l':
          $flags[$flag] = function_exists('authx_login') ? ['login'] : ['nologin'];
          break;

        case 't':
          $this->setTrusted(TRUE);
          $flags[$flag] = 'trusted';
          break;

        case 'u':
          $this->setTrusted(FALSE);
          $flags[$flag] = 'untrusted';
          break;

        default:
          // What flags might exist in the future? We don't know! Communicate that we don't know.
          $flags[$flag] = NULL;
          break;
      }
    }

    return json_encode(['Civi::pipe' => $flags]);
  }

  /**
   * @inheritDoc
   */
  protected function onRequest(string $requestLine): ?string {
    return JsonRpc::run($requestLine, function(string $method, array $params) {
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
