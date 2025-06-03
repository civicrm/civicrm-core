<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;
use Civi\Api4\Utils\CoreUtil;
use CRM_Search_ExtensionUtil as E;
use Civi\Api4\Entity;

/**
 * Load the available tasks for a given entity.
 *
 * @method $this setDisplay(array|string $display)
 * @method array|string|null getDisplay()
 * @package Civi\Api4\Action\SearchDisplay
 */
class GetSearchTasks extends \Civi\Api4\Generic\AbstractAction {

  use SavedSearchInspectorTrait;

  /**
   * An array containing the searchDisplay definition
   * @var string|array
   */
  protected $display;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \CRM_Core_Exception
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    $this->loadSavedSearch();
    $this->loadSearchDisplay();

    // Adding checkPermissions filters out actions the user is not allowed to perform
    $entityName = $this->savedSearch['api_entity'];
    // Hack to support relationships
    $entityName = ($entityName === 'RelationshipCache') ? 'Relationship' : $entityName;
    $entity = Entity::get($this->checkPermissions)->addWhere('name', '=', $entityName)
      ->addSelect('name', 'title_plural')
      ->setChain([
        'actions' => ['$name', 'getActions', ['where' => [['name', 'IN', ['update', 'delete']]]], 'name'],
        'fields' => ['$name', 'getFields', ['where' => [['deprecated', '=', FALSE], ['type', '=', 'Field']]], 'name'],
      ])
      ->execute()->first();

    if (!$entity) {
      return;
    }

    $tasks = [$entity['name'] => []];

    if (CoreUtil::isContact($entity['name']) || array_key_exists($entity['name'], \CRM_Export_BAO_Export::getComponents())) {
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

      // Enable/disable are basically shortcut update actions
      if (isset($entity['fields']['is_active'])) {
        $tasks[$entity['name']]['enable'] = [
          'title' => E::ts('Enable %1', [1 => $entity['title_plural']]),
          'icon' => 'fa-toggle-on',
          'apiBatch' => [
            'action' => 'update',
            'params' => [
              'values' => ['is_active' => TRUE],
              'where' => [['is_active', '=', FALSE]],
            ],
            'runMsg' => E::ts('Enabling %1 %2...'),
            'successMsg' => E::ts('Successfully enabled %1 %2.'),
            'errorMsg' => E::ts('An error occurred while attempting to enable %1 %2.'),
          ],
        ];
        $tasks[$entity['name']]['disable'] = [
          'title' => E::ts('Disable %1', [1 => $entity['title_plural']]),
          'icon' => 'fa-toggle-off',
          'apiBatch' => [
            'action' => 'update',
            'params' => [
              'values' => ['is_active' => FALSE],
              'where' => [['is_active', '=', TRUE]],
            ],
            'confirmMsg' => E::ts('Are you sure you want to disable %1 %2?'),
            'runMsg' => E::ts('Disabling %1 %2...'),
            'successMsg' => E::ts('Successfully disabled %1 %2.'),
            'errorMsg' => E::ts('An error occurred while attempting to disable %1 %2.'),
          ],
        ];
      }

      $taggable = \CRM_Core_OptionGroup::values('tag_used_for', FALSE, FALSE, FALSE, NULL, 'name');
      if (CoreUtil::isContact($entity['name']) || in_array($entity['name'], $taggable, TRUE)) {
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
        'title' => E::ts('Delete %1', [1 => $entity['title_plural']]),
        'icon' => 'fa-trash',
        'apiBatch' => [
          'action' => 'delete',
          'params' => NULL,
          'confirmMsg' => E::ts('Are you sure you want to delete %1 %2?'),
          'runMsg' => E::ts('Deleting %1 %2...'),
          'successMsg' => E::ts('Successfully deleted %1 %2.'),
          'errorMsg' => E::ts('An error occurred while attempting to delete %1 %2.'),
        ],
      ];
    }

    /*
     * ENTITY-SPECIFIC TASKS BELOW
     * FIXME: Move these somewhere?
     */

