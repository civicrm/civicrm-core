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

use Civi\Cxn\Rpc\Exception\GarbledMessageException;
use Civi\Cxn\Rpc\Exception\InvalidMessageException;
use Civi\Cxn\Rpc\Http\ViaPortHttp;
use Civi\Cxn\Rpc\Message\GarbledMessage;
use Civi\Cxn\Rpc\Message\StdMessage;

class ApiClient extends Agent {
  /**
   * @var array
   */
  protected $appMeta;

  /**
   * @var string
   */
  protected $cxnId;

  /**
   * @param array $appMeta
   * @param CxnStore\CxnStoreInterface $cxnStore
   * @param string $cxnId
   */
  public function __construct($appMeta, $cxnStore, $cxnId) {
    parent::__construct(NULL, $cxnStore);
    $this->appMeta = $appMeta;
    $this->cxnId = $cxnId;
    $this->http = new Http\PhpHttp();
  }

  /**
   * Call a remote API.
   *
   * Protocol errors will be reported as exceptions.
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   * @throws GarbledMessageException
   * @throws InvalidMessageException
   * @return mixed
   */
  public function call($entity, $action, $params = array()) {
    $this->log->debug("Send API call: {entity}.{action} over {cxnId}", array(
      'entity' => $entity,
      'action' => $action,
      'cxnId' => $this->cxnId,
    ));
    $cxn = $this->cxnStore->getByCxnId($this->cxnId);
    $req = new StdMessage($cxn['cxnId'], $cxn['secret'],
      array($entity, $action, $params, $this->appMeta['appCert']));

    $http = empty($cxn['viaPort']) ? $this->http : new ViaPortHttp($this->http, $cxn['viaPort']);

    list($respHeaders, $respCiphertext, $respCode) = $http->send('POST', $cxn['siteUrl'], $req->encode(), array(
      'Content-type' => Constants::MIME_TYPE,
    ));
    $respMessage = $this->decode(array(StdMessage::NAME, GarbledMessage::NAME), $respCiphertext);
    if ($respMessage instanceof GarbledMessage) {
      throw new GarbledMessageException($respMessage);
    }
    elseif ($respMessage instanceof StdMessage) {
      if ($respMessage->getCxnId() != $cxn['cxnId']) {
        // Tsk, tsk, Mallory!
        throw new InvalidMessageException('Received response from incorrect connection.');
      }
      return $respMessage->getData();
    }
    else {
      throw new InvalidMessageException('Unrecognized message type.');
    }
  }

  /**
   * Call a remote API.
   *
   * This is the same as call(), except that it wraps the request in a try-catch
   * block and converts any exceptions to array(is_error=>...) format.
   *
   * @param $entity
   * @param $action
   * @param array $params
   * @return array|mixed
   */
  public function callSafe($entity, $action, $params = array()) {
    try {
      return $this->call($entity, $action, $params);
    }
    catch (GarbledMessageException $e) {
      return array(
        'is_error' => 1,
        'error_message' => "Client exception: " . $e->getMessage(),
        'garbled_message' => substr($e->getGarbledMessage()->getData(), 0, 77),
      );
    }
    catch (\Exception $e) {
      return array(
        'is_error' => 1,
        'error_message' => "Client exception: " . $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      );
    }
  }

}
