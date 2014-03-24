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

use Civi\API\Event\AuthorizeEvent;
use Civi\API\Event\PrepareEvent;
use Civi\API\Event\ExceptionEvent;
use Civi\API\Event\RespondEvent;

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
    $apiRequest = $this->createRequest($entity, $action, $params, $extra);

    try {
      if (!is_array($params)) {
        throw new \API_Exception('Input variable `params` is not an array', 2000);
      }

      $this->boot();
      $errorScope = \CRM_Core_TemporaryErrorScope::useException();

      // look up function, file, is_generic
      $apiRequest += _civicrm_api_resolve($apiRequest);

      if (! $this->dispatcher->dispatch(Events::AUTHORIZE, new AuthorizeEvent(NULL, $apiRequest))->isAuthorized()) {
        throw new \API_Exception("Authorization failed");
      }

      $apiRequest = $this->dispatcher->dispatch(Events::PREPARE, new PrepareEvent(NULL, $apiRequest))->getApiRequest();

      $function = $apiRequest['function'];
      if ($apiRequest['function'] && $apiRequest['is_generic']) {
        // Unlike normal API implementations, generic implementations require explicit
        // knowledge of the entity and action (as well as $params). Bundle up these bits
        // into a convenient data structure.
        $result = $function($apiRequest);
      }
      elseif ($apiRequest['function'] && !$apiRequest['is_generic']) {
        $result = isset($extra) ? $function($apiRequest['params'], $extra) : $function($apiRequest['params']);
      }
      else {
        throw new \API_Exception("API (" . $apiRequest['entity'] . ", " . $apiRequest['action'] . ") does not exist (join the API team and implement it!)");
      }

      if (\CRM_Utils_Array::value('is_error', $result, 0) == 0) {
        _civicrm_api_call_nested_api($apiRequest['params'], $result, $apiRequest['action'], $apiRequest['entity'], $apiRequest['version']);
      }

      $responseEvent = $this->dispatcher->dispatch(Events::RESPOND, new RespondEvent(NULL, $apiRequest, $result));
      return $this->formatResult($apiRequest, $responseEvent->getResponse());
    }
    catch (\Exception $e) {
      $this->dispatcher->dispatch(Events::EXCEPTION, new ExceptionEvent($e, NULL, $apiRequest));

      if ($e instanceof \PEAR_Exception) {
        $err = $this->formatPearException($e, $apiRequest);
      } elseif ($e instanceof \API_Exception) {
        $err = $this->formatApiException($e, $apiRequest);
      } else {
        $err = $this->formatException($e, $apiRequest);
      }

      return $this->formatResult($apiRequest, $err);
    }

  }

  /**
   * Create a formatted/normalized request object.
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param mixed $extra
   * @return array the request descriptor
   */
  public function createRequest($entity, $action, $params, $extra) {
    $apiRequest = array();
    $apiRequest['entity'] = \CRM_Utils_String::munge($entity);
    $apiRequest['action'] = \CRM_Utils_String::munge($action);
    $apiRequest['version'] = civicrm_get_api_version($params);
    $apiRequest['params'] = $params;
    $apiRequest['extra'] = $extra;
    $apiRequest['fields'] = NULL;
    return $apiRequest;
  }

  public function boot() {
    require_once ('api/v3/utils.php');
    require_once 'api/Exception.php';
    _civicrm_api3_initialize();
  }

  /**
   * @param \Exception $e
   * @param array $apiRequest
   * @return array (API response)
   */
  public function formatException($e, $apiRequest) {
    $data = array();
    if (!empty($apiRequest['params']['debug'])) {
      $data['trace'] = $e->getTraceAsString();
    }
    return civicrm_api3_create_error($e->getMessage(), $data, $apiRequest, $e->getCode());
  }

  /**
   * @param \API_Exception $e
   * @param array $apiRequest
   * @return array (API response)
   */
  public function formatApiException($e, $apiRequest) {
    $data = $e->getExtraParams();
    $data['entity'] = \CRM_Utils_Array::value('entity', $apiRequest);
    $data['action'] = \CRM_Utils_Array::value('action', $apiRequest);

    if (\CRM_Utils_Array::value('debug', \CRM_Utils_Array::value('params', $apiRequest))
      && empty($data['trace']) // prevent recursion
    ) {
      $data['trace'] = $e->getTraceAsString();
    }

    return civicrm_api3_create_error($e->getMessage(), $data, $apiRequest, $e->getCode());
  }

  /**
   * @param \PEAR_Exception $e
   * @param array $apiRequest
   * @return array (API response)
   */
  public function formatPearException($e, $apiRequest) {
    $data = array();
    $error = $e->getCause();
    if ($error instanceof \DB_Error) {
      $data["error_code"] = \DB::errorMessage($error->getCode());
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

    return civicrm_api3_create_error($e->getMessage(), $data, $apiRequest);
  }

  /**
   * @return mixed
   */
  public function formatResult($apiRequest, $result) {
    if (isset($apiRequest, $apiRequest['params'])) {
      if (isset($apiRequest['params']['format.is_success']) && $apiRequest['params']['format.is_success'] == 1) {
        return (empty($result['is_error'])) ? 1 : 0;
      }

      if (!empty($apiRequest['params']['format.only_id']) && isset($result['id'])) {
        // FIXME dispatch
        return $result['id'];
      }
    }
    return $result;
  }
}