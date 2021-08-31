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

    $tasks[$entity['name']]['download'] = [
      'module' => 'crmSearchTasks',
      'title' => E::ts('Download Spreadsheet'),
      'icon' => 'fa-download',
      'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskDownload.html'],
      // Does not require any rows to be selected
      'number' => '>= 0',
    ];

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
      // Add contact tasks which support standalone mode
      $contactTasks = $this->checkPermissions ? \CRM_Contact_Task::permissionedTaskTitles(\CRM_Core_Permission::getPermission()) : NULL;
      foreach (\CRM_Contact_Task::tasks() as $id => $task) {
        if (
          (!$this->checkPermissions || isset($contactTasks[$id])) &&
          // Must support standalone mode (with a 'url' property)
          !empty($task['url']) &&
          // The delete task is redundant with the new api-based one
          $task['url'] !== 'civicrm/task/delete-contact'
        ) {
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
      // FIXME: tasks() function always checks permissions, should respect `$this->checkPermissions`
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

    // Call `hook_civicrm_searchKitTasks`.
    // Note - this hook serves 2 purposes, both to augment this list of tasks AND to
    // get a full list of Angular modules which provide tasks. That's why this hook needs
    // the base-level array and not just the array of tasks for `$this->entity`.
    // Although it may seem wasteful to have extensions add tasks for all possible entities and then
    // discard most of it (all but the ones relevant to `$this->entity`), it's necessary to do it this way
    // so that they can be declared as angular dependencies - see search_kit_civicrm_angularModules().
    $null = NULL;
    $checkPermissions = $this->checkPermissions;
    $userId = $this->checkPermissions ? \CRM_Core_Session::getLoggedInContactID() : NULL;
    \CRM_Utils_Hook::singleton()->invoke(['tasks', 'checkPermissions', 'userId'],
      $tasks, $checkPermissions, $userId,
      $null, $null, $null, 'civicrm_searchKitTasks'
    );

    usort($tasks[$entity['name']], function($a, $b) {
      return strnatcasecmp($a['title'], $b['title']);
    });

    foreach ($tasks[$entity['name']] as $name => &$task) {
      $task['name'] = $name;
      // Add default for number of rows action requires
      $task += ['number' => '> 0'];
    }

    $result->exchangeArray(array_values($tasks[$entity['name']]));
  }

}
