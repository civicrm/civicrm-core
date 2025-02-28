<?php

namespace Civi\Api4\Action\CustomGroup;

use CRM_Afform_ExtensionUtil as E;
use Civi\Api4\Utils\CoreUtil;

/**
 * Get dynamic afforms for a custom group.
 *
 * For single value custom groups we generate:
 * - a field block with all of the fields from the group
 * - a submission form for editing these fields on a parent entity record
 *
 * For multi-value custom groups we generate:
 * - a field block with all of the fields from the group
 * - a submission form for adding a new Custom record to a parent entity record
 * - a submission form for editing a particular Custom record
 * - (not implemented yet) a search form for viewing all the Custom records
 *
 * @package Civi\Api4\Action\CustomGroup
 */
class GetAfforms extends \Civi\Api4\Generic\BasicBatchAction {

  protected const FORM_TYPES = [
    'afblockCustom',
    'afformUpdateCustom',
    'afformCreateCustom',
    'afformViewCustom',
    'afsearchTabCustom',
  ];

  /**
   * Whether to generate the form layouts
   * @var bool
   */
  protected bool $getLayout = FALSE;

  /**
   * Limit to generating specific afform types
   *
   * Default to all
   *
   * @var array
   */
  protected array $formTypes = ['block', 'form', 'search'];

  protected function getSelect() {
    return ['id', 'name', 'title', 'is_multiple',
      'help_pre', 'help_post', 'extends', 'icon', 'style',
      'extends_entity_column_value', 'weight',
    ];
  }

  protected function doTask($item) {
    $forms = [];

    // get field names once, for use across all the generate actions
    $item['field_names'] = \Civi\Api4\CustomField::get(FALSE)
      ->addSelect('name')
      ->addWhere('custom_group_id', '=', $item['id'])
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->column('name');

    // Custom group has no enabled fields; nothing to generate.
    if (!$item['field_names']) {
      return [
        'id' => $item['id'],
        'forms' => $forms,
      ];
    }

    // restrict forms other than block to if Admin UI is enabled
    $hasAdminUi = \CRM_Extension_System::singleton()->getMapper()->isActiveModule('civicrm_admin_ui');
    if (!$hasAdminUi) {
      $this->formTypes = array_intersect(['block'], $this->formTypes);
    }

    foreach ($this->formTypes as $type) {
      switch ($type) {
        case 'block':
          $forms[] = $this->generateFieldBlock($item);
          break;

        case 'form':
          $forms[] = $this->generateViewForm($item);
          $forms[] = $this->generateUpdateForm($item);
          if ($item['is_multiple']) {
            $forms[] = $this->generateCreateForm($item);
          }
          break;

        case 'search':
          // TODO:
          // 1. tabs with grid display
          if (
            $item['is_multiple']
            && ($item['style'] === 'Tab with table')
          ) {
            $forms[] = $this->generateTabForm($item);
          }
          break;
      }
    }

    return [
      'id' => $item['id'],
      'forms' => $forms,
    ];
  }

  private function generateFieldBlock($item): array {
    $afform = [
      'name' => 'afblockCustom_' . $item['name'],
      'type' => 'block',
      'requires' => [],
      'title' => E::ts('%1 block', [1 => $item['title']]),
      'description' => '',
      'is_public' => FALSE,
      'permission' => ['access CiviCRM'],
      'entity_type' => $item['extends'],
      'icon' => $item['icon'],
    ];
    if ($item['is_multiple']) {
      $afform['join_entity'] = 'Custom_' . $item['name'];
    }
    if ($this->getLayout) {
      $afform['layout'] = \CRM_Core_Smarty::singleton()->fetchWith(
        'afform/customGroups/afblock.tpl',
        ['group' => $item]
      );
    }
    return $afform;
  }

  private function generateViewForm($item): array {
    $afform = [
      'name' => 'afformViewCustom_' . $item['name'],
      'type' => 'form',
      'title' => E::ts('View %1', [1 => $item['title']]),
      'description' => '',
      'is_public' => FALSE,
      // NOTE: we will use RBAC for entities to ensure
      // this form does not allow folks who shouldn't
      // to edit contacts
      'permission' => ['access CiviCRM'],
      'server_route' => 'civicrm/af/custom/' . $item['name'] . '/view',
      'icon' => $item['icon'],
    ];
    if ($this->getLayout) {

      // form entity depends on whether this is a multirecord custom group
      $formEntity = $item['is_multiple'] ?
        [
          'type' => 'Custom_' . $item['name'],
          'name' => 'Record',
          'label' => $item['extends'] . ' ' . $item['title'],
          'parent_field' => 'entity_id',
          'parent_field_defn' => [
            'input_type' => 'Hidden',
            'label' => FALSE,
          ],
        ] :
        [
          'type' => $item['extends'],
          'name' => $item['extends'] . '1',
          'label' => $item['extends'],
          'parent_field' => 'id',
          'parent_field_defn' => [
            'input_type' => 'Hidden',
            'label' => FALSE,
          ],
        ];

      $afform['layout'] = \CRM_Core_Smarty::singleton()->fetchWith(
        'afform/customGroups/afformView.tpl',
        [
          'formEntity' => $formEntity,
          'group' => $item,
        ]
      );
    }
    return $afform;
  }

