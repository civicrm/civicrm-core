<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * This class defines operations for inspecting the API's metadata.
 */
class ReflectionProvider implements EventSubscriberInterface, ProviderInterface {
  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return array(
      Events::RESOLVE => array(
        // TODO decide if we really want to override others
        array('onApiResolve', Events::W_EARLY),
      ),
      Events::AUTHORIZE => array(
        // TODO decide if we really want to override others
        array('onApiAuthorize', Events::W_EARLY),
      ),
    );
  }

  /**
   * @var \Civi\API\Kernel
   */
  private $apiKernel;

  /**
   * @var array (string $entityName => array(string $actionName))
   */
  private $actions;

  /**
   * @param \Civi\API\Kernel $apiKernel
   *   The API kernel.
   */
  public function __construct($apiKernel) {
    $this->apiKernel = $apiKernel;
    $this->actions = array(
      'Entity' => array('get', 'getactions'),
      '*' => array('getactions'), // 'getfields'
    );
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
   * @throws \API_Exception
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
    throw new \API_Exception("Unsupported action (" . $apiRequest['entity'] . '.' . $apiRequest['action'] . ']');
  }

  /**
   * @inheritDoc
   * @param int $version
   * @return array
   */
  public function getEntityNames($version) {
    return array('Entity');
  }

  /**
   * @inheritDoc
   * @param int $version
   * @param string $entity
   * @return array
   */
  public function getActionNames($version, $entity) {
    $entity = _civicrm_api_get_camel_name($entity, $version);
    return isset($this->actions[$entity]) ? $this->actions[$entity] : $this->actions['*'];
  }

}
