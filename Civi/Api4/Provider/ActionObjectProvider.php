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
use Civi\Core\ClassScanner;
use Civi\Api4\Generic\EntityInterface;
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
      $entityName = $apiRequest->getEntityName();
      if (!isset($this->getEntities()[$entityName])) {
        // this certainly seems bad - we're taking an action on an entity that doesn't currently exist
        //
        // however some existing behaviours seem to rely on this being possible
        // @see https://lab.civicrm.org/dev/core/-/issues/5533
        //
        // maybe this is reasonable for some kinds of AbstractAction that happen to live
        // on an entity, but the entity isn't really doing anything (like a static method)?
        //
        // for something like DAOEntities, it will definitely fail later when it realises
        // the entity doesnt exist - and give a more cryptic error message
        //
        // unfortunately because the entity doesn't exist we can't check what kind of entity
        // for now let's just log that something weird is happening
        //
        // throw new \CRM_Core_Exception("Unrecognised entity in Api4 ActionObjectProvider: $entityName");
        \Civi::log()->debug("Unrecognised entity in Api4 ActionObjectProvider: $entityName");
      }
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
    $resultClass = $this->getResultClass($action);
    $result = new $resultClass();
    $result->action = $action->getActionName();
    $result->entity = $action->getEntityName();
    $action->_run($result);
    $this->handleChains($action, $result);
    return $result;
  }

  private function getResultClass($action): string {
    $actionClassName = get_class($action);
    if (!isset(\Civi::$statics[__CLASS__][__FUNCTION__][$actionClassName])) {
      $reflection = new \ReflectionClass($action);
      $doc = ReflectionUtils::getCodeDocs($reflection->getMethod('execute'), 'Method');
      \Civi::$statics[__CLASS__][__FUNCTION__][$actionClassName] = $doc['return'][0] ?? '\Civi\Api4\Generic\Result';
    }
    return \Civi::$statics[__CLASS__][__FUNCTION__][$actionClassName];
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
      //
      // NOTE: this is slightly subtle in how it respects the boundary between core
      // and extensions
      //
      // It *should* align with EntityRepository::loadAll which listens to hook_civicrm_entityType
      //
      // In restricted circumstances (like upgrade) extension entities are not picked
      // up there because hook_civicrm_entityType is blocked by the dispatch policy
      //
      // They are not picked up here because hook_civicrm_scanClasses is not called, so
      // their classes are not found by the ClassScanner
      //
      // I think it would be *better* if the extension entities always came through
      // civi.api4.entityTypes here, but can't see how to distinguish extension
      // classes from core classes with the ClassScanner
      //
      $entityClasses = ClassScanner::get(['interface' => EntityInterface::class]);
      foreach ($entityClasses as $class) {
        $info = $class::getInfo();
        $entities[$info['name']] = $info;
      }
      // Allow extensions to modify the list of entities
      $event = GenericHookEvent::create(['entities' => &$entities]);
      \Civi::dispatcher()->dispatch('civi.api4.entityTypes', $event);
      $this->fillEntityDefaults($entities);
      ksort($entities);
      $cache->set('api4.entities.info', $entities);
    }

    return $entities;
  }

  public function fillEntityDefaults(array &$entities) {
    foreach ($entities as &$entity) {
      if (!isset($entity['search_fields'])) {
        $entity['search_fields'] = (array) ($entity['label_field'] ?? NULL);
      }
    }
  }

}
