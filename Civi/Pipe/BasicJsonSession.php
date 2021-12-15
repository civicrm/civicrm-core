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

class BasicJsonSession {

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
    return json_encode(["Civi::pipe" => ['json']]);
  }

  /**
   * @inheritDoc
   */
  protected function onRequest(string $requestLine): ?string {
    $request = \json_decode($requestLine, TRUE);
    if ($request === NULL || !is_array($request) || count($request) !== 1) {
      throw new \InvalidArgumentException('Malformed request');
    }

    foreach ($request as $type => $params) {
      $method = str_replace('.', '_', mb_strtolower($type));
      if (is_string($method) && preg_match(self::METHOD_REGEX, $method) && is_callable([$this->methods, $method])) {
        $response = call_user_func([$this->methods, $method], $this, $params);
        return \json_encode(['OK' => $response]);
      }
      else {
        return $this->onException($requestLine, new \InvalidArgumentException('Invalid request type'));
      }
    }

    throw new \RuntimeException("Unreachable: Request count and request loop were mismatched");
  }

  /**
   * @inheritDoc
   */
  protected function onException(string $requestLine, \Throwable $t): ?string {
    return \json_encode([
      'ERR' => [
        'type' => get_class($t),
        'message' => $t->getMessage(),
        'trace' => $t->getTraceAsString(),
      ],
    ]);
  }

}
