<?php

namespace Civi\Afform;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Afform;

/**
 * Class FormDataModel
 * @package Civi\Afform
 *
 * Examines a form and determines the entities, fields & joins in use.
 */
class FormDataModel {

  protected $defaults = ['security' => 'RBAC', 'actions' => ['create' => TRUE, 'update' => TRUE]];

  /**
   * @var array
   *   Ex: $entities['spouse']['type'] = 'Contact';
   */
  protected $entities;

  /**
   * @var array
   */
  protected $blocks = [];

  /**
   * @var array
   *   Ex: $secureApi4s['spouse'] = function($entity, $action, $params){...};
   */
  protected $secureApi4s = [];

  public function __construct($layout) {
    $root = AHQ::makeRoot($layout);
    $this->entities = array_column(AHQ::getTags($root, 'af-entity'), NULL, 'name');
    foreach (array_keys($this->entities) as $entity) {
      $this->entities[$entity] = array_merge($this->defaults, $this->entities[$entity]);
      $this->entities[$entity]['fields'] = $this->entities[$entity]['joins'] = [];
    }
    // Pre-load full list of afforms in case this layout embeds other afform directives
    $this->blocks = (array) Afform::get()->setCheckPermissions(FALSE)->setSelect(['name', 'directive_name'])->execute()->indexBy('directive_name');
    $this->parseFields($layout);
  }

  /**
   * Prepare to access APIv4 on behalf of a particular entity. This will enforce
   * any security options associated with that entity.
   *
   * $formDataModel->getSecureApi4('me')('Contact', 'get', ['where'=>[...]]);
   * $formDataModel->getSecureApi4('me')('Email', 'create', [...]);
   *
   * @param string $entityName
   *   Ex: 'Individual1', 'Individual2', 'me', 'spouse', 'children', 'theMeeting'
   *
   * @return callable
   *   API4-style
   */
  public function getSecureApi4($entityName) {
    if (!isset($this->secureApi4s[$entityName])) {
      if (!isset($this->entities[$entityName])) {
        throw new UnauthorizedException("Cannot delegate APIv4 calls on behalf of unrecognized entity ($entityName)");
      }
      $this->secureApi4s[$entityName] = function(string $entity, string $action, $params = [], $index = NULL) use ($entityName) {
        $entityDefn = $this->entities[$entityName];

        switch ($entityDefn['security']) {
          // Role-based access control. Limits driven by the current user's role/group/permissions.
          case 'RBAC':
            $params['checkPermissions'] = TRUE;
            break;

          // Form-based access control. Limits driven by form configuration.
          case 'FBAC':
            $params['checkPermissions'] = FALSE;
            break;

          default:
            throw new UnauthorizedException("Cannot process APIv4 request for $entityName ($entity.$action): Unrecognized security model");
        }

        if (!$this->isActionAllowed($entityDefn, $entity, $action, $params)) {
          throw new UnauthorizedException("Cannot process APIv4 request for $entityName ($entity.$action): Action is not approved");
        }

        return civicrm_api4($entity, $action, $params, $index);
      };
    }
    return $this->secureApi4s[$entityName];
  }

  /**
   * Determine if we are allowed to perform a given action for this entity.
   *
   * @param $entityDefn
   * @param $entity
   * @param $action
   * @param $params
   *
   * @return bool
   */
  protected function isActionAllowed($entityDefn, $entity, $action, $params) {
    if ($action === 'save') {
      foreach ($params['records'] ?? [] as $record) {
        $nextAction = !isset($record['id']) ? 'create' : 'update';
        if (!$this->isActionAllowed($entityDefn, $entity, $nextAction, $record)) {
          return FALSE;
        }
      }
      return TRUE;
    }

    // "Update" effectively means "read+save".
    if ($action === 'get') {
      $action = 'update';
    }

    $result = !empty($entityDefn['actions'][$action]);
    return $result;
  }

  /**
   * @param array $nodes
   * @param string $entity
   * @param string $join
   */
  protected function parseFields($nodes, $entity = NULL, $join = NULL) {
    foreach ($nodes as $node) {
      if (!is_array($node) || !isset($node['#tag'])) {
        continue;
      }
      elseif (!empty($node['af-fieldset']) && !empty($node['#children'])) {
        $this->parseFields($node['#children'], $node['af-fieldset'], $join);
      }
      elseif ($entity && $node['#tag'] === 'af-field') {
        if ($join) {
          $this->entities[$entity]['joins'][$join]['fields'][$node['name']] = AHQ::getProps($node);
        }
        else {
          $this->entities[$entity]['fields'][$node['name']] = AHQ::getProps($node);
        }
      }
      elseif ($entity && !empty($node['af-join'])) {
        $this->entities[$entity]['joins'][$node['af-join']] = AHQ::getProps($node);
        $this->parseFields($node['#children'] ?? [], $entity, $node['af-join']);
      }
      elseif (!empty($node['#children'])) {
        $this->parseFields($node['#children'], $entity, $join);
      }
      // Recurse into embedded blocks
      if (isset($this->blocks[$node['#tag']])) {
        if (!isset($this->blocks[$node['#tag']]['layout'])) {
          $this->blocks[$node['#tag']] = Afform::get()->setCheckPermissions(FALSE)->setSelect(['name', 'layout'])->addWhere('name', '=', $this->blocks[$node['#tag']]['name'])->execute()->first();
        }
        if (!empty($this->blocks[$node['#tag']]['layout'])) {
          $this->parseFields($this->blocks[$node['#tag']]['layout'], $entity, $join);
        }
      }
    }
  }

  /**
   * @return array[]
   *   Ex: $entities['spouse']['type'] = 'Contact';
   */
  public function getEntities() {
    return $this->entities;
  }

  /**
   * @return array
   */
  public function getEntity($entityName) {
    return $this->entities[$entityName] ?? NULL;
  }

}
