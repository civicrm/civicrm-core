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

namespace Civi\Api4\Service;

use Civi\Api4\CaseType;
use CRM_Case_ExtensionUtil as E;
use Civi\Core\Event\GenericHookEvent;

/**
 * @service
 * @internal
 */
class CaseTasksProvider extends \Civi\Core\Service\AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_searchKitTasks' => ['addTasks', 100],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public function addTasks(GenericHookEvent $event): void {
    if (\CRM_Core_Permission::access('CiviCase') || !$event->checkPermissions) {
      $event->tasks['Contact']['contact.addCase'] = [
        'title' => E::ts('Case - Open New Case'),
        'uiDialog' => ['templateUrl' => '~/civiCaseTasks/civiCaseTaskOpenCase.html'],
        'icon' => 'fa-folder-plus',
        'module' => 'civiCaseTasks',
        // Default values can be set via `hook_civicrm_searchKitTasks`
        'values' => [],
        'allowMultipleClients' => (bool) \Civi::settings()->get('civicaseAllowMultipleClients'),
      ];
      $event->tasks['Case']['case.addRole'] = [
        'title' => E::ts('Case - Assign Role'),
        'uiDialog' => ['templateUrl' => '~/civiCaseTasks/civiCaseTaskCaseRole.html'],
        'icon' => 'fa-user-plus',
        'module' => 'civiCaseTasks',
        // Initial values can be set via `hook_civicrm_searchKitTasks`
        // @var array{contact_id: int, relationship_type: string, disableRelationshipSelect: bool, addOrReplace: string}
        'values' => [],
        'relationshipTypes' => [],
      ];
      // In search mode, load relationship types enabled in case roles
      if (!empty($event->search) && $event->search['api_entity'] === 'Case') {
        $event->tasks['Case']['case.addRole']['relationshipTypes'] = $this->getCaseRoles($event->search['api_params']);
      }
    }
  }

  private function getCaseRoles(array $apiParams): array {
    $where = [];
    // If this search is limited to certain case types we can restrict further
    foreach ($apiParams['where'] ?? [] as $clause) {
      if (in_array($clause[0], ['case_type_id', 'case_type_id:name'], TRUE) && in_array($clause[1], ['=', 'IN'], TRUE) && !empty($clause[2])) {
        $clause[0] = $clause[0] === 'case_type_id' ? 'id' : 'name';
        $where[] = $clause;
      }
    }
    $roleNames = [];
    $caseTypes = CaseType::get(FALSE)
      ->setWhere($where)
      ->addSelect('definition')
      ->execute();
    foreach ($caseTypes as $caseType) {
      if (!empty($caseType['definition']['caseRoles'])) {
        $roleNames = array_merge($roleNames, array_column($caseType['definition']['caseRoles'], 'name'));
      }
    }
    $roleNames = array_filter(array_unique($roleNames));
    $where = [];
    if ($roleNames) {
      $where[] = ['name_b_a', 'IN', array_values($roleNames)];
    }
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
    $caseRelationshipTypes = [];
    foreach ($relationshipTypes as $relationshipType) {
      $caseRelationshipTypes[] = [
        'id' => $relationshipType['key'],
        'text' => $relationshipType['label_a_b'],
        'description' => $relationshipType['description'],
        'contact_type' => $relationshipType['contact_type'],
        'contact_sub_type' => $relationshipType['contact_sub_type_b'],
      ];
    }
    return $caseRelationshipTypes;
  }

}