    if ($entity['name'] === 'Group') {
      $tasks['Group']['refresh'] = [
        'title' => E::ts('Refresh Group Cache'),
        'icon' => 'fa-refresh',
        'apiBatch' => [
          'action' => 'refresh',
          'runMsg' => E::ts('Refreshing %1 %2...'),
          'successMsg' => E::ts('%1 %2 Refreshed.'),
          'errorMsg' => E::ts('An error occurred while attempting to refresh %1 %2.'),
        ],
      ];
    }

    if (CoreUtil::isContact($entity['name'])) {
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

          // Print Labels action does not support popups, open full-screen
          $actionType = $id == \CRM_Core_Task::LABEL_CONTACTS ? 'redirect' : 'crmPopup';

          $tasks[$entity['name']]['contact.' . $id] = [
            'title' => $task['title'],
            'icon' => $task['icon'] ?? 'fa-gear',
            $actionType => [
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
    }

    // Call `hook_civicrm_searchKitTasks` which serves 3 purposes:
    // 1. For extensions to augment this list of tasks
    // 2. To allow tasks to be added/removed per search display
    //    Note: Use Events::W_LATE to do so after the tasks are filtered per search-display settings.
    // 3. To get a full list of Angular modules which provide tasks.
    //    Note: That's why this hook needs the base-level array and not just the array of tasks for `$entity`.
    //    Although it may seem wasteful to have extensions add tasks for all possible entities and then
    //    discard most of it (all but the ones relevant to `$entity`), it's necessary to do it this way
    //    so that they can be declared as angular dependencies - see search_kit_civicrm_angularModules().
    $null = NULL;
    $checkPermissions = $this->checkPermissions;
    $userId = $this->checkPermissions ? \CRM_Core_Session::getLoggedInContactID() : NULL;
    \CRM_Utils_Hook::singleton()->invoke(['tasks', 'checkPermissions', 'userId', 'search', 'display'],
      $tasks, $checkPermissions, $userId,
      $this->savedSearch, $this->display, $null, 'civicrm_searchKitTasks'
    );

    // If the entity is Individual, Organization, or Household, add the "Contact" actions
    if (CoreUtil::isContact($entity['name'])) {
      $tasks[$entity['name']] = array_merge($tasks[$entity['name']], $tasks['Contact']);
    }

    foreach ($tasks[$entity['name']] as $name => &$task) {
      $task['name'] = $name;
      $task['entity'] = $entity['name'];
      // Add default for number of rows action requires
      $task += ['number' => '> 0'];
      if (!empty($task['apiBatch']['fields'])) {
        $this->getApiBatchFields($task);
      }
      // If action includes a WHERE clause, add it to the conditions (see e.g. the enable/disable actions)
      if (!empty($task['apiBatch']['params']['where'])) {
        $task['conditions'] = array_merge($task['conditions'] ?? [], $task['apiBatch']['params']['where']);
      }
    }

    usort($tasks[$entity['name']], function($a, $b) {
      return strnatcasecmp($a['title'], $b['title']);
    });

    $result->exchangeArray($tasks[$entity['name']]);
  }

  private function getApiBatchFields(array &$task) {
    $fieldInfo = civicrm_api4($task['entity'], 'getFields', [
      'checkPermissions' => $this->getCheckPermissions(),
      'action' => $task['apiBatch']['action'] ?? 'update',
      'select' => ['name', 'label', 'description', 'input_type', 'data_type', 'serialize', 'options', 'fk_entity', 'required', 'nullable'],
      'loadOptions' => ['id', 'name', 'label', 'description', 'color', 'icon'],
      'where' => [['name', 'IN', array_column($task['apiBatch']['fields'], 'name')]],
    ])->indexBy('name');
    foreach ($task['apiBatch']['fields'] as &$field) {
      $field += $fieldInfo[$field['name']] ?? [];
    }
  }

  public static function fields(): array {
    return [
      [
        'name' => 'name',
      ],
      [
        'name' => 'module',
      ],
      [
        'name' => 'title',
      ],
      [
        'name' => 'icon',
      ],
      [
        'name' => 'number',
      ],
      [
        'name' => 'entity',
      ],
      [
        'name' => 'apiBatch',
        'data_type' => 'Array',
      ],
      [
        'name' => 'uiDialog',
        'data_type' => 'Array',
      ],
      [
        'name' => 'crmPopup',
        'data_type' => 'Array',
      ],
    ];
  }

}
