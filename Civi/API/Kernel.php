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
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * @var \Civi\API\Provider\ProviderInterface[]
   */
  protected $apiProviders;

  /**
   * @param \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
   *   The event dispatcher which receives kernel events.
   * @param array $apiProviders
   *   Array of ProviderInterface.
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
   * @throws \API_Exception
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
   * @throws \API_Exception
   */
  public function runSafe($entity, $action, $params) {
    $apiRequest = [];
    try {
      $apiRequest = Request::create($entity, $action, $params);
      $apiResponse = $this->runRequest($apiRequest);
      return $this->formatResult($apiRequest, $apiResponse);
    }
    catch (\Exception $e) {
      if ($apiRequest) {
        $this->dispatcher->dispatch(Events::EXCEPTION, new ExceptionEvent($e, NULL, $apiRequest, $this));
      }

      if ($e instanceof \PEAR_Exception) {
        $err = $this->formatPearException($e, $apiRequest);
      }
      elseif ($e instanceof \API_Exception) {
        $err = $this->formatApiException($e, $apiRequest);
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
      list($apiProvider, $apiRequest) = $this->resolve($apiRequest);
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
   * @throws \API_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function runRequest($apiRequest) {
    $this->boot($apiRequest);
    $errorScope = \CRM_Core_TemporaryErrorScope::useException();

    list($apiProvider, $apiRequest) = $this->resolve($apiRequest);
    $this->authorize($apiProvider, $apiRequest);
    list ($apiProvider, $apiRequest) = $this->prepare($apiProvider, $apiRequest);
    $result = $apiProvider->invoke($apiRequest);

    return $this->respond($apiProvider, $apiRequest, $result);
  }

  /**
   * Bootstrap - Load basic dependencies and sanity-check inputs.
   *
   * @param \Civi\Api4\Generic\AbstractAction|array $apiRequest
   * @throws \API_Exception
   */
  public function boot($apiRequest) {
    require_once 'api/Exception.php';
    switch ($apiRequest['version']) {
      case 3:
        if (!is_array($apiRequest['params'])) {
          throw new \API_Exception('Input variable `params` is not an array', 2000);
        }
        require_once 'api/v3/utils.php';
        _civicrm_api3_initialize();
        break;

      case 4:
        // nothing to do
        break;

      default:
        throw new \API_Exception('Unknown api version', 2000);
    }
  }

  /**
   * @param array $apiRequest
   * @throws \API_Exception
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
    $resolveEvent = $this->dispatcher->dispatch(Events::RESOLVE, new ResolveEvent($apiRequest, $this));
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
    $event = $this->dispatcher->dispatch(Events::AUTHORIZE, new AuthorizeEvent($apiProvider, $apiRequest, $this));
    if (!$event->isAuthorized()) {
      throw new \Civi\API\Exception\UnauthorizedException("Authorization failed");
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
    $event = $this->dispatcher->dispatch(Events::PREPARE, new PrepareEvent($apiProvider, $apiRequest, $this));
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
    $event = $this->dispatcher->dispatch(Events::RESPOND, new RespondEvent($apiProvider, $apiRequest, $result, $this));
    return $event->getResponse();
  }

  /**
   * @param int $version
   *   API version.
   * @return array
   *   Array<string>.
   */
  public function getEntityNames($version) {
    // Question: Would it better to eliminate $this->apiProviders and just use $this->dispatcher?
    $entityNames = [];
    foreach ($this->getApiProviders() as $provider) {
      /** @var \Civi\API\Provider\ProviderInterface $provider */
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
   * @return array
   *   Array<string>
   */
  public function getActionNames($version, $entity) {
    // Question: Would it better to eliminate $this->apiProviders and just use $this->dispatcher?
    $actionNames = [];
    foreach ($this->getApiProviders() as $provider) {
      /** @var \Civi\API\Provider\ProviderInterface $provider */
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
   * @throws \API_Exception
   */
  public function formatException($e, $apiRequest) {
    $data = [];
    if (!empty($apiRequest['params']['debug'])) {
      $data['trace'] = $e->getTraceAsString();
    }
    return $this->createError($e->getMessage(), $data, $apiRequest, $e->getCode());
  }

  /**
   * @param \API_Exception $e
   *   An unhandled exception.
   * @param array $apiRequest
   *   The full description of the API request.
   *
   * @return array
   *   (API response)
   * @throws \API_Exception
   */
  public function formatApiException($e, $apiRequest) {
    $data = $e->getExtraParams();
    $data['entity'] = \CRM_Utils_Array::value('entity', $apiRequest);
    $data['action'] = \CRM_Utils_Array::value('action', $apiRequest);

    if (\CRM_Utils_Array::value('debug', \CRM_Utils_Array::value('params', $apiRequest))
      // prevent recursion
      && empty($data['trace'])
    ) {
      $data['trace'] = $e->getTraceAsString();
    }

    return $this->createError($e->getMessage(), $data, $apiRequest, $e->getCode());
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
   * @throws \API_Exception
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
   * @param mixed $code
   *   Doesn't appear to be used.
   *
   * @throws \API_Exception
   * @return array
   *   Array<type>.
   */
  public function createError($msg, $data, $apiRequest, $code = NULL) {
    // FIXME what to do with $code?
    if ($msg === 'DB Error: constraint violation' || substr($msg, 0, 9) == 'DB Error:' || $msg == 'DB Error: already exists') {
      try {
        $fields = _civicrm_api3_api_getfields($apiRequest);
        _civicrm_api3_validate_foreign_keys($apiRequest['entity'], $apiRequest['action'], $apiRequest['params'], $fields);
      }
      catch (\Exception $e) {
        $msg = $e->getMessage();
      }
    }

    $data = \civicrm_api3_create_error($msg, $data);

    if (isset($apiRequest['params']) && is_array($apiRequest['params']) && !empty($apiRequest['params']['api.has_parent'])) {
      $errorCode = empty($data['error_code']) ? 'chained_api_failed' : $data['error_code'];
      throw new \API_Exception('Error in call to ' . $apiRequest['entity'] . '_' . $apiRequest['action'] . ' : ' . $msg, $errorCode, $data);
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
   * @return array<ProviderInterface>
   */
  public function getApiProviders() {
    return $this->apiProviders;
  }

  /**
   * @param array $apiProviders
   *   Array<ProviderInterface>.
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
   * @return \Symfony\Component\EventDispatcher\EventDispatcher
   */
  public function getDispatcher() {
    return $this->dispatcher;
  }

  /**
   * @param \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
   *   The event dispatcher which receives kernel events.
   * @return Kernel
   */
  public function setDispatcher($dispatcher) {
    $this->dispatcher = $dispatcher;
    return $this;
  }

}
