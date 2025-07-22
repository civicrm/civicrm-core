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
class CRM_SMS_BAO_SmsProvider extends CRM_SMS_DAO_SmsProvider {

  /**
   * @return int
   */
  public static function activeProviderCount(): int {
    return (int) CRM_Core_DAO::singleValueQuery('SELECT count(id) FROM civicrm_sms_provider WHERE is_active = 1 AND (domain_id = %1 OR domain_id IS NULL)',
       [1 => [CRM_Core_Config::domainID(), 'Positive']]);
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
    $dao = new CRM_SMS_DAO_SmsProvider();
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
    $id = $params['id'] ?? NULL;

    if ($id) {
      CRM_Utils_Hook::pre('edit', 'SmsProvider', $id, $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'SmsProvider', NULL, $params);
    }

    $provider = new CRM_SMS_DAO_SmsProvider();
    if ($id) {
      $provider->id = $id;
      $provider->find(TRUE);
    }
    if ($id) {
      $provider->domain_id = $params['domain_id'] ?? $provider->domain_id;
    }
    else {
      $provider->domain_id = $params['domain_id'] ?? CRM_Core_Config::domainID();
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
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_SMS_DAO_SmsProvider', $id, 'is_active', $is_active);
  }

  /**
   * @param int $providerID
   *
   * @return null
   * @throws CRM_Core_Exception
   *
   * @deprecated
   */
  public static function del($providerID) {
    if (!$providerID) {
      throw new CRM_Core_Exception(ts('Invalid value passed to delete function.'));
    }

    $dao = new CRM_SMS_DAO_SmsProvider();
    $dao->id = $providerID;
    $dao->whereAdd = "(domain_id = " . CRM_Core_Config::domainID() . "OR domain_id IS NULL)";
    if (!$dao->find(TRUE)) {
      return NULL;
    }
    // The above just filters out attempts to delete for other domains
    // Not sure it's needed, but preserves old behaviour and is deprecated.
    static::deleteRecord(['id' => $providerID]);
  }

  /**
   * @param int $providerID
   * @param string|null $returnParam
   * @param string|null $returnDefaultString
   *
   * @return mixed
   */
  public static function getProviderInfo($providerID, $returnParam = NULL, $returnDefaultString = NULL) {

    if (!isset(\Civi::$statics[__CLASS__ . __FUNCTION__][$providerID])) {

      $providerInfo = [];

      $dao = new CRM_SMS_DAO_SmsProvider();
      $dao->id = $providerID;
      if ($dao->find(TRUE)) {
        CRM_Core_DAO::storeValues($dao, $providerInfo);
        $inputLines = explode("\n", $providerInfo['api_params']);
        $inputVals = [];
        foreach ($inputLines as $value) {
          if ($value) {
            list($key, $val) = explode("=", $value);
            $inputVals[trim($key)] = trim($val);
          }
        }
        $providerInfo['api_params'] = $inputVals;

        // Replace the api_type ID with the string value
        $apiTypes = CRM_Core_OptionGroup::values('sms_api_type');
        $apiTypeId = $providerInfo['api_type'];
        $providerInfo['api_type'] = $apiTypes[$apiTypeId] ?? $apiTypeId;
      }
      \Civi::$statics[__CLASS__ . __FUNCTION__][$providerID] = $providerInfo;
    }

    if ($returnParam) {
      return \Civi::$statics[__CLASS__ . __FUNCTION__][$providerID][$returnParam] ?? $returnDefaultString;
    }
    return \Civi::$statics[__CLASS__ . __FUNCTION__][$providerID];
  }

}
