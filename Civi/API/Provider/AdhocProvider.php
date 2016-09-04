<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
    return array(
      Events::RESOLVE => array(
        array('onApiResolve', Events::W_EARLY),
      ),
      Events::AUTHORIZE => array(
        array('onApiAuthorize', Events::W_EARLY),
      ),
    );
  }

  /**
   * @var array (string $name => array('perm' => string, 'callback' => callable))
   */
  protected $actions = array();

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
   * @return ReflectionProvider
   */
  public function addAction($name, $perm, $callback) {
    $this->actions[strtolower($name)] = array(
      'perm' => $perm,
      'callback' => $callback,
    );
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
    return array($this->entity);
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
      return array();
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