  private function generateUpdateForm($item): array {
    $afform = [
      'name' => 'afformUpdateCustom_' . $item['name'],
      'type' => 'form',
      'title' => E::ts('Update %1', [1 => $item['title']]),
      'description' => '',
      'is_public' => FALSE,
      // NOTE: we will use RBAC for entities to ensure
      // this form does not allow folks who shouldn't
      // to edit contacts
      'permission' => ['access CiviCRM'],
      'server_route' => 'civicrm/af/custom/' . $item['name'] . '/update',
      'icon' => $item['icon'],
    ];
    if ($this->getLayout) {

      // form entity depends on whether this is a multirecord custom group
      $formEntity = $item['is_multiple'] ?
        [
          'type' => 'Custom_' . $item['name'],
          'name' => 'Record',
          'label' => $item['extends'] . ' ' . $item['title'],
          'parent_field' => 'entity_id',
          'parent_field_defn' => [
            'input_type' => 'Hidden',
            'label' => FALSE,
          ],
        ] :
        [
          'type' => $item['extends'],
          'name' => $item['extends'] . '1',
          'label' => $item['extends'],
          'parent_field' => 'id',
          'parent_field_defn' => [
            'input_type' => 'Hidden',
            'label' => FALSE,
          ],
        ];

      $afform['layout'] = \CRM_Core_Smarty::singleton()->fetchWith(
        'afform/customGroups/afformEdit.tpl',
        [
          'formEntity' => $formEntity,
          'formActions' => [
            'create' => FALSE,
            'update' => TRUE,
          ],
          'urlAutofill' => TRUE,
          'blockDirective' => _afform_angular_module_name('afblockCustom_' . $item['name'], 'dash'),
        ]
      );
    }
    return $afform;
  }

  private function generateCreateForm($item): array {
    $afform = [
      'name' => 'afformCreateCustom_' . $item['name'],
      'type' => 'form',
      'title' => E::ts('Add %1', [1 => $item['title']]),
      'description' => '',
      'is_public' => FALSE,
      // NOTE: we will use RBAC for entities to ensure
      // this form does not allow folks who shouldn't
      // to edit contacts
      'permission' => ['access CiviCRM'],
      'server_route' => 'civicrm/af/custom/' . $item['name'] . '/create',
      'icon' => $item['icon'],
    ];
    if ($this->getLayout) {
      $formEntity = [
        'type' => 'Custom_' . $item['name'],
        'name' => 'Record',
        'label' => $item['extends'] . ' ' . $item['title'],
        'parent_field' => 'entity_id',
        'parent_field_defn' => [
          'input_type' => 'Hidden',
          'label' => FALSE,
        ],
      ];
      $afform['layout'] = \CRM_Core_Smarty::singleton()->fetchWith(
        'afform/customGroups/afformEdit.tpl',
        [
          'formEntity' => $formEntity,
          'formActions' => [
            'create' => TRUE,
            'update' => FALSE,
          ],
          'urlAutofill' => FALSE,
          'blockDirective' => _afform_angular_module_name('afblockCustom_' . $item['name'], 'dash'),
        ]
      );
    }
    return $afform;
  }

