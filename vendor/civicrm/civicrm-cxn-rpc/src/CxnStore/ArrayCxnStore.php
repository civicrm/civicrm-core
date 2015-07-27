<?php
namespace Civi\Cxn\Rpc\CxnStore;

class ArrayCxnStore implements CxnStoreInterface {

  protected $cxns = array();

  public function getAll() {
    return $this->cxns;
  }

  public function getByCxnId($cxnId) {
    return isset($this->cxns[$cxnId]) ? $this->cxns[$cxnId] : NULL;
  }

  public function getByAppId($appId) {
    foreach ($this->cxns as $cxn) {
      if ($appId == $cxn['appId']) {
        return $cxn;
      }
    }
    return NULL;
  }

  public function add($cxn) {
    $this->cxns[$cxn['cxnId']] = $cxn;
  }

  public function remove($cxnId) {
    unset($this->cxns[$cxnId]);
  }

}
