<?php

/**
 * Class CRM_Cxn_CiviCxnStore
 */
class CRM_Cxn_CiviCxnStore implements Civi\Cxn\Rpc\CxnStore\CxnStoreInterface {

  protected $cxns = array();

  /**
   * @inheritDoc
   */
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

  /**
   * @inheritDoc
   */
  public function getByCxnId($cxnId) {
    if (isset($this->cxns[$cxnId])) {
      return $this->cxns[$cxnId];
    }
    $dao = new CRM_Cxn_DAO_Cxn();
    $dao->cxn_guid = $cxnId;
    if ($dao->find(TRUE)) {
      $this->cxns[$cxnId] = $this->convertDaoToCxn($dao);
      return $this->cxns[$cxnId];
    }
    else {
      return NULL;
    }
  }

  /**
   * @inheritDoc
   */
  public function getByAppId($appId) {
    $dao = new CRM_Cxn_DAO_Cxn();
    $dao->app_guid = $appId;
    if ($dao->find(TRUE)) {
      $this->cxns[$dao->cxn_guid] = $this->convertDaoToCxn($dao);
      return $this->cxns[$dao->cxn_guid];
    }
    else {
      return NULL;
    }
  }

  /**
   * @inheritDoc
   */
  public function add($cxn) {
    $dao = new CRM_Cxn_DAO_Cxn();
    $dao->cxn_guid = $cxn['cxnId'];
    $dao->find(TRUE);
    $this->convertCxnToDao($cxn, $dao);
    $dao->save();

    $sql = '
      UPDATE civicrm_cxn SET created_date = modified_date
      WHERE created_date IS NULL
      AND cxn_guid = %1
      ';
    CRM_Core_DAO::executeQuery($sql, array(
      1 => array($cxn['cxnId'], 'String'),
    ));

    $this->cxns[$cxn['cxnId']] = $cxn;
  }

  /**
   * @inheritDoc
   */
  public function remove($cxnId) {
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_cxn WHERE cxn_guid = %1', array(
      1 => array($cxnId, 'String'),
    ));
    unset($this->cxns[$cxnId]);
  }

  /**
   * @param CRM_Cxn_DAO_Cxn $dao
   * @return array
   *   Array-encoded connection details.
   */
  protected function convertDaoToCxn($dao) {
    $appMeta = json_decode($dao->app_meta, TRUE);
    return array(
      'cxnId' => $dao->cxn_guid,
      'secret' => $dao->secret,
      'appId' => $dao->app_guid,
      'appUrl' => $appMeta['appUrl'],
      'siteUrl' => CRM_Cxn_BAO_Cxn::getSiteCallbackUrl(),
      'perm' => json_decode($dao->perm, TRUE),
    );
  }

  /**
   * @param array $cxn
   *   Array-encoded connection details.
   * @param CRM_Cxn_DAO_Cxn $dao
   */
  protected function convertCxnToDao($cxn, $dao) {
    $dao->cxn_guid = $cxn['cxnId'];
    $dao->secret = $cxn['secret'];
    $dao->app_guid = $cxn['appId'];
    $dao->perm = json_encode($cxn['perm']);

    // Note: we don't save siteUrl because it's more correct to regenerate on-demand.
    // Note: we don't save appUrl, but other processes will update appMeta.
  }

}
