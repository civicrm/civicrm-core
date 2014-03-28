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
use Civi\API\Event\ResolveEvent;
use Civi\API\Event\RespondEvent;
use Civi\API\Provider\ProviderInterface;

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
   * @var array<ProviderInterface>
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
    /**
     * @var $apiProvider \Civi\API\Provider\ProviderInterface|NULL
     */
    $apiProvider = NULL;

    // TODO Define alternative calling convention makes it easier to construct $apiRequest
    // without the ambiguity of "data" vs "options"
    $apiRequest = $this->createRequest($entity, $action, $params, $extra);

    try {
      if (!is_array($params)) {
        throw new \API_Exception('Input variable `params` is not an array', 2000);
      }

      $this->boot();
      $errorScope = \CRM_Core_TemporaryErrorScope::useException();

      list($apiProvider, $apiRequest) = $this->resolve($apiRequest);
      $this->authorize($apiProvider, $apiRequest);
      $apiRequest = $this->prepare($apiProvider, $apiRequest);
      $result = $apiProvider->invoke($apiRequest);

      if (\CRM_Utils_Array::value('is_error', $result, 0) == 0) {
        _civicrm_api_call_nested_api($apiRequest['params'], $result, $apiRequest['action'], $apiRequest['entity'], $apiRequest['version']);
      }

      $apiResponse = $this->respond($apiProvider, $apiRequest, $result);
      return $this->formatResult($apiRequest, $apiResponse);
    }
    catch (\Exception $e) {
      $this->dispatcher->dispatch(Events::EXCEPTION, new ExceptionEvent($e, $apiProvider, $apiRequest));

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
   * @return array the request descriptor; keys:
   *   - version: int
   *   - entity: string
   *   - action: string
   *   - params: array (string $key => mixed $value) [deprecated in v4]
   *   - extra: unspecified
   *   - fields: NULL|array (string $key => array $fieldSpec)
   *   - options: \CRM_Utils_OptionBag derived from params [v4-only]
   *   - data: \CRM_Utils_OptionBag derived from params [v4-only]
   *   - chains: unspecified derived from params [v4-only]
   */
  public function createRequest($entity, $action, $params, $extra) {
    $apiRequest = array(); // new \Civi\API\Request();
    $apiRequest['version'] = civicrm_get_api_version($params);
    $apiRequest['params'] = $params;
    $apiRequest['extra'] = $extra;
    $apiRequest['fields'] = NULL;

    if ($apiRequest['version'] <= 3) {
      // APIv1-v3 munges entity/action names, which means that the same name can be written
      // multiple ways. That makes it harder to work with.
      $apiRequest['entity'] = \CRM_Utils_String::munge($entity);
      $apiRequest['action'] = \CRM_Utils_String::munge($action);
    }
    else {
      // APIv4 requires exact entity/action name; deviations should cause errors
      if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $entity)) {
        throw new \API_Exception("Malformed entity");
      }
      if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $action)) {
        throw new \API_Exception("Malformed action");
      }
      $apiRequest['entity'] = $entity;
      $apiRequest['action'] = $action;
    }

    // APIv1-v3 mix data+options in $params which means that each API callback is responsible
    // for splitting the two. In APIv4, the split is done systematically so that we don't
    // so much parsing logic spread around.
    if ($apiRequest['version'] >= 4) {
      $options = array();
      $data = array();
      $chains = array();
      foreach ($params as $key => $value) {
        if ($key == 'options') {
          $options = array_merge($options, $value);
        }
        elseif ($key == 'return') {
          if (!isset($options['return'])) {
            $options['return'] = array();
          }
          $options['return'] = array_merge($options['return'], $value);
        }
        elseif (preg_match('/^option\.(.*)$/', $key, $matches)) {
          $options[$matches[1]] = $value;
        }
        elseif (preg_match('/^return\.(.*)$/', $key, $matches)) {
          if ($value) {
            if (!isset($options['return'])) {
              $options['return'] = array();
            }
            $options['return'][] = $matches[1];
          }
        }
        elseif (preg_match('/^format\.(.*)$/', $key, $matches)) {
          if ($value) {
            if (!isset($options['format'])) {
              $options['format'] = $matches[1];
            }
            else {
              throw new \API_Exception("Too many API formats specified");
            }
          }
        }
        elseif (preg_match('/^api\./', $key)) {
          // FIXME: represent subrequests as instances of "Request"
          $chains[$key] = $value;
        }
        elseif ($key == 'debug') {
          $options['debug'] = $value;
        }
        elseif ($key == 'version') {
          // ignore
        }
        else {
          $data[$key] = $value;

        }
      }
      $apiRequest['options'] = new \CRM_Utils_OptionBag($options);
      $apiRequest['data'] = new \CRM_Utils_OptionBag($data);
      $apiRequest['chains'] = $chains;
    }

    return $apiRequest;
  }

  public function boot() {
    require_once ('api/v3/utils.php');
    require_once 'api/Exception.php';
    _civicrm_api3_initialize();
  }

  /**
   * Determine which, if any, service will execute the API request.
   *
   * @param $apiRequest
   * @return array
   * @throws \API_Exception
   */
  public function resolve($apiRequest) {
    $resolveEvent = $this->dispatcher->dispatch(Events::RESOLVE, new ResolveEvent($apiRequest));
    $apiRequest = $resolveEvent->getApiRequest();
    if (!$resolveEvent->getApiProvider()) {
      throw new \API_Exception("API (" . $apiRequest['entity'] . ", " . $apiRequest['action'] . ") does not exist (join the API team and implement it!)", \API_Exception::NOT_IMPLEMENTED);
    }
    return array($resolveEvent->getApiProvider(), $apiRequest);
  }

  /**
   * Determine if the API request is allowed (under current policy)
   *
   * @param ProviderInterface $apiProvider
   * @param array $apiRequest
   * @throws \API_Exception
   */
  public function authorize($apiProvider, $apiRequest) {
    $event = $this->dispatcher->dispatch(Events::AUTHORIZE, new AuthorizeEvent($apiProvider, $apiRequest));
    if (!$event->isAuthorized()) {
      throw new \API_Exception("Authorization failed", \API_Exception::UNAUTHORIZED);
    }
  }

  /**
   * Allow third-party code to manipulate the API request before execution.
   *
   * @param ProviderInterface $apiProvider
   * @param array $apiRequest
   * @return mixed
   */
  public function prepare($apiProvider, $apiRequest) {
    $event = $this->dispatcher->dispatch(Events::PREPARE, new PrepareEvent($apiProvider, $apiRequest));
    return $event->getApiRequest();
  }

  /**
   * Allow third-party code to manipulate the API response after execution.
   *
   * @param ProviderInterface $apiProvider
   * @param array $apiRequest
   * @param array $result
   * @return mixed
   */
  public function respond($apiProvider, $apiRequest, $result) {
    $event = $this->dispatcher->dispatch(Events::RESPOND, new RespondEvent($apiProvider, $apiRequest, $result));
    return $event->getResponse();
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
    return $this->createError($e->getMessage(), $data, $apiRequest, $e->getCode());
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

    return $this->createError($e->getMessage(), $data, $apiRequest, $e->getCode());
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

    return $this->createError($e->getMessage(), $data, $apiRequest);
  }

  /**
   *
   * @param <type> $data
   * @param array $data
   * @param object $apiRequest DAO / BAO object to be freed here
   *
   * @throws API_Exception
   * @return array <type>
   */
  function createError($msg, $data, $apiRequest, $code = NULL) {
    // FIXME what to do with $code?
    if ($msg == 'DB Error: constraint violation' || substr($msg, 0, 9) == 'DB Error:' || $msg == 'DB Error: already exists') {
      try {
        $fields = _civicrm_api3_api_getfields($apiRequest);
        _civicrm_api3_validate_fields($apiRequest['entity'], $apiRequest['action'], $apiRequest['params'], $fields, TRUE);
      } catch (Exception $e) {
        $msg = $e->getMessage();
      }
    }

    $data = civicrm_api3_create_error($msg, $data);

    if (isset($apiRequest['params']) && is_array($apiRequest['params']) && !empty($apiRequest['params']['api.has_parent'])) {
      $errorCode = empty($data['error_code']) ? 'chained_api_failed' : $data['error_code'];
      throw new \API_Exception('Error in call to ' . $apiRequest['entity'] . '_' . $apiRequest['action'] . ' : ' . $msg, $errorCode, $data);
    }

    return $data;
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