<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/
namespace Civi\API;

/**
 *
 * @package Civi
 * @copyright CiviCRM LLC (c) 2004-2013
 */

class Kernel {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * @var array<APIProviderInterface>
   */
  protected $apiProviders;

  function __construct($dispatcher, $apiProviders = array()) {
    $this->apiProviders = $apiProviders;
    $this->dispatcher = $dispatcher;
  }

  /**
   * @param string $entity
   *   type of entities to deal with
   * @param string $action
   *   create, get, delete or some special action name.
   * @param array $params
   *   array to be passed to function
   * @param null $extra
   *
   * @return array|int
   */
  public function run($entity, $action, $params, $extra) {
    $apiRequest = array();
    $apiRequest['entity'] = \CRM_Utils_String::munge($entity);
    $apiRequest['action'] = \CRM_Utils_String::munge($action);
    $apiRequest['version'] = civicrm_get_api_version($params);
    $apiRequest['params'] = $params;
    $apiRequest['extra'] = $extra;

    $apiWrappers = array(
      \CRM_Utils_API_HTMLInputCoder::singleton(),
      \CRM_Utils_API_NullOutputCoder::singleton(),
      \CRM_Utils_API_ReloadOption::singleton(),
      \CRM_Utils_API_MatchOption::singleton(),
    );
    \CRM_Utils_Hook::apiWrappers($apiWrappers, $apiRequest);

    try {
      require_once ('api/v3/utils.php');
      require_once 'api/Exception.php';
      if (!is_array($params)) {
        throw new \API_Exception('Input variable `params` is not an array', 2000);
      }
      _civicrm_api3_initialize();
      $errorScope = \CRM_Core_TemporaryErrorScope::useException();
      // look up function, file, is_generic
      $apiRequest += _civicrm_api_resolve($apiRequest);
      if (strtolower($action) == 'create' || strtolower($action) == 'delete' || strtolower($action) == 'submit') {
        $apiRequest['is_transactional'] = 1;
        $transaction = new \CRM_Core_Transaction();
      }

      // support multi-lingual requests
      if ($language = \CRM_Utils_Array::value('option.language', $params)) {
        _civicrm_api_set_locale($language);
      }

      _civicrm_api3_api_check_permission($apiRequest['entity'], $apiRequest['action'], $apiRequest['params']);
      $fields = _civicrm_api3_api_getfields($apiRequest);
      // we do this before we
      _civicrm_api3_swap_out_aliases($apiRequest, $fields);
      if (strtolower($action) != 'getfields') {
        if (empty($apiRequest['params']['id'])) {
          $apiRequest['params'] = array_merge(_civicrm_api3_getdefaults($apiRequest, $fields), $apiRequest['params']);
        }
        //if 'id' is set then only 'version' will be checked but should still be checked for consistency
        civicrm_api3_verify_mandatory($apiRequest['params'], NULL, _civicrm_api3_getrequired($apiRequest, $fields));
      }

      // For input filtering, process $apiWrappers in forward order
      foreach ($apiWrappers as $apiWrapper) {
        $apiRequest = $apiWrapper->fromApiInput($apiRequest);
      }

      $function = $apiRequest['function'];
      if ($apiRequest['function'] && $apiRequest['is_generic']) {
        // Unlike normal API implementations, generic implementations require explicit
        // knowledge of the entity and action (as well as $params). Bundle up these bits
        // into a convenient data structure.
        $result = $function($apiRequest);
      }
      elseif ($apiRequest['function'] && !$apiRequest['is_generic']) {
        _civicrm_api3_validate_fields($apiRequest['entity'], $apiRequest['action'], $apiRequest['params'], $fields);

        $result = isset($extra) ? $function($apiRequest['params'], $extra) : $function($apiRequest['params']);
      }
      else {
        return civicrm_api3_create_error("API (" . $apiRequest['entity'] . ", " . $apiRequest['action'] . ") does not exist (join the API team and implement it!)");
      }

      // For output filtering, process $apiWrappers in reverse order
      foreach (array_reverse($apiWrappers) as $apiWrapper) {
        $result = $apiWrapper->toApiOutput($apiRequest, $result);
      }

      if (\CRM_Utils_Array::value('format.is_success', $apiRequest['params']) == 1) {
        if ($result['is_error'] === 0) {
          return 1;
        }
        else {
          return 0;
        }
      }
      if (!empty($apiRequest['params']['format.only_id']) && isset($result['id'])) {
        return $result['id'];
      }
      if (\CRM_Utils_Array::value('is_error', $result, 0) == 0) {
        _civicrm_api_call_nested_api($apiRequest['params'], $result, $apiRequest['action'], $apiRequest['entity'], $apiRequest['version']);
      }
      if (function_exists('xdebug_time_index')
        && \CRM_Utils_Array::value('debug', $apiRequest['params'])
        // result would not be an array for getvalue
        && is_array($result)
      ) {
        $result['xdebug']['peakMemory'] = xdebug_peak_memory_usage();
        $result['xdebug']['memory'] = xdebug_memory_usage();
        $result['xdebug']['timeIndex'] = xdebug_time_index();
      }

      return $result;
    } catch (PEAR_Exception $e) {
      if (\CRM_Utils_Array::value('format.is_success', $apiRequest['params']) == 1) {
        return 0;
      }
      $error = $e->getCause();
      if ($error instanceof DB_Error) {
        $data["error_code"] = DB::errorMessage($error->getCode());
        $data["sql"] = $error->getDebugInfo();
      }
      if (!empty($apiRequest['params']['debug'])) {
        if (method_exists($e, 'getUserInfo')) {
          $data['debug_info'] = $error->getUserInfo();
        }
        if (method_exists($e, 'getExtraData')) {
          $data['debug_info'] = $data + $error->getExtraData();
        }
        $data['trace'] = $e->getTraceAsString();
      }
      else {
        $data['tip'] = "add debug=1 to your API call to have more info about the error";
      }
      $err = civicrm_api3_create_error($e->getMessage(), $data, $apiRequest);
      if (!empty($apiRequest['is_transactional'])) {
        $transaction->rollback();
      }
      return $err;
    }
    catch (\API_Exception $e) {
      if (!isset($apiRequest)) {
        $apiRequest = array();
      }
      if (\CRM_Utils_Array::value('format.is_success', \CRM_Utils_Array::value('params', $apiRequest)) == 1) {
        return 0;
      }
      $data = $e->getExtraParams();
      $data['entity'] = \CRM_Utils_Array::value('entity', $apiRequest);
      $data['action'] = \CRM_Utils_Array::value('action', $apiRequest);
      $err = civicrm_api3_create_error($e->getMessage(), $data, $apiRequest, $e->getCode());
      if (\CRM_Utils_Array::value('debug', \CRM_Utils_Array::value('params', $apiRequest))
        && empty($data['trace']) // prevent recursion
      ) {
        $err['trace'] = $e->getTraceAsString();
      }
      if (!empty($apiRequest['is_transactional'])) {
        $transaction->rollback();
      }
      return $err;
    }
    catch (\Exception $e) {
      if (\CRM_Utils_Array::value('format.is_success', $apiRequest['params']) == 1) {
        return 0;
      }
      $data = array();
      $err = civicrm_api3_create_error($e->getMessage(), $data, $apiRequest, $e->getCode());
      if (!empty($apiRequest['params']['debug'])) {
        $err['trace'] = $e->getTraceAsString();
      }
      if (!empty($apiRequest['is_transactional'])) {
        $transaction->rollback();
      }
      return $err;
    }

  }
}