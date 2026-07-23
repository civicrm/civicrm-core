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

namespace Civi\Api4\Subscriber;

use Civi\Api4\AfformSubmissionData;
use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CRM_Afform_ExtensionUtil as E;

/**
 * Provides default display for AfAdmin_Submission_List search display
 *
 * @service
 * @internal
 */
class AfformSubmissionDefaultDisplaySubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.search.defaultDisplay' => [
        'getDefault',
      ],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function getDefault(GenericHookEvent $e) {
    $savedSearchName = $e->savedSearch['name'] ?? NULL;

    if ($savedSearchName !== 'AfAdmin_Submission_List' || empty($e->context['filters']['afformName'])) {
      return;
    }

    $fields = AfformSubmissionData::getFields(FALSE)
      ->setAction('get')
      ->setAfformName($e->context['filters']['afformName'])
      ->execute();

    $columns = [
      [
        'type' => 'field',
        'key' => 'contact_id.display_name',
        'label' => 'Submitted by',
        'sortable' => TRUE,
        'link' => [
          'entity' => 'Contact',
          'action' => 'view',
          'join' => 'contact_id',
          'target' => '_blank',
        ],
        'empty_value' => E::ts('Anonymous'),
        'cssRules' => [
          [
            'disabled',
            'contact_id.display_name',
            'IS NULL',
          ],
        ],
      ],
      [
        'type' => 'field',
        'key' => 'submission_date',
        'label' => 'Submission Date/Time',
        'sortable' => TRUE,
      ],
      [
        'type' => 'field',
        'key' => 'status_id:label',
        'label' => 'Submission Status',
        'sortable' => TRUE,
        'icons' => [
          [
            'field' => 'status_id:icon',
            'side' => 'left',
          ],
        ],
      ],
    ];

    $skip = ['id', 'contact_id', 'submission_date', 'status_id'];

    // Add all form fields as columns
    foreach ($fields as $field) {
      $fieldName = $field['name'];
      if (in_array($fieldName, $skip, TRUE)) {
        continue;
      }
      if (in_array('label', $field['suffixes'] ?? [])) {
        $fieldName .= ':label';
      }
      $column = [
        'type' => 'field',
        'key' => $fieldName,
        'label' => $field['label'],
        'sortable' => TRUE,
      ];
      if ($field['fk_entity']) {
        $column['link'] = [
          'entity' => $field['fk_entity'],
          'action' => 'view',
          'join' => strrchr($field['name'], '.', TRUE),
          'target' => '_blank',
        ];
      }
      $columns[] = $column;
    }

    $columns[] = [
      'size' => 'btn-xs',
      'type' => 'menu',
      'icon' => 'fa-bars',
      'alignment' => 'text-right',
      'links' => [
        [
          'entity' => 'AfformSubmission',
          'action' => 'view',
          'join' => '',
          'target' => 'crm-popup',
          'icon' => 'fa-external-link',
          'text' => E::ts('View'),
          'style' => 'default',
        ],
        [
          'icon' => 'fa-check-square-o',
          'text' => E::ts('Process'),
          'style' => 'default',
          'task' => 'process',
          'entity' => 'AfformSubmissionData',
        ],
        [
          'icon' => 'fa-rectangle-xmark',
          'text' => E::ts('Reject'),
          'style' => 'warning',
          'task' => 'reject',
          'entity' => 'AfformSubmissionData',
        ],
        [
          'icon' => 'fa-trash',
          'text' => E::ts('Delete'),
          'style' => 'danger',
          'task' => 'delete',
          'entity' => 'AfformSubmissionData',
        ],
      ],
    ];

    $e->display['settings'] += [
      'description' => $e->savedSearch['description'] ?? NULL,
      'sort' => [
        ['submission_date', 'ASC'],
      ],
      'limit' => (int) \Civi::settings()->get('default_pager_size'),
      'pager' => [
        'show_count' => TRUE,
        'expose_limit' => TRUE,
      ],
      'placeholder' => 5,
      'toggleColumns' => TRUE,
      'columns' => $columns,
      'actions_display_mode' => 'menu',
      'actions' => TRUE,
      'classes' => ['table', 'table-striped'],
    ];

  }

}
