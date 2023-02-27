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

namespace Civi\Api4\Provider;

use Civi\API\Event\ResolveEvent;
use Civi\API\Provider\ProviderInterface;
use Civi\Api4\Generic\AbstractAction;
use Civi\API\Events;
use Civi\Api4\Utils\ReflectionUtils;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Accept $apiRequests based on \Civi\API\Action
 *
 * @service action_object_provider
 */
class ActionObjectProvider extends AutoService implements EventSubscriberInterface, ProviderInterface {

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
    ];
  }

  /**
   * @param \Civi\API\Event\ResolveEvent $event
   *   API resolution event.
   */
  public function onApiResolve(ResolveEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($apiRequest instanceof AbstractAction) {
      $event->setApiRequest($apiRequest);
      $event->setApiProvider($this);
      $event->stopPropagation();
    }
  }

  /**
   * @inheritDoc
   *
   * @param \Civi\Api4\Generic\AbstractAction $action
   *
   * @return \Civi\Api4\Generic\Result
   */
  public function invoke($action) {
    // Load result class based on @return annotation in the execute() method.
    $reflection = new \ReflectionClass($action);
    $doc = ReflectionUtils::getCodeDocs($reflection->getMethod('execute'), 'Method');
    $resultClass = $doc['return'][0] ?? '\\Civi\\Api4\\Generic\\Result';
    $result = new $resultClass();
    $result->action = $action->getActionName();
    $result->entity = $action->getEntityName();
    $action->_run($result);
    $this->handleChains($action, $result);
    return $result;
  }

  /**
   * Run each chained action once per row
   *
   * @param \Civi\Api4\Generic\AbstractAction $action
   * @param \Civi\Api4\Generic\Result $result
   */
  protected function handleChains($action, $result) {
    foreach ($action->getChain() as $name => $request) {
      $request += [NULL, NULL, [], NULL];
      $request[2]['checkPermissions'] = $action->getCheckPermissions();
      foreach ($result as &$row) {
        $row[$name] = $this->runChain($request, $row);
      }
    }
  }

  /**
   * Run a chained action
   *
   * @param $request
   * @param $row
   * @return array|\Civi\Api4\Generic\Result|null
   * @throws \CRM_Core_Exception
   */
  protected function runChain($request, $row) {
    [$entity, $action, $params, $index] = $request;
    // Swap out variables in $entity, $action & $params
    $this->resolveChainLinks($entity, $row);
    $this->resolveChainLinks($action, $row);
    $this->resolveChainLinks($params, $row);
    return (array) civicrm_api4($entity, $action, $params, $index);
  }

  /**
   * Swap out variable names
   *
   * @param mixed $val
   * @param array $result
   */
  protected function resolveChainLinks(&$val, $result) {
    if (is_array($val)) {
      foreach ($val as &$v) {
        $this->resolveChainLinks($v, $result);
      }
    }
    elseif (is_string($val) && strlen($val) > 1 && substr($val, 0, 1) === '$') {
      $key = substr($val, 1);
      $val = $result[$key] ?? \CRM_Utils_Array::pathGet($result, explode('.', $key)) ?? $val;
    }
  }

  /**
   * @inheritDoc
   * @param int $version
   * @return array
   */
  public function getEntityNames($version) {
    return $version === 4 ? array_keys($this->getEntities()) : [];
  }

  /**
   * @inheritDoc
   * @param int $version
   * @param string $entity
   * @return array
   */
  public function getActionNames($version, $entity) {
    /** FIXME Civi\API\V4\Action\GetActions */
    return [];
  }

  /**
   * Get all APIv4 entities
   */
  public function getEntities() {
    $cache = \Civi::cache('metadata');
    $entities = $cache->get('api4.entities.info', []);

    if (!$entities) {
      // Load entities declared in API files
      foreach ($this->getAllApiClasses() as $className) {
        $info = $className::getInfo();
        $entities[$info['name']] = $info;
      }
      // Allow extensions to modify the list of entities
      $event = GenericHookEvent::create(['entities' => &$entities]);
      \Civi::dispatcher()->dispatch('civi.api4.entityTypes', $event);
      ksort($entities);
      $cache->set('api4.entities.info', $entities);
    }

    return $entities;
  }

  /**
   * Scan all api directories to discover entities
   * @return \Civi\Api4\Generic\AbstractEntity[]
   */
  private function getAllApiClasses() {
    $classNames = [];
    $locations = array_merge([\Civi::paths()->getPath('[civicrm.root]/Civi.php')],
      array_column(\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'filePath')
    );
    foreach ($locations as $location) {
      $dir = \CRM_Utils_File::addTrailingSlash(dirname($location ?? '')) . 'Civi/Api4';
      if (is_dir($dir)) {
        foreach (glob("$dir/*.php") as $file) {
          $className = 'Civi\Api4\\' . basename($file, '.php');
          if (is_a($className, 'Civi\Api4\Generic\AbstractEntity', TRUE)) {
            $classNames[] = $className;
          }
        }
      }
    }
    return $classNames;
  }

}
