<?php

class CRM_Cxn_CiviCxnStore implements Civi\Cxn\Rpc\CxnStore\CxnStoreInterface {

  protected $cxns = array();

  public function getAll() {
    if (!$this->cxns) {
      $this->cxns = array();
      $dao = new CRM_Cxn_DAO_Cxn();
      $dao->find();
      while ($dao->fetch()) {
        $cxn = $this->convertDaoToCxn($dao);
        $this->cxns[$cxn['cxnId']] = $cxn;
      }
    }
    return $this->cxns;
  }

  public function getByCxnId($cxnId) {
    if (isset($this->cxns[$cxnId])) {
      return $this->cxns[$cxnId];
    }
    $dao = new CRM_Cxn_DAO_Cxn();
    $dao->cxn_id = $cxnId;
    if ($dao->find(TRUE)) {
      $this->cxns[$cxnId] = $this->convertDaoToCxn($dao);
      return $this->cxns[$cxnId];
    }
    else {
      return NULL;
    }
  }

  public function getByAppId($appId) {
    $dao = new CRM_Cxn_DAO_Cxn();
    $dao->app_id = $appId;
    if ($dao->find(TRUE)) {
      $this->cxns[$dao->cxn_id] = $this->convertDaoToCxn($dao);
      return $this->cxns[$dao->cxn_id];
    }
    else {
      return NULL;
    }
  }

  public function add($cxn) {
    $dao = new CRM_Cxn_DAO_Cxn();
    $dao->cxn_id = $cxn['cxnId'];
    $dao->find(TRUE);
    $this->convertCxnToDao($cxn, $dao);
    $dao->save();
    $this->cxns[$cxn['cxnId']] = $cxn;
  }

  public function remove($cxnId) {
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_cxn WHERE cxn_id = %1', array(
      1 => array($cxnId, 'String'),
    ));
    unset($this->cxns[$cxnId]);
  }

  /**
   * @param CRM_Cxn_DAO_Cxn $dao
   * @return array
   */
  protected function convertDaoToCxn($dao) {
    $appMeta = json_decode($dao->app_meta, TRUE);
    return array(
      'cxnId' => $dao->cxn_id,
      'secret' => $dao->secret,
      'appId' => $dao->app_id,
      'appUrl' => $appMeta['appUrl'],
      'siteUrl' => CRM_Cxn_BAO_Cxn::getSiteCallbackUrl(),
      'perm' => json_decode($dao->perm, TRUE),
    );
  }

  /**
   * @param CRM_Cxn_DAO_Cxn $dao
   */
  protected function convertCxnToDao($cxn, $dao) {
    $dao->cxn_id = $cxn['cxnId'];
    $dao->secret = $cxn['secret'];
    $dao->app_id = $cxn['appId'];
    $dao->perm = json_encode($cxn['perm']);

    // Note: we don't save siteUrl because it's more correct to regenerate on-demand.
    // Note: we don't save appUrl, but other processes will update appMeta.
  }
}
