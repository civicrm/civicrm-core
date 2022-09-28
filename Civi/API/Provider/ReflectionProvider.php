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

namespace Civi\API\Provider;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class defines operations for inspecting the API's metadata.
 */
class ReflectionProvider implements EventSubscriberInterface, ProviderInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.resolve' => [
        // TODO decide if we really want to override others
        ['onApiResolve', Events::W_EARLY],
      ],
      'civi.api.authorize' => [
        // TODO decide if we really want to override others
        ['onApiAuthorize', Events::W_EARLY],
      ],
    ];
  }

  /**
   * @var \Civi\API\Kernel
   */
  private $apiKernel;

  /**
   * List of all entities and their supported actions
   *
   * array(string $entityName => string[] $actionNames).
   *
   * @var array
   */
  private $actions;

  /**
   * @param \Civi\API\Kernel $apiKernel
   *   The API kernel.
   */
  public function __construct($apiKernel) {
    $this->apiKernel = $apiKernel;
    $this->actions = [
      'Entity' => ['get', 'getactions'],
      // 'getfields'
      '*' => ['getactions'],
    ];
  }

  /**
   * @param \Civi\API\Event\ResolveEvent $event
   *   API resolution event.
   */
  public function onApiResolve(\Civi\API\Event\ResolveEvent $event) {
    $apiRequest = $event->getApiRequest();
    $actions = $this->getActionNames($apiRequest['version'], $apiRequest['entity']);
    if (in_array($apiRequest['action'], $actions)) {
      $apiRequest['is_metadata'] = TRUE;
      $event->setApiRequest($apiRequest);
      $event->setApiProvider($this);
      $event->stopPropagation();
      // TODO decide if we really want to override others
    }
  }

  /**
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   */
  public function onApiAuthorize(\Civi\API\Event\AuthorizeEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (isset($apiRequest['is_metadata'])) {
      // if (\CRM_Core_Permission::check('access AJAX API')
      //   || \CRM_Core_Permission::check('access CiviCRM')) {
      $event->authorize();
      $event->stopPropagation();
      // }
    }
  }

  /**
   * @inheritDoc
   * @param array $apiRequest
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function invoke($apiRequest) {
    if (strtolower($apiRequest['entity']) == 'entity' && $apiRequest['action'] == 'get') {
      return civicrm_api3_create_success($this->apiKernel->getEntityNames($apiRequest['version']), $apiRequest['params'], 'entity', 'get');
    }
    switch ($apiRequest['action']) {
      case 'getactions':
        return civicrm_api3_create_success($this->apiKernel->getActionNames($apiRequest['version'], $apiRequest['entity']), $apiRequest['params'], $apiRequest['entity'], $apiRequest['action']);

      //case 'getfields':
      //  return $this->doGetFields($apiRequest);

      default:
    }

    // We shouldn't get here because onApiResolve() checks $this->actions
    throw new \CRM_Core_Exception("Unsupported action (" . $apiRequest['entity'] . '.' . $apiRequest['action'] . ']');
  }

  /**
   * @inheritDoc
   * @param int $version
   * @return array
   */
  public function getEntityNames($version) {
    return ['Entity'];
  }

  /**
   * @inheritDoc
   * @param int $version
   * @param string $entity
   * @return array
   */
  public function getActionNames($version, $entity) {
    $entity = _civicrm_api_get_camel_name($entity, $version);
    return $this->actions[$entity] ?? $this->actions['*'];
  }

}
