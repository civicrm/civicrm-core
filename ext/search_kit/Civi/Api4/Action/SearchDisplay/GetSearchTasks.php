<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\API\Exception\UnauthorizedException;
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
   * Override execute method to change the result object type
   * @return \Civi\Api4\Result\SearchDisplayRunResult
   */
  public function execute() {
    return parent::execute();
  }

  public function _run(\Civi\Api4\Generic\Result $result) {
    $this->checkPermissionToLoadSearch();
    $this->loadSavedSearch();
    $this->loadSearchDisplay();

    // Hack to support relationships
    $mainEntityName = $this->savedSearch['api_entity'];
    $mainEntityName = ($mainEntityName === 'RelationshipCache') ? 'Relationship' : $mainEntityName;

    $actionableEntities = $this->getActionableEntities();
    $enabledEntities = !empty($this->display['settings']['actions']) ? $this->getEnabledActionEntities() : [];
    $rowKeyFields = $this->getRowKeyFields();
    $keyIndices = array_flip(array_keys($rowKeyFields));

    $targetEntities = [];
    foreach ($enabledEntities as $alias => $info) {
      $targetEntities[$alias] = $info + [
        'inMenu' => TRUE,
        'keyIndex' => $keyIndices[$alias] ?? NULL,
      ];
    }
    // Include any actionable entity referenced by an in-row task link
    foreach ($this->display['settings']['columns'] ?? [] as $column) {
      $links = !empty($column['links']) ? $column['links'] : (!empty($column['link']) ? [$column['link']] : []);
      foreach ($links as $link) {
        if (!empty($link['task']) && empty($link['path'])) {
          $alias = $link['join'] ?? '';
          if (isset($actionableEntities[$alias]) && !isset($targetEntities[$alias])) {
            $targetEntities[$alias] = $actionableEntities[$alias] + [
              'inMenu' => FALSE,
              'keyIndex' => $keyIndices[$alias] ?? NULL,
            ];
          }
        }
      }
    }

    $entityNames = array_unique(array_merge([$mainEntityName], array_column($targetEntities, 'entity')));
    $entityInfoMap = Entity::get(FALSE)
      ->addWhere('name', 'IN', $entityNames)
      ->addSelect('name', 'title', 'title_plural', 'primary_key')
      ->execute()
      ->indexBy('name');
    // Used by searchDisplayTasksTrait
    $result->editable['entityInfo'] = (array) $entityInfoMap;

    $tasks = [];
    $builtEntities = [];
    foreach ($targetEntities as $targetInfo) {
      $entityName = $targetInfo['entity'];
      if (!isset($builtEntities[$entityName]) && isset($entityInfoMap[$entityName])) {
        $builtEntities[$entityName] = TRUE;
        $this->buildEntityTasks($entityInfoMap[$entityName], $tasks);
      }
    }

    // Call `hook_civicrm_searchKitTasks` which serves 3 purposes:
    // 1. For extensions to augment this list of tasks
    // 2. To allow tasks to be added/removed per search display
    //    Note: Use Events::W_LATE to do so after the tasks are filtered per search-display settings.
    // 3. To get a full list of Angular modules which provide tasks.
    $null = NULL;
    $checkPermissions = $this->checkPermissions;
    $userId = $this->checkPermissions ? \CRM_Core_Session::getLoggedInContactID() : NULL;
    \CRM_Utils_Hook::singleton()->invoke(['tasks', 'checkPermissions', 'userId', 'search', 'display'],
      $tasks, $checkPermissions, $userId,
      $this->savedSearch, $this->display, $null, 'civicrm_searchKitTasks'
    );

    $enabledActions = $this->display['settings']['actions'] ?? NULL;
    $outputTasks = [];
    $menuEntityCount = count($enabledEntities);

    foreach ($targetEntities as $alias => $targetInfo) {
      $entityName = $targetInfo['entity'];
      $entityTasks = $tasks[$entityName] ?? [];
      // If the entity is Individual, Organization, or Household, add the "Contact" actions
      if (CoreUtil::isContact($entityName)) {
        $entityTasks = array_merge($entityTasks, $tasks['Contact'] ?? []);
      }

      $aliasTasks = [];
      $prefix = $alias !== '' ? "$alias:" : '';
      foreach ($entityTasks as $name => $task) {
        if ($name === 'download') {
          continue;
        }
        $namespacedName = $prefix . $name;
        $inMenu = $task['inMenu'] ?? $targetInfo['inMenu'];
        if ($targetInfo['inMenu'] && is_array($enabledActions) && !empty($task['_filteredBySubscriber'])) {
          $inMenu = in_array($namespacedName, $enabledActions, TRUE)
            || ($menuEntityCount === 1 && in_array($name, $enabledActions, TRUE));
        }
        elseif (!$targetInfo['inMenu']) {
          $inMenu = FALSE;
        }
        unset($task['_filteredBySubscriber']);

        $task['name'] = $namespacedName;
        $task['entity'] = $entityName;
        $task['join'] = $alias;
        $task['entityLabel'] = $targetInfo['label'];
        $task['idField'] = $targetInfo['idField'];
        $task['keyIndex'] = $targetInfo['keyIndex'];
        $task['inMenu'] = $inMenu;
        // Add default for number of rows action requires
        $task += ['number' => '> 0'];
        if (!empty($task['apiBatch']['fields'])) {
          if (!$this->getApiBatchFields($task)) {
            continue;
          }
        }
        // If action includes a WHERE clause, add it to the conditions (see e.g. the enable/disable actions)
        if (!empty($task['apiBatch']['params']['where'])) {
          $task['conditions'] = array_merge($task['conditions'] ?? [], $task['apiBatch']['params']['where']);
        }
        $aliasTasks[] = $task;
      }

      usort($aliasTasks, function($a, $b) {
        return strnatcasecmp($a['title'], $b['title']);
      });
      $outputTasks = array_merge($outputTasks, $aliasTasks);
    }

    // Add the global "Download Spreadsheet" task once when actions menu is enabled
    if (!empty($enabledActions) && (!is_array($enabledActions) || in_array('download', $enabledActions, TRUE))) {
      $firstTarget = reset($enabledEntities) ?: reset($targetEntities);
      $outputTasks[] = [
        'name' => 'download',
        'module' => 'crmSearchTasks',
        'title' => E::ts('Download Spreadsheet'),
        'icon' => 'fa-download',
        'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskDownload.html'],
        // Does not require any rows to be selected
        'number' => '>= 0',
        'entity' => $firstTarget['entity'] ?? $mainEntityName,
        'join' => $menuEntityCount > 1 ? NULL : ($firstTarget['alias'] ?? ''),
        'entityLabel' => $menuEntityCount > 1 ? '' : ($firstTarget['label'] ?? ($entityInfoMap[$mainEntityName]['title_plural'] ?? '')),
        'idField' => $firstTarget['idField'] ?? NULL,
        'keyIndex' => NULL,
        'inMenu' => TRUE,
      ];
      if ($menuEntityCount <= 1) {
        usort($outputTasks, function($a, $b) {
          return strnatcasecmp($a['title'], $b['title']);
        });
      }
    }

    $result->exchangeArray($outputTasks);
  }

  /**
   * Populate core/entity tasks for a single entity into $tasks[$entity['name']].
   */
  private function buildEntityTasks(array $entity, array &$tasks): void {
    $actions = [];
    $fields = [];
    try {
      $actions = (array) civicrm_api4($entity['name'], 'getActions', [
        'checkPermissions' => $this->checkPermissions,
        'where' => [['name', 'IN', ['get', 'update', 'delete']]],
      ], 'name');
      $fields = (array) civicrm_api4($entity['name'], 'getFields', [
        'checkPermissions' => $this->checkPermissions,
        'where' => [['deprecated', '=', FALSE], ['type', '=', 'Field']],
      ], 'name');
    }
    catch (UnauthorizedException $e) {
      // Limited access user
    }

    $tasks[$entity['name']] ??= [];

    if (array_key_exists('get', $actions)) {
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
    }

    if (array_key_exists('update', $actions)) {
      $tasks[$entity['name']]['update'] = [
        'module' => 'crmSearchTasks',
        'title' => E::ts('Update %1', [1 => $entity['title_plural']]),
        'icon' => 'fa-save',
        'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskUpdate.html'],
        // Default values can be set via `hook_civicrm_searchKitTasks`
        'values' => [],
      ];

      // Enable/disable are basically shortcut update actions
      if (isset($fields['is_active'])) {
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

    if (array_key_exists('delete', $actions)) {
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

    if ($entity['name'] === 'Group' && array_key_exists('get', $actions)) {
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

    if ($entity['name'] === 'Tag' && array_key_exists('update', $actions) && array_key_exists('delete', $actions)) {
      $tasks['Tag']['merge'] = [
        'module' => 'crmSearchTasks',
        'title' => E::ts('Merge Tags'),
        'icon' => 'fa-compress',
        'number' => '>= 2',
        'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskTagMerge.html'],
      ];
    }

    if ($entity['name'] === 'Contribution') {
      $defaultSoftCreditTypeID = \CRM_Core_OptionGroup::getDefaultValue('soft_credit_type');
      $tasks['Contribution']['add_soft_credit'] = [
        'title' => E::ts('Add Soft Credits'),
        'icon' => 'fa-user-plus',
        'module' => 'crmSearchTasks',
        'apiBatch' => [
          'entity' => 'ContributionSoft',
          'action' => 'save',
          'runMsg' => E::ts('Adding soft credits...'),
          'idField' => 'contribution_id',
          'fields' => [
            ['name' => 'contact_id', 'required' => TRUE],
            ['name' => 'soft_credit_type_id', 'required' => TRUE, 'default_value' => $defaultSoftCreditTypeID],
          ],
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
              'mode' => $task['mode'] ?? 'back',
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
      if (array_key_exists('update', $actions)) {
        $tasks[$entity['name']]['contact.relationship'] = [
          'title' => E::ts('Add Relationship'),
          'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskRelationship.html'],
          'icon' => 'fa-user-plus',
          'module' => 'crmSearchTasks',
          // Initial values can be set via `hook_civicrm_searchKitTasks`
          // @var array{contact_id: array, relationship_type: string, disableRelationshipSelect: bool, description: string, start_date: string, end_date: string}
          'values' => [],
          'relationshipTypes' => [],
        ];
        // In search mode, load relationship types enabled in case roles
        if (!empty($this->savedSearch)) {
          $tasks[$entity['name']]['contact.relationship']['relationshipTypes'] = $this->getRelationshipTypes($this->savedSearch, $entity['name']);
        }
      }
    }
  }

  private function getApiBatchFields(array &$task): bool {
    try {
      $fieldInfo = civicrm_api4($task['apiBatch']['entity'] ?? $task['entity'], 'getFields', [
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
    catch (\CRM_Core_Exception $e) {
      // User doesn't have permission
      return FALSE;
    }
    return TRUE;
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
        'name' => 'join',
      ],
      [
        'name' => 'entityLabel',
      ],
      [
        'name' => 'idField',
      ],
      [
        'name' => 'keyIndex',
        'data_type' => 'Integer',
      ],
      [
        'name' => 'inMenu',
        'data_type' => 'Boolean',
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

  private function getRelationshipTypes(array $savedSearch, string $entityName = 'Contact'): array {
    $types = [];
    $where = [];
    // If this search is limited to certain contact types we can restrict side_a
    if ($entityName !== 'Contact') {
      $where[] = ['contact_type_a', '=', $entityName];
    }
    elseif (($savedSearch['api_entity'] ?? '') === 'Contact') {
      // Contact type might be specified in the WHERE clause
      foreach ($savedSearch['api_params']['where'] ?? [] as $clause) {
        if (in_array($clause[0], ['contact_type', 'contact_type:name'], TRUE) && in_array($clause[1], ['=', 'IN'], TRUE) && !empty($clause[2]) && empty($clause[3])) {
          $clause[0] = str_replace('contact_type', 'contact_type_a', $clause[0]);
          $where[] = $clause;
        }
      }
    }
    // UNION query to fetch both sides of each relationship type (for non-symmetrical relationships)
    $relationshipTypes = civicrm_api4('EntitySet', 'get', [
      'select' => ['key', 'label_a_b', 'description', 'contact_type', 'contact_sub_type_b'],
      'sets' => [
        [
          'UNION ALL', 'RelationshipType', 'get', [
            'select' => [
              'CONCAT(id, "_a_b") AS key',
              'label_a_b',
              'description',
              'IFNULL(contact_type_b, "Contact") AS contact_type',
              'contact_sub_type_b',
              'name_b_a',
              'contact_type_a',
            ],
            'where' => [
              ['is_active', '=', TRUE],
            ],
          ],
        ],
        [
          'UNION ALL', 'RelationshipType', 'get', [
            'select' => [
              'CONCAT(id, "_b_a") AS key',
              'label_b_a',
              'description',
              'IFNULL(contact_type_a, "Contact") AS contact_type',
              'contact_sub_type_a',
              'name_a_b',
              'contact_type_b',
            ],
            'where' => [
              ['is_active', '=', TRUE],
              ['label_a_b', '!=', 'label_b_a', TRUE],
            ],
          ],
        ],
      ],
      'orderBy' => ['label_a_b' => 'ASC'],
      'where' => $where,
    ]);
    foreach ($relationshipTypes as $relationshipType) {
      $types[] = [
        'id' => $relationshipType['key'],
        'text' => $relationshipType['label_a_b'],
        'description' => $relationshipType['description'],
        'contact_type' => $relationshipType['contact_type'],
        'contact_sub_type' => $relationshipType['contact_sub_type_b'],
      ];
    }
    return $types;
  }

}
