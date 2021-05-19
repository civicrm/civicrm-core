<?php

namespace Civi\Api4\Action\SearchDisplay;

use CRM_Search_ExtensionUtil as E;
use Civi\Api4\Entity;

/**
 * Load the available tasks for a given entity.
 *
 * @package Civi\Api4\Action\SearchDisplay
 */
class GetSearchTasks extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Name of entity
   * @var string
   * @required
   */
  protected $entity;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \API_Exception
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    $entity = Entity::get($this->checkPermissions)->addWhere('name', '=', $this->entity)
      ->addSelect('name', 'title_plural')
      ->setChain(['actions' => ['$name', 'getActions', ['where' => [['name', 'IN', ['update', 'delete']]]], 'name']])
      ->execute()->first();

    if (!$entity) {
      return;
    }
    $tasks = [$entity['name'] => []];

    if (array_key_exists($entity['name'], \CRM_Export_BAO_Export::getComponents())) {
      $tasks[$entity['name']]['export'] = [
        'title' => E::ts('Export %1', [1 => $entity['title_plural']]),
        'icon' => 'fa-file-excel-o',
        'crmPopup' => [
          'path' => "'civicrm/export/standalone'",
          'query' => "{reset: 1, entity: '{$entity['name']}', id: ids.join(',')}",
        ],
      ];
    }

    if (array_key_exists('update', $entity['actions'])) {
      $tasks[$entity['name']]['update'] = [
        'module' => 'crmSearchTasks',
        'title' => E::ts('Update %1', [1 => $entity['title_plural']]),
        'icon' => 'fa-save',
        'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskUpdate.html'],
      ];
    }

    if (array_key_exists('delete', $entity['actions'])) {
      $tasks[$entity['name']]['delete'] = [
        'module' => 'crmSearchTasks',
        'title' => E::ts('Delete %1', [1 => $entity['title_plural']]),
        'icon' => 'fa-trash',
        'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskDelete.html'],
      ];
    }

    if ($entity['name'] === 'Contact') {
      // Add contact tasks which support standalone mode (with a 'url' property)
      $contactTasks = \CRM_Contact_Task::permissionedTaskTitles(\CRM_Core_Permission::getPermission());
      foreach (\CRM_Contact_Task::tasks() as $id => $task) {
        if (isset($contactTasks[$id]) && !empty($task['url']) && $task['url'] !== 'civicrm/task/delete-contact') {
          if ($task['url'] === 'civicrm/task/pick-profile') {
            $task['title'] = E::ts('Profile Update');
          }
          $tasks[$entity['name']]['contact.' . $id] = [
            'title' => $task['title'],
            'icon' => $task['icon'] ?? 'fa-gear',
            'crmPopup' => [
              'path' => "'{$task['url']}'",
              'query' => "{reset: 1, cids: ids.join(',')}",
            ],
          ];
        }
      }
    }

    if ($entity['name'] === 'Contribution') {
      foreach (\CRM_Contribute_Task::tasks() as $id => $task) {
        if (!empty($task['url'])) {
          $tasks[$entity['name']]['contribution.' . $id] = [
            'title' => $task['title'],
            'icon' => $task['icon'] ?? 'fa-gear',
            'crmPopup' => [
              'path' => "'{$task['url']}'",
              'query' => "{id: ids.join(',')}",
            ],
          ];
        }
      }
    }

    $null = NULL;
    \CRM_Utils_Hook::singleton()->invoke(['tasks'], $tasks,
      $null, $null, $null, $null, $null, 'civicrm_searchKitTasks'
    );

    usort($tasks[$entity['name']], function($a, $b) {
      return strnatcasecmp($a['title'], $b['title']);
    });

    foreach ($tasks[$entity['name']] as $name => &$task) {
      $task['name'] = $name;
    }

    $result->exchangeArray(array_values($tasks[$entity['name']]));
  }

}
