<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc\CxnStore;

/**
 * Class JsonFileCxnStore
 *
 * This is a very simple implementation. DO NOT USE IN PRODUCTION. It is not multithread safe.
 *
 * @package Civi\Cxn\Rpc\CxnStore
 */
class JsonFileCxnStore implements CxnStoreInterface {

  private $file;

  private $cache;

  public function __construct($file) {
    $this->file = $file;
  }

  public function getCache() {
    if (!$this->cache) {
      $this->cache = $this->load();
    }
    return $this->cache;
  }

  public function getAll() {
    return $this->getCache();
  }

  public function getByCxnId($cxnId) {
    $cache = $this->getCache();
    return isset($cache[$cxnId]) ? $cache[$cxnId] : NULL;
  }

  public function getByAppId($appId) {
    $cache = $this->getCache();
    foreach ($cache as $cxn) {
      if ($cxn['appId'] == $appId) {
        return $cxn;
      }
    }
    return NULL;
  }

  public function add($cxn) {
    $data = $this->load();
    $data[$cxn['cxnId']] = $cxn;
    $this->save($data);
  }

  public function remove($cxnId) {
    $data = $this->load();
    if (isset($data[$cxnId])) {
      unset($data[$cxnId]);
      $this->save($data);
    }
  }

  /**
   * @return mixed
   */
  public function load() {
    return json_decode(file_get_contents($this->file), TRUE);
  }

  public function save($data) {
    file_put_contents($this->file, json_encode($data));
  }

}
