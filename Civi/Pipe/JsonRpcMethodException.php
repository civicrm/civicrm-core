<?php

namespace Civi\Pipe;

/**
 * The JsonRpcMethodException is emitted by a JSON-RPC client if a method call returns an error.
 *
 * This differs from an protocol-error or client-error. In this case, all JSON-RPC traffic has
 * been well-formed; but the payload indicates that a specific method-call failed.
 */
class JsonRpcMethodException extends \CRM_Core_Exception {

  /**
   * @var array
   * @readonly
   */
  public $raw;

  public function __construct(array $jsonRpcError) {
    parent::__construct($jsonRpcError['error']['message'] ?? 'Unknown JSON-RPC error',
      $jsonRpcError['error']['code'] ?? 0,
      $jsonRpcError['error']['data'] ?? []
    );
    $this->raw = $jsonRpcError;
  }

}
