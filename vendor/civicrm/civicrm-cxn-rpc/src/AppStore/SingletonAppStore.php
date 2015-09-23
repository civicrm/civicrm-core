<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc\AppStore;

use Civi\Cxn\Rpc\AppMeta;

class SingletonAppStore implements AppStoreInterface {

  private $appId;

  private $appMeta;

  private $publicKey;

  private $privateKey;

  public function __construct($appId, $appMeta, $privateKey, $publicKey) {
    AppMeta::validate($appMeta);
    $this->appId = $appId;
    $this->appMeta = $appMeta;
    $this->privateKey = $privateKey;
    $this->publicKey = $publicKey;
  }

  public function getAppIds() {
    return array($this->appId);
  }

  public function getAppMeta($appId) {
    if ($appId == $this->appId) {
      return $this->appMeta;
    }
    else {
      return NULL;
    }
  }

  public function getPublicKey($appId) {
    if ($appId == $this->appId) {
      return $this->publicKey;
    }
    else {
      return NULL;
    }
  }

  public function getPrivateKey($appId) {
    if ($appId == $this->appId) {
      return $this->privateKey;
    }
    else {
      return NULL;
    }
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return array
   *   Array with elements:
   *     - publickey: string, pem.
   *     - privatekey: string, pem
   */
  public function getKeyPair($appId) {
    if ($appId == $this->appId) {
      return array(
        'publickey' => $this->getPublicKey($appId),
        'privatekey' => $this->getPrivateKey($appId),
      );
    }
    else {
      return NULL;
    }
  }

}
