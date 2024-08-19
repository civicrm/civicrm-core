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
namespace Civi\API;

use Civi\API\Event\AuthorizeEvent;
use Civi\API\Event\PrepareEvent;
use Civi\API\Event\ExceptionEvent;
use Civi\API\Event\ResolveEvent;
use Civi\API\Event\RespondEvent;

/**
 * @package Civi
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class Kernel {

  /**
   * @var \Civi\Core\CiviEventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * @var \Civi\API\Provider\ProviderInterface[]
   */
  protected $apiProviders;

  /**
   * @param \Civi\Core\CiviEventDispatcherInterface $dispatcher
   * @param \Civi\API\Provider\ProviderInterface[] $apiProviders
   */
  public function __construct($dispatcher, $apiProviders = []) {
    $this->apiProviders = $apiProviders;
    $this->dispatcher = $dispatcher;
  }

  /**
   * @param string $entity
   *   Name of entity: e.g. Contact, Activity, Event
   * @param string $action
   *   Name of action: e.g. create, get, delete
   * @param array $params
   *   Array to be passed to API function.
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   * @see runSafe
   * @deprecated
   */
  public function run($entity, $action, $params) {
    return $this->runSafe($entity, $action, $params);
  }

  /**
   * Parse and execute an API request. Any errors will be converted to
   * normal format.
   *
   * @param string $entity
   *   Name of entity: e.g. Contact, Activity, Event
   * @param string $action
   *   Name of action: e.g. create, get, delete
   * @param array $params
   *   Array to be passed to API function.
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  public function runSafe($entity, $action, $params) {
    $apiRequest = [];
    try {
      $apiRequest = Request::create($entity, $action, $params);
      $apiResponse = $this->runRequest($apiRequest);
      return $this->formatResult($apiRequest, $apiResponse);
    }
    catch (\Exception $e) {
      if ($e instanceof \CRM_Core_Exception) {
        $err = $this->formatApiException($e, $apiRequest);
      }
      elseif ($e instanceof \PEAR_Exception) {
        $err = $this->formatPearException($e, $apiRequest);
      }
      else {
        $err = $this->formatException($e, $apiRequest);
      }

      return $this->formatResult($apiRequest, $err);
    }
  }

  /**
   * Determine if a hypothetical API call would be authorized.
   *
   * @param string $entity
   *   Type of entities to deal with.
   * @param string $action
   *   Create, get, delete or some special action name.
   * @param array $params
   *   Array to be passed to function.
   *
   * @return bool
   *   TRUE if authorization would succeed.
   * @throws \Exception
   */
  public function runAuthorize($entity, $action, $params) {
    $apiProvider = NULL;
    $apiRequest = Request::create($entity, $action, $params);

    try {
      $this->boot($apiRequest);
      [$apiProvider, $apiRequest] = $this->resolve($apiRequest);
      $this->authorize($apiProvider, $apiRequest);
      return TRUE;
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      return FALSE;
    }
  }

  /**
   * Execute an API v3 or v4 request.
   *
   * The request must be in canonical format. Exceptions will be propagated out.
   *
   * @param array|\Civi\Api4\Generic\AbstractAction $apiRequest
   * @return array|\Civi\Api4\Generic\Result
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function runRequest($apiRequest) {
    try {
      $this->boot($apiRequest);

      [$apiProvider, $apiRequest] = $this->resolve($apiRequest);

      try {
        $this->authorize($apiProvider, $apiRequest);
      }
      catch (\Civi\API\Exception\UnauthorizedException $e) {
        // We catch and re-throw to log for visibility
        \CRM_Core_Error::backtrace('API Request Authorization failed', TRUE);
        throw $e;
      }

      [$apiProvider, $apiRequest] = $this->prepare($apiProvider, $apiRequest);
      $result = $apiProvider->invoke($apiRequest);

      return $this->respond($apiProvider, $apiRequest, $result);
    }
    catch (\Exception $e) {
      if ($apiRequest) {
        $this->dispatcher->dispatch('civi.api.exception', new ExceptionEvent($e, NULL, $apiRequest, $this));
      }
      throw $e;
    }
  }

  /**
   * Bootstrap - Load basic dependencies and sanity-check inputs.
   *
   * @param \Civi\Api4\Generic\AbstractAction|array $apiRequest
   * @throws \CRM_Core_Exception
   */
  public function boot($apiRequest) {
    require_once 'api/Exception.php';
    // the create error function loads some functions from utils
    // so this require is also needed for apiv4 until such time as
    // we alter create error.
    require_once 'api/v3/utils.php';
    switch ($apiRequest['version']) {
      case 3:
        if (!is_array($apiRequest['params'])) {
          throw new \CRM_Core_Exception('Input variable `params` is not an array', 2000);
        }
        _civicrm_api3_initialize();
        break;

      case 4:
        // nothing to do
        break;

      default:
        throw new \CRM_Core_Exception('Unknown api version', 2000);
    }
  }

  /**
   * @param array $apiRequest
   * @throws \CRM_Core_Exception
   */
  protected function validate($apiRequest) {
  }

  /**
   * Determine which, if any, service will execute the API request.
   *
   * @param array $apiRequest
   *   The full description of the API request.
   * @throws Exception\NotImplementedException
   * @return array
   *   A tuple with the provider-object and a revised apiRequest.
   *   Array(0 => ProviderInterface, 1 => array $apiRequest).
   */
  public function resolve($apiRequest) {
    /** @var \Civi\API\Event\ResolveEvent $resolveEvent */
    $resolveEvent = $this->dispatcher->dispatch('civi.api.resolve', new ResolveEvent($apiRequest, $this));
    $apiRequest = $resolveEvent->getApiRequest();
    if (!$resolveEvent->getApiProvider()) {
      throw new \Civi\API\Exception\NotImplementedException("API (" . $apiRequest['entity'] . ", " . $apiRequest['action'] . ") does not exist (join the API team and implement it!)");
    }
    return [$resolveEvent->getApiProvider(), $apiRequest];
  }

  /**
   * Determine if the API request is allowed (under current policy)
   *
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for executing the request.
   * @param array $apiRequest
   *   The full description of the API request.
   * @throws Exception\UnauthorizedException
   */
  public function authorize($apiProvider, $apiRequest) {
    /** @var \Civi\API\Event\AuthorizeEvent $event */
    $event = $this->dispatcher->dispatch('civi.api.authorize', new AuthorizeEvent($apiProvider, $apiRequest, $this, \CRM_Core_Session::getLoggedInContactID() ?: 0));
    if (!$event->isAuthorized()) {
      throw new \Civi\API\Exception\UnauthorizedException("Authorization failed: CiviCRM APIv{$apiRequest['version']} ({$apiRequest['entity']}::{$apiRequest['action']})");
    }
  }

  /**
   * Allow third-party code to manipulate the API request before execution.
   *
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for executing the request.
   * @param array $apiRequest
   *   The full description of the API request.
   * @return array
   *   [0 => ProviderInterface $provider, 1 => array $apiRequest]
   *   The revised API request.
   */
  public function prepare($apiProvider, $apiRequest) {
    /** @var \Civi\API\Event\PrepareEvent $event */
    $event = $this->dispatcher->dispatch('civi.api.prepare', new PrepareEvent($apiProvider, $apiRequest, $this));
    return [$event->getApiProvider(), $event->getApiRequest()];
  }

  /**
   * Allow third-party code to manipulate the API response after execution.
   *
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for executing the request.
   * @param array $apiRequest
   *   The full description of the API request.
   * @param array $result
   *   The response to return to the client.
   * @return mixed
   *   The revised $result.
   */
  public function respond($apiProvider, $apiRequest, $result) {
    /** @var \Civi\API\Event\RespondEvent $event */
    $event = $this->dispatcher->dispatch('civi.api.respond', new RespondEvent($apiProvider, $apiRequest, $result, $this));
    return $event->getResponse();
  }

  /**
   * @param int $version
   *   API version.
   * @return string[]
   */
  public function getEntityNames($version) {
    // Question: Would it better to eliminate $this->apiProviders and just use $this->dispatcher?
    $entityNames = [];
    foreach ($this->getApiProviders() as $provider) {
      $entityNames = array_merge($entityNames, $provider->getEntityNames($version));
    }
    $entityNames = array_unique($entityNames);
    sort($entityNames);
    return $entityNames;
  }

  /**
   * @param int $version
   *   API version.
   * @param string $entity
   *   API entity.
   * @return string[]
   */
  public function getActionNames($version, $entity) {
    // Question: Would it better to eliminate $this->apiProviders and just use $this->dispatcher?
    $actionNames = [];
    foreach ($this->getApiProviders() as $provider) {
      $actionNames = array_merge($actionNames, $provider->getActionNames($version, $entity));
    }
    $actionNames = array_unique($actionNames);
    sort($actionNames);
    return $actionNames;
  }

  /**
   * @param \Exception $e
   *   An unhandled exception.
   * @param array $apiRequest
   *   The full description of the API request.
   *
   * @return array
   *   API response.
   * @throws \CRM_Core_Exception
   */
  public function formatException($e, $apiRequest) {
    $data = [];
    if (!empty($apiRequest['params']['debug'])) {
      $data['trace'] = $e->getTraceAsString();
    }
    return $this->createError($e->getMessage(), $data, $apiRequest);
  }

  /**
   * @param \CRM_Core_Exception $e
   *   An unhandled exception.
   * @param array $apiRequest
   *   The full description of the API request.
   *
   * @return array
   *   (API response)
   * @throws \CRM_Core_Exception
   */
  public function formatApiException($e, $apiRequest) {
    $data = $e->getExtraParams();
    if (($data['exception'] ?? NULL) instanceof \DB_Error) {
      $data['sql'] = $e->getSQL();
      $data['debug_info'] = $e->getUserInfo();
    }
    unset($data['exception']);
    $data['entity'] = $apiRequest['entity'] ?? NULL;
    $data['action'] = $apiRequest['action'] ?? NULL;

    if (!empty($apiRequest['params']['debug'])
      // prevent recursion
      && empty($data['trace'])
    ) {
      $data['trace'] = $e->getTraceAsString();
    }

    return $this->createError($e->getMessage(), $data, $apiRequest);
  }

  /**
   * @param \PEAR_Exception $e
   *   An unhandled exception.
   * @param array $apiRequest
   *   The full description of the API request.
   *
   * @return array
   *   API response.
   *
   * @throws \CRM_Core_Exception
   */
  public function formatPearException($e, $apiRequest) {
    $data = [];
    $error = $e->getCause();
    if ($error instanceof \DB_Error) {
      $data['error_code'] = \DB::errorMessage($error->getCode());
      $data['sql'] = $error->getDebugInfo();
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
      $data['tip'] = 'add debug=1 to your API call to have more info about the error';
    }

    return $this->createError($e->getMessage(), $data, $apiRequest);
  }

  /**
   * @param string $msg
   *   Descriptive error message.
   * @param array $data
   *   Error data.
   * @param array $apiRequest
   *   The full description of the API request.
   *
   * @throws \CRM_Core_Exception
   * @return array
   */
  public function createError($msg, $data, $apiRequest) {
    if ($msg === 'DB Error: constraint violation' || substr($msg, 0, 9) == 'DB Error:' || $msg == 'DB Error: already exists') {
      try {
        $fields = _civicrm_api3_api_getfields($apiRequest);
        _civicrm_api3_validate_foreign_keys($apiRequest['entity'], $apiRequest['action'], $apiRequest['params'], $fields);
      }
      catch (\Exception $e) {
        $msg = $e->getMessage();
      }
    }

    require_once "api/v3/utils.php";
    $data = \civicrm_api3_create_error($msg, $data);

    if (isset($apiRequest['params']) && is_array($apiRequest['params']) && !empty($apiRequest['params']['api.has_parent'])) {
      $errorCode = empty($data['error_code']) ? 'chained_api_failed' : $data['error_code'];
      throw new \CRM_Core_Exception('Error in call to ' . $apiRequest['entity'] . '_' . $apiRequest['action'] . ' : ' . $msg, $errorCode, $data);
    }

    return $data;
  }

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   * @param array $result
   *   The response to return to the client.
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

  /**
   * @return \Civi\API\Provider\ProviderInterface[]
   */
  public function getApiProviders() {
    return $this->apiProviders;
  }

  /**
   * @param \Civi\API\Provider\ProviderInterface[] $apiProviders
   * @return Kernel
   */
  public function setApiProviders($apiProviders) {
    $this->apiProviders = $apiProviders;
    return $this;
  }

  /**
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for executing the request.
   * @return Kernel
   */
  public function registerApiProvider($apiProvider) {
    $this->apiProviders[] = $apiProvider;
    if ($apiProvider instanceof \Symfony\Component\EventDispatcher\EventSubscriberInterface) {
      $this->getDispatcher()->addSubscriber($apiProvider);
    }
    return $this;
  }

  /**
   * @return \Civi\Core\CiviEventDispatcherInterface
   */
  public function getDispatcher() {
    return $this->dispatcher;
  }

  /**
   * @param \Civi\Core\CiviEventDispatcherInterface $dispatcher
   *   The event dispatcher which receives kernel events.
   * @return Kernel
   */
  public function setDispatcher($dispatcher) {
    $this->dispatcher = $dispatcher;
    return $this;
  }

}
