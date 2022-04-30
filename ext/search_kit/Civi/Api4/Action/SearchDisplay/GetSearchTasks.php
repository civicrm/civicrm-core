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
    // Adding checkPermissions filters out actions the user is not allowed to perform
    $entity = Entity::get($this->checkPermissions)->addWhere('name', '=', $this->entity)
      ->addSelect('name', 'title_plural')
      ->setChain(['actions' => ['$name', 'getActions', ['where' => [['name', 'IN', ['update', 'delete']]]], 'name']])
      ->execute()->first();

    if (!$entity) {
      return;
    }
    $tasks = [$entity['name'] => []];

    if (array_key_exists($entity['name'], \CRM_Export_BAO_Export::getComponents())) {
      $key = \CRM_Core_Key::get('CRM_Export_Controller_Standalone', TRUE);
      $tasks[$entity['name']]['export'] = [
        'title' => E::ts('Export %1', [1 => $entity['title_plural']]),
        'icon' => 'fa-file-excel-o',
        'crmPopup' => [
          'path' => "'civicrm/export/standalone'",
          'query' => "{reset: 1, entity: '{$entity['name']}'}",
          'data' => "{id: ids.join(','), qfKey: '$key'}",
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

      $taggable = \CRM_Core_OptionGroup::values('tag_used_for', FALSE, FALSE, FALSE, NULL, 'name');
      if (in_array($entity['name'], $taggable, TRUE)) {
        $tasks[$entity['name']]['tag'] = [
          'module' => 'crmSearchTasks',
          'title' => E::ts('Tag - Add/Remove Tags'),
          'icon' => 'fa-tags',
          'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskTag.html'],
        ];
      }

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
      // These tasks are redundant with the new api-based ones in SearchKit
      $redundant = [\CRM_Core_Task::TAG_ADD, \CRM_Core_Task::TAG_REMOVE, \CRM_Core_Task::TASK_DELETE];
      foreach (\CRM_Contact_Task::tasks() as $id => $task) {
        if (
          (!$this->checkPermissions || isset($contactTasks[$id])) &&
          // Must support standalone mode (with a 'url' property)
          !empty($task['url']) &&
          !in_array($id, $redundant)
        ) {
          if ($task['url'] === 'civicrm/task/pick-profile') {
            $task['title'] = E::ts('Profile Update');
          }
          $key = \CRM_Core_Key::get(\CRM_Utils_Array::first((array) $task['class']), TRUE);
          $tasks[$entity['name']]['contact.' . $id] = [
            'title' => $task['title'],
            'icon' => $task['icon'] ?? 'fa-gear',
            'crmPopup' => [
              'path' => "'{$task['url']}'",
              'query' => "{reset: 1}",
              'data' => "{cids: ids.join(','), qfKey: '$key'}",
            ],
          ];
        }
      }
      if (!$this->checkPermissions || \CRM_Core_Permission::check(['merge duplicate contacts', 'delete contacts'])) {
        $tasks[$entity['name']]['contact.merge'] = [
          'title' => E::ts('Dedupe - Merge 2 Contacts'),
          'number' => '=== 2',
          'icon' => 'fa-compress',
          'crmPopup' => [
            'path' => "'civicrm/contact/merge'",
            'query' => '{reset: 1, cid: ids[0], oid: ids[1], action: "update"}',
          ],
        ];
      }
      if (\CRM_Core_Component::isEnabled('CiviMail') && (
        \CRM_Core_Permission::access('CiviMail') || !$this->checkPermissions ||
        (\CRM_Mailing_Info::workflowEnabled() && \CRM_Core_Permission::check('create mailings'))
      )) {
        $tasks[$entity['name']]['contact.mailing'] = [
          'title' => E::ts('Email - schedule/send via CiviMail'),
          'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskMailing.html'],
          'icon' => 'fa-paper-plane',
        ];
      }
    }

    if ($entity['name'] === 'Contribution') {
      // FIXME: tasks() function always checks permissions, should respect `$this->checkPermissions`
      foreach (\CRM_Contribute_Task::tasks() as $id => $task) {
        if (!empty($task['url'])) {
          $key = \CRM_Core_Key::get(\CRM_Utils_Array::first((array) $task['class']), TRUE);
          $tasks[$entity['name']]['contribution.' . $id] = [
            'title' => $task['title'],
            'icon' => $task['icon'] ?? 'fa-gear',
            'crmPopup' => [
              'path' => "'{$task['url']}'",
              'data' => "{id: ids.join(','), qfKey: '$key'}",
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
