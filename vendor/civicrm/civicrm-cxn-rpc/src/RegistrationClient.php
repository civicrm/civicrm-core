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

use Civi\Cxn\Rpc\Exception\CxnException;
use Civi\Cxn\Rpc\Message\GarbledMessage;
use Civi\Cxn\Rpc\Message\InsecureMessage;
use Civi\Cxn\Rpc\Message\RegistrationMessage;
use Civi\Cxn\Rpc\Message\StdMessage;

class RegistrationClient extends Agent {

  /**
   * @var string
   */
  protected $siteUrl;

  /**
   * @var string|NULL
   *   Ex: "123.123.123.123:456".
   *   Ex: "proxy.example.com:789"
   *   Ex: "dhcp123.isp.example.net:456"
   */
  protected $viaPort;

  /**
   * @param CxnStore\CxnStoreInterface $cxnStore
   *   The place to store active connections.
   * @param string $siteUrl
   *   The callback URL used when the app wishes to send an API call to the site.
   * @param string|NULL $viaPort
   *   If $siteUrl is behind a firewall or otherwise unrouteable, you can
   *   specify a proxy for any callback messages.
   *   Ex: "123.123.123.123:456".
   *   Ex: "proxy.example.com:789"
   *   Ex: "dhcp123.isp.example.net:456"
   */
  public function __construct($cxnStore, $siteUrl, $viaPort = NULL) {
    parent::__construct(NULL, $cxnStore);
    $this->siteUrl = $siteUrl;
    $this->viaPort = $viaPort;
  }

  /**
   * @param array $appMeta
   * @return array
   *   Array($cxnId, $isOk).
   */
  public function register($appMeta) {
    AppMeta::validate($appMeta);
    if ($this->certValidator) {
      $this->certValidator->validateCert($appMeta['appCert']);
    }

    $cxn = $this->cxnStore->getByAppId($appMeta['appId']);
    if (!$cxn) {
      $cxn = array(
        'cxnId' => Cxn::createId(),
        'secret' => AesHelper::createSecret(),
        'appId' => $appMeta['appId'],
      );
    }
    $cxn['appUrl'] = $appMeta['appUrl'];
    $cxn['siteUrl'] = $this->siteUrl;
    $cxn['perm'] = $appMeta['perm'];
    if (!empty($this->viaPort)) {
      $cxn['viaPort'] = $this->viaPort;
    }
    Cxn::validate($cxn);
    $this->cxnStore->add($cxn);

    list($respCode, $respData) = $this->doCall($appMeta, 'Cxn', 'register', array(), $cxn);
    $success = $respCode == 200 && $respData['is_error'] == 0;
    $this->log->info($success ? 'Registered cxnId={cxnId} ({appId}, {appUrl})' : 'Failed to register cxnId={cxnId} ({appId}, {appUrl})', array(
      'cxnId' => $cxn['cxnId'],
      'appId' => $cxn['appId'],
      'appUrl' => $cxn['appUrl'],
    ));
    return array($cxn['cxnId'], $respData);
  }

  /**
   * @param array $appMeta
   * @return array
   *   Array($cxnId, $apiResult).
   */
  public function unregister($appMeta, $force = FALSE) {
    $cxn = $this->cxnStore->getByAppId($appMeta['appId']);
    if (!$cxn) {
      return array(
        NULL,
        array(
          'is_error' => 1,
          'error_message' => 'Unrecognized appId',
        ),
      );
    }

    $this->log->info('Unregister cxnId={cxnId} ({appId}, {appUrl})', array(
      'cxnId' => $cxn['cxnId'],
      'appId' => $cxn['appId'],
      'appUrl' => $cxn['appUrl'],
    ));

    $e = NULL;
    try {
      if ($this->certValidator) {
        $this->certValidator->validateCert($appMeta['appCert']);
      }
      list($respCode, $respData) = $this->doCall($appMeta, 'Cxn', 'unregister', array(), $cxn);
      $success = $respCode == 200 && is_array($respData) && $respData['is_error'] == 0;
    }
    catch (\Exception $e2) {
      // simulate try..finally..
      $e = $e2;
      $success = FALSE;
    }

    if ($success || $force) {
      $this->cxnStore->remove($cxn['cxnId']);
    }

    if ($e) {
      throw $e;
    }

    return array($cxn['cxnId'], $respData);
  }

  /**
   * @param array $appMeta
   *   See AppMeta::validate.
   * @param string $entity
   *   An entity name (usually "Cxn").
   * @param string $action
   *   An action (eg "getlink").
   * @param array $params
   *   Open-ended key-value params (depending on entity+action).
   * @return mixed
   *   The response data.
   * @throws Exception\ExpiredCertException
   * @throws Exception\InvalidCertException
   */
  public function call($appMeta, $entity, $action, $params) {
    $cxn = $this->cxnStore->getByAppId($appMeta['appId']);
    if (!$cxn) {
      return array(
        NULL,
        array(
          'is_error' => 1,
          'error_message' => 'Unrecognized appId',
        ),
      );
    }

    $this->log->info('Call {entity}.{action}: ({cxnId}, {appId}, {appUrl})', array(
      'entity' => $entity,
      'action' => $action,
      'cxnId' => $cxn['cxnId'],
      'appId' => $cxn['appId'],
      'appUrl' => $cxn['appUrl'],
    ));

    if ($this->certValidator) {
      $this->certValidator->validateCert($appMeta['appCert']);
    }
    list($respCode, $respData) = $this->doCall($appMeta, $entity, $action, $params, $cxn);
    return $respData;
  }

  /**
   * @param $appMeta
   * @param $entity
   * @param $action
   * @param $params
   * @param $cxn
   * @return array
   * @throws Exception\InvalidMessageException
   */
  protected function doCall($appMeta, $entity, $action, $params, $cxn) {
    $appCert = new \File_X509();
    $appCert->loadX509($appMeta['appCert']);

    $req = new RegistrationMessage($cxn['appId'], $appCert->getPublicKey(), array(
      'cxn' => $cxn,
      'entity' => $entity,
      'action' => $action,
      'params' => $params,
    ));

    list($respHeaders, $respCiphertext, $respCode) = $this->http->send('POST', $cxn['appUrl'], $req->encode());
    $respMessage = $this->decode(array(StdMessage::NAME, InsecureMessage::NAME, GarbledMessage::NAME), $respCiphertext);
    if ($respMessage instanceof GarbledMessage) {
      return array(
        $respCode,
        array(
          'is_error' => 1,
          'error_message' => 'Received garbled message',
          'original_message' => $respMessage->getData(),
        ),
      );
    }
    elseif ($respMessage instanceof InsecureMessage) {
      return array(
        $respCode,
        array(
          'is_error' => 1,
          'error_message' => 'Received insecure error message',
          'original_message' => $respMessage->getData(),
        ),
      );
    }
    if ($respMessage->getCxnId() != $cxn['cxnId']) {
      // Tsk, tsk, Mallory!
      throw new \RuntimeException('Received response from incorrect connection.');
    }
    return array($respCode, $respMessage->getData());
  }

}
