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

use Civi\Cxn\Rpc\AppStore\SingletonAppStore;
use Civi\Cxn\Rpc\Exception\CxnException;
use Civi\Cxn\Rpc\Exception\InvalidMessageException;
use Civi\Cxn\Rpc\Message\InsecureMessage;
use Civi\Cxn\Rpc\Message\RegistrationMessage;
use Civi\Cxn\Rpc\Message\StdMessage;

/**
 * Class RegistrationServer
 *
 * A registration accepts registration messages and updates a list of
 * active connections.
 *
 * @package Civi\Cxn\Rpc
 */
class RegistrationServer extends Agent {

  /**
   * @param array $appMeta
   * @param array $keyPair
   * @param CxnStore\CxnStoreInterface $cxnStore
   *
   * TODO Change contract, passing in AppStoreInterface instead of appMeta/keyPair.
   * This will allow hosting multiple apps in the same endpoint.
   */
  public function __construct($appMeta, $keyPair, $cxnStore) {
    if (empty($keyPair)) {
      throw new CxnException("Missing keyPair");
    }
    if (empty($keyPair)) {
      throw new CxnException("Missing cxnStore");
    }

    parent::__construct(NULL, $cxnStore);

    $this->appStore = new SingletonAppStore($appMeta['appId'], $appMeta, $keyPair['privatekey'], $keyPair['publickey']);
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
      $reqData = $this->decode(RegistrationMessage::NAME, $blob);
    }
    catch (InvalidMessageException $e) {
      $this->log->warning('Received invalid message', array(
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

    $this->log->debug('Received registration request', array(
      'reqData' => $reqData,
    ));
    $cxn = $reqData['cxn'];
    $validation = Cxn::getValidationMessages($cxn);
    if (!empty($validation)) {
      // $cxn is not valid, so we can't use it for encoding.
      $resp = new InsecureMessage(array(
        'is_error' => 1,
        'error_message' => 'Invalid cxn details: ' . implode(', ', array_keys($validation)),
      ));
      return $resp->setCode(400);
    }

    $respData = $this->call($reqData);
    $this->log->debug('Responding', array($cxn['cxnId'], $cxn['secret'], $respData));
    return new StdMessage($cxn['cxnId'], $cxn['secret'], $respData);
  }

  /**
   * Delegate handling of hte registration message to a callback function.
   *
   * @param $reqData
   * @return array|mixed
   */
  public function call($reqData) {
    $respData = $this->createError('Unrecognized entity or action');

    if ($reqData['entity'] == 'Cxn' && preg_match('/^[a-zA-Z]+$/', $reqData['action'])) {
      $func = 'on' . $reqData['entity'] . strtoupper($reqData['action']{0}) . substr($reqData['action'], 1);
      if (is_callable(array($this, $func))) {
        $respData = call_user_func(array($this, $func), $reqData['cxn'], $reqData['params']);
      }
    }

    return $respData;
  }

  /**
   * Callback for Cxn.register.
   *
   * @param array $cxn
   *   The CXN record submitted by the client.
   * @param array $params
   *   Additional parameters from the client.
   * @return array
   */
  public function onCxnRegister($cxn, $params) {
    $storedCxn = $this->cxnStore->getByCxnId($cxn['cxnId']);

    if (!$storedCxn || $storedCxn['secret'] == $cxn['secret']) {
      $this->log->notice('Register cxnId="{cxnId}" siteUrl={siteUrl}: OK', array(
        'cxnId' => $cxn['cxnId'],
        'siteUrl' => $cxn['siteUrl'],
      ));
      $this->cxnStore->add($cxn);
      return $this->createSuccess(array(
        'cxn_id' => $cxn['cxnId'],
      ));
    }
    else {
      $this->log->warning('Register cxnId="{cxnId}" siteUrl="{siteUrl}": Secret does not match.', array(
        'cxnId' => $cxn['cxnId'],
        'siteUrl' => $cxn['siteUrl'],
      ));
      $this->createError('Secret does not match previous registration.');
    }
  }

  /**
   * Callback for Cxn.unregister.
   *
   * @param array $cxn
   *   The CXN record submitted by the client.
   * @param array $params
   *   Additional parameters from the client.
   * @return array
   */
  public function onCxnUnregister($cxn, $params) {
    $storedCxn = $this->cxnStore->getByCxnId($cxn['cxnId']);
    if (!$storedCxn) {
      $this->log->warning('Unregister cxnId="{cxnId} siteUrl="{siteUrl}"": Non-existent', array(
        'cxnId' => $cxn['cxnId'],
        'siteUrl' => $cxn['siteUrl'],
      ));
      return $this->createSuccess(array(
        'cxn_id' => $cxn['cxnId'],
      ));
    }
    elseif ($storedCxn['secret'] == $cxn['secret']) {
      $this->log->notice('Unregister cxnId="{cxnId} siteUrl="{siteUrl}": OK"', array(
        'cxnId' => $cxn['cxnId'],
        'siteUrl' => $cxn['siteUrl'],
      ));
      $this->cxnStore->remove($cxn['cxnId']);
      return $this->createSuccess(array(
        'cxn_id' => $cxn['cxnId'],
      ));
    }
    else {
      $this->log->warning('Unregister cxnId="{cxnId}" siteUrl="{siteUrl}": Secret does not match.', array(
        'cxnId' => $cxn['cxnId'],
        'siteUrl' => $cxn['siteUrl'],
      ));
      $this->createError('Incorrect cxnId or secret.');
    }
  }

}
