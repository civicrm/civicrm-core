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

namespace Civi\Search;

/**
 * Class Tasks
 * @package Civi\Search
 */
class Actions {

  /**
   * @return array
   */
  public static function getActionSettings():array {
    return [
      'tasks' => self::getTasks(),
      'groupOptions' => self::getGroupOptions(),
    ];
  }

  /**
   * @return array
   */
  public static function getGroupOptions():array {
    return \Civi\Api4\Group::getFields(FALSE)
      ->setLoadOptions(['id', 'label'])
      ->addWhere('name', 'IN', ['group_type', 'visibility'])
      ->execute()
      ->indexBy('name')
      ->column('options');
  }

  /**
   * @return array
   */
  public static function getTasks():array {
    // Note: the placeholder %1 will be replaced with entity name on the clientside
    $tasks = [
      'export' => [
        'title' => ts('Export %1'),
        'icon' => 'fa-file-excel-o',
        'entities' => array_keys(\CRM_Export_BAO_Export::getComponents()),
        'crmPopup' => [
          'path' => "'civicrm/export/standalone'",
          'query' => "{entity: entity, id: ids.join(',')}",
        ],
      ],
      'update' => [
        'title' => ts('Update %1'),
        'icon' => 'fa-save',
        'entities' => [],
        'uiDialog' => ['templateUrl' => '~/crmSearchActions/crmSearchActionUpdate.html'],
      ],
      'delete' => [
        'title' => ts('Delete %1'),
        'icon' => 'fa-trash',
        'entities' => [],
        'uiDialog' => ['templateUrl' => '~/crmSearchActions/crmSearchActionDelete.html'],
      ],
    ];

    // Add contact tasks which support standalone mode (with a 'url' property)
    $contactTasks = \CRM_Contact_Task::permissionedTaskTitles(\CRM_Core_Permission::getPermission());
    foreach (\CRM_Contact_Task::tasks() as $id => $task) {
      if (isset($contactTasks[$id]) && !empty($task['url']) && $task['url'] !== 'civicrm/task/delete-contact') {
        $tasks['contact.' . $id] = [
          'title' => $task['title'],
          'entities' => ['Contact'],
          'icon' => $task['icon'] ?? 'fa-gear',
          'crmPopup' => [
            'path' => "'{$task['url']}'",
            'query' => "{cids: ids.join(',')}",
          ],
        ];
      }
    }

    return $tasks;
  }

}
