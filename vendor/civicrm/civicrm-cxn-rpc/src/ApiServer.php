<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc;

use Civi\Cxn\Rpc\Exception\InvalidMessageException;
use Civi\Cxn\Rpc\Message\InsecureMessage;
use Civi\Cxn\Rpc\Message\StdMessage;

class ApiServer extends Agent {

  /**
   * @var callable
   */
  protected $router;

  /**
   * @param CxnStore\CxnStoreInterface $cxnStore
   */
  public function __construct($cxnStore, $router = NULL) {
    parent::__construct(NULL, $cxnStore);
    $this->router = $router;
  }

  /**
   * Parse the ciphertext, process it, and return the response.
   *
   * FIXME Catch exceptions and return in a nice format.
   *
   * @param string $blob
   *   POST'ed ciphertext.
   * @return Message
   */
  public function handle($blob) {
    try {
      $reqMessage = $this->decode(StdMessage::NAME, $blob);
    }
    catch (InvalidMessageException $e) {
      $this->log->debug('Received invalid message', array(
        'exception' => $e,
      ));
      $resp = new InsecureMessage(array(
        'is_error' => 1,
        'error_message' => 'Invalid message coding',
        array(
          $e->getMessage(),
          $e->getTraceAsString(),
        ),
      ));
      return $resp->setCode(400);
    }

    $cxn = $this->cxnStore->getByCxnId($reqMessage->getCxnId());
    $validation = Cxn::getValidationMessages($cxn);
    if (!empty($validation)) {
      $this->log->error('Invalid cxn ({cxnId})', array(
        'cxnId' => $reqMessage->getCxnId(),
        'messages' => $validation,
      ));
      // $cxn is not valid, so we can't encode it use it for encoding.
      $resp = new InsecureMessage(array(
        'is_error' => 1,
        'error_message' => 'Invalid cxn details: ' . implode(', ', array_keys($validation)),
      ));
      return $resp->setCode(400);
    }

    try {
      list ($entity, $action, $params, $appCert) = $reqMessage->getData();
      if ($this->certValidator) {
        $this->certValidator->validateCert($appCert);
        $appCertObj = X509Util::loadCert($appCert);
        $cn = $appCertObj->getDNProp('CN');
        if (count($cn) != 1 || $cn[0] !== $cxn['appId']) {
          throw new InvalidMessageException('Invalid message: Submitted certificate does not matched expected appId');
        }
      }
      $respData = call_user_func($this->router, $cxn, $entity, $action, $params);
      $this->log->info('Processed API call ({entity}.{action})', array(
        'entity' => $entity,
        'action' => $action,
      ));
    }
    catch (\Exception $e) {
      $this->log->error('Error executing API call', array(
        'request' => $reqMessage->getData(),
        'exception' => $e,
      ));
      $respData = array(
        'is_error' => 1,
        'error_message' => $e->getMessage(),
      );
    }

    return new StdMessage($reqMessage->getCxnId(), $cxn['secret'], $respData);
  }

  /**
   * @param callable $router
   */
  public function setRouter($router) {
    $this->router = $router;
  }

}