  private function generateTabForm($item): array {
    $extendsLabel = CoreUtil::getInfoItem($item['extends'], 'title');
    $afform = [
      // name required to replace the existing tab
      'name' => 'afsearchTabCustom_' . $item['name'],
      'description' => E::ts('%1 tab display for %2', [1 => $extendsLabel, 2 => $item['title']]),
      'type' => 'search',
      'is_public' => FALSE,
      // Q: should this be more permissive if user has access
      // to contact record?
      // what about ACLs?
      'permission' => ['access all custom data'],
      'title' => $item['title'],
      'icon' => $item['icon'],
      'summary_weight' => 100 + ($item['weight'] ?? 0),
      'placement_filters' => [],
    ];
    $entityIdFilter = \CRM_Utils_String::convertStringToSnakeCase($item['extends']) . '_id';
    // Place in Contact Summary Tabs
    if (CoreUtil::isContact($item['extends'])) {
      // override e.g. "individual_id", we want "contact_id"
      $entityIdFilter = 'contact_id';
      $afform['placement'] = ['contact_summary_tab'];
      // Add contact_type filter (extends == contact_type, extends_entity_column_value == contact_sub_type)
      // Note: Afform placement_filters mixes contact_subtype in with contact_type
      if (!empty($item['extends_entity_column_value'])) {
        $afform['placement_filters']['contact_type'] = (array) $item['extends_entity_column_value'];
      }
      // Only add contact_type if a sub_type wasn't specified
      elseif ($item['extends'] !== 'Contact') {
        $afform['placement_filters']['contact_type'] = (array) $item['extends'];
      }
    }
    elseif ($item['extends'] === 'Event') {
      $afform['placement'] = ['event_manage_tab'];
      // Add event_type filter (extends_entity_column_value == event_type_id)
      if (!empty($item['extends_entity_column_value'])) {
        // Convert event_type_id to "event_type" (id -> name)
        $eventTypes = \Civi::entity('Event')->getOptions('event_type_id');
        $eventTypes = array_column($eventTypes, 'id', 'name');
        $afform['placement_filters']['event_type'] = array_keys(array_intersect($eventTypes, $item['extends_entity_column_value']));
      }
    }
    if ($this->getLayout) {
      // TODO: the template should be a table or grid depending
      // on $item['style']
      $afform['layout'] = \CRM_Core_Smarty::singleton()->fetchWith(
        'afform/customGroups/afsearchTab.tpl',
        [
          'group' => $item,
          'saved_search' => 'Custom_' . $item['name'] . '_Search',
          'display_type' => 'table',
          'search_display' => 'Custom_' . $item['name'] . '_Tab',
          // 'contact_id', 'event_id', etc.
          'entity_id_filter' => $entityIdFilter,
        ]
      );
    }
    return $afform;
  }

  public static function getCustomGroupAfforms($event) {
    $formGenerate = \Civi\Api4\CustomGroup::getAfforms(FALSE)
      ->addWhere('is_active', '=', TRUE);

    // only generate layout if this is required by the Afform.get
    $formGenerate->setGetLayout($event->getLayout);

    // if the Afform.get is limited to specific form types
    // we can limit to those
    if ($event->getTypes) {
      $formGenerate->setFormTypes($event->getTypes);
    }

    // if the Afform.get is limited to specific form names
    // we can limit our action to specific custom groups
    if ($event->getNames) {
      $groupNames = self::parseGroupNamesFromAfformGetNames($event->getNames);
      if (!$groupNames) {
        // Afform.get is limited to specific form names, none of which
        // correspond to a custom group form, so we can return early
        return;
      }

      $formGenerate->addWhere('name', 'IN', $groupNames);
    }

    $formsByGroup = $formGenerate->execute()->column('forms');

    // add generated forms back to the hook event
    // indexing by the form name
    $forms = array_merge(...$formsByGroup);

    foreach ($forms as $form) {
      $form['has_base'] = TRUE;
      // blocks are provided by this module, but others are effectively provided
      // by `civicrm_admin_ui` at this stage
      $form['base_module'] = ($form['type'] === 'block') ? E::LONG_NAME : 'civicrm_admin_ui';
      $event->afforms[$form['name']] = $form;
    }
  }

  protected static function parseGroupNamesFromAfformGetNames(array $getNames): array {
    // preserves previous logic when module_name or directive_name
    // are specified - look for at least one appearance of a known prefix
    // otherwise we can exit early
    if (!empty($getNames['module_name'])) {
      $imploded = implode(' ', $getNames['module_name']);
      $found = FALSE;
      foreach (self::FORM_TYPES as $typePrefix) {
        if (str_contains($imploded, $typePrefix)) {
          $found = TRUE;
          break;
        }
      }
      if (!$found) {
        return [];
      }
    }

    if (!empty($getNames['directive_name'])) {
      $imploded = implode(' ', $getNames['directive_name']);
      $found = FALSE;
      foreach (self::FORM_TYPES as $typePrefix) {
        $kebabPrefix = _afform_angular_module_name($typePrefix, 'dash');
        if (str_contains($imploded, $kebabPrefix)) {
          $found = TRUE;
          break;
        }
      }
      if (!$found) {
        return [];
      }
    }

    $formNames = $getNames['name'] ?? [];

    $groupNames = array_map(fn ($name) => self::parseGroupNameFromFormName($name), $formNames);

    // filter nulls where form name didnt correspond to a group name
    return array_filter($groupNames);
  }

  /**
   * For afform base name, get the custom group name it corresponds to
   * Or null if none
   *
   * @return ?string
   */
  protected static function parseGroupNameFromFormName(string $name): ?string {
    $prefixLength = strpos($name, '_');

    if (!$prefixLength) {
      // no prefix
      return NULL;
    }
    $prefix = substr($name, 0, $prefixLength);

    if (!in_array($prefix, self::FORM_TYPES)) {
      // prefix doesn't match our custom group
      // form prefixes
      return NULL;
    }

    // TODO the prefix tells us which types of forms we
    // want - so we could further optimise by passing
    // that along to the generate action

    // we have a match - return everything after the '_'
    return substr($name, $prefixLength + 1);
  }

}
