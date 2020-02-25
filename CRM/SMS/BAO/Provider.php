<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_SMS_BAO_Provider extends CRM_SMS_DAO_Provider {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * @return null|string
   */
  public static function activeProviderCount() {
    $activeProviders = CRM_Core_DAO::singleValueQuery('SELECT count(id) FROM civicrm_sms_provider WHERE is_active = 1 AND (domain_id = %1 OR domain_id IS NULL)',
       [1 => [CRM_Core_Config::domainID(), 'Positive']]);
    return $activeProviders;
  }

  /**
   * Retrieves the list of providers from the database.
   *
   * $selectArr array of coloumns to fetch
   * $getActive boolean to get active providers
   *
   * @param null $selectArr
   * @param null $filter
   * @param bool $getActive
   * @param string $orderBy
   *
   * @return array
   */
  public static function getProviders($selectArr = NULL, $filter = NULL, $getActive = TRUE, $orderBy = 'id') {

    $providers = [];
    $temp = [];
    $dao = new CRM_SMS_DAO_Provider();
    if ($filter && !array_key_exists('is_active', $filter) && $getActive) {
      $dao->is_active = 1;
    }
    if ($filter && is_array($filter)) {
      foreach ($filter as $key => $value) {
        $dao->$key = $value;
      }
    }
    if ($selectArr && is_array($selectArr)) {
      $select = implode(',', $selectArr);
      $dao->selectAdd($select);
    }
    $dao->whereAdd("(domain_id = " . CRM_Core_Config::domainID() . " OR domain_id IS NULL)");
    $dao->orderBy($orderBy);
    $dao->find();
    while ($dao->fetch()) {
      CRM_Core_DAO::storeValues($dao, $temp);
      $providers[$dao->id] = $temp;
    }
    return $providers;
  }

  /**
   * Create or Update an SMS provider
   * @param array $params
   * @return array saved values
   */
  public static function create(&$params) {
    $id = CRM_Utils_Array::value('id', $params);

    if ($id) {
      CRM_Utils_Hook::pre('edit', 'SmsProvider', $id, $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'SmsProvider', NULL, $params);
    }

    $provider = new CRM_SMS_DAO_Provider();
    if ($id) {
      $provider->id = $id;
      $provider->find(TRUE);
    }
    if ($id) {
      $provider->domain_id = CRM_Utils_Array::value('domain_id', $params, $provider->domain_id);
    }
    else {
      $provider->domain_id = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());
    }
    $provider->copyValues($params);
    $result = $provider->save();
    if ($id) {
      CRM_Utils_Hook::post('edit', 'SmsProvider', $provider->id, $provider);
    }
    else {
      CRM_Utils_Hook::post('create', 'SmsProvider', NULL, $provider);
    }
    return $result;
  }

  /**
   * @param int $id
   * @param $is_active
   *
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_SMS_DAO_Provider', $id, 'is_active', $is_active);
  }

  /**
   * @param int $providerID
   *
   * @return null
   * @throws CRM_Core_Exception
   */
  public static function del($providerID) {
    if (!$providerID) {
      throw new CRM_Core_Exception(ts('Invalid value passed to delete function.'));
    }

    $dao = new CRM_SMS_DAO_Provider();
    $dao->id = $providerID;
    $dao->whereAdd = "(domain_id = " . CRM_Core_Config::domainID() . "OR domain_id IS NULL)";
    if (!$dao->find(TRUE)) {
      return NULL;
    }
    $dao->delete();
  }

  /**
   * @param int $providerID
   * @param null $returnParam
   * @param null $returnDefaultString
   *
   * @return mixed
   */
  public static function getProviderInfo($providerID, $returnParam = NULL, $returnDefaultString = NULL) {
    static $providerInfo = [];

    if (!array_key_exists($providerID, $providerInfo)) {
      $providerInfo[$providerID] = [];

      $dao = new CRM_SMS_DAO_Provider();
      $dao->id = $providerID;
      if ($dao->find(TRUE)) {
        CRM_Core_DAO::storeValues($dao, $providerInfo[$providerID]);
        $inputLines = explode("\n", $providerInfo[$providerID]['api_params']);
        $inputVals = [];
        foreach ($inputLines as $value) {
          if ($value) {
            list($key, $val) = explode("=", $value);
            $inputVals[trim($key)] = trim($val);
          }
        }
        $providerInfo[$providerID]['api_params'] = $inputVals;

        // Replace the api_type ID with the string value
        $apiTypes = CRM_Core_OptionGroup::values('sms_api_type');
        $apiTypeId = $providerInfo[$providerID]['api_type'];
        $providerInfo[$providerID]['api_type'] = CRM_Utils_Array::value($apiTypeId, $apiTypes, $apiTypeId);
      }
    }

    if ($returnParam) {
      return CRM_Utils_Array::value($returnParam, $providerInfo[$providerID], $returnDefaultString);
    }
    return $providerInfo[$providerID];
  }

}
