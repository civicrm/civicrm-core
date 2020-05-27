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
 * An adhoc provider is useful for creating mock API implementations.
 */
class AdhocProvider implements EventSubscriberInterface, ProviderInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    // Using a high priority allows adhoc implementations
    // to override standard implementations -- which is
    // handy for testing/mocking.
    return [
      'civi.api.resolve' => [
        ['onApiResolve', Events::W_EARLY],
      ],
      'civi.api.authorize' => [
        ['onApiAuthorize', Events::W_EARLY],
      ],
    ];
  }

  /**
   * List of adhoc actions
   *
   * array(string $ame => array('perm' => string, 'callback' => callable))
   *
   * @var array
   */
  protected $actions = [];

  /**
   * @var string
   */
  protected $entity;

  /**
   * @var int
   */
  protected $version;

  /**
   * @param int $version
   *   API version.
   * @param string $entity
   *   API entity.
   */
  public function __construct($version, $entity) {
    $this->entity = $entity;
    $this->version = $version;
  }

  /**
   * Register a new API.
   *
   * @param string $name
   *   Action name.
   * @param string $perm
   *   Permissions required for invoking the action.
   * @param mixed $callback
   *   The function which executes the API.
   * @return AdhocProvider
   */
  public function addAction($name, $perm, $callback) {
    $this->actions[strtolower($name)] = [
      'perm' => $perm,
      'callback' => $callback,
    ];
    return $this;
  }

  /**
   * @param \Civi\API\Event\ResolveEvent $event
   *   API resolution event.
   */
  public function onApiResolve(\Civi\API\Event\ResolveEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($this->matchesRequest($apiRequest)) {
      $event->setApiRequest($apiRequest);
      $event->setApiProvider($this);
      $event->stopPropagation();
    }
  }

  /**
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   */
  public function onApiAuthorize(\Civi\API\Event\AuthorizeEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($this->matchesRequest($apiRequest) && \CRM_Core_Permission::check($this->actions[strtolower($apiRequest['action'])]['perm'])) {
      $event->authorize();
      $event->stopPropagation();
    }
  }

  /**
   * @inheritDoc
   * @param array $apiRequest
   * @return array|mixed
   */
  public function invoke($apiRequest) {
    return call_user_func($this->actions[strtolower($apiRequest['action'])]['callback'], $apiRequest);
  }

  /**
   * @inheritDoc
   * @param int $version
   * @return array
   */
  public function getEntityNames($version) {
    return [$this->entity];
  }

  /**
   * @inheritDoc
   * @param int $version
   * @param string $entity
   * @return array
   */
  public function getActionNames($version, $entity) {
    if ($version == $this->version && $entity == $this->entity) {
      return array_keys($this->actions);
    }
    else {
      return [];
    }
  }

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   *
   * @return bool
   */
  public function matchesRequest($apiRequest) {
    return $apiRequest['entity'] == $this->entity && $apiRequest['version'] == $this->version && isset($this->actions[strtolower($apiRequest['action'])]);
  }

}
