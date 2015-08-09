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

interface CxnStoreInterface {

  /**
   * Return a list of all Cxns.
   *
   * @return array
   *   Array(string $cxnId => array $cxn).
   */
  public function getAll();

  /**
   * @param string $cxnId
   * @return array|NULL
   *   Zero or one matching Cxn's (array-encoded).
   * @see Cxn::validate
   */
  public function getByCxnId($cxnId);

  /**
   * @param $appId
   * @return array|NULL
   *   Zero or one matching Cxn's (array-encoded).
   * @see Cxn::validate
   */
  public function getByAppId($appId);

  /**
   * @param array $cxn
   *   An array-encoded Cxn.
   * @see Cxn::validate
   */
  public function add($cxn);

  /**
   * @param string $cxnId
   */
  public function remove($cxnId);

}
