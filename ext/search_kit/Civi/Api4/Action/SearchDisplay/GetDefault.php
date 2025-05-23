<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;
use Civi\Api4\SavedSearch;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Core\Event\GenericHookEvent;
use Civi\Search\Display;
use CRM_Search_ExtensionUtil as E;
use Civi\Api4\Query\SqlField;
use Civi\Api4\Query\SqlFunctionGROUP_CONCAT;
use Civi\Api4\Utils\CoreUtil;

/**
 * Generate the default display for a saved search.
 *
 * Dispatches `civi.search.defaultDisplay` event to allow subscribers to provide a display based on context.
 *
 * @method $this setType(string $type)
 * @method string getType()
 * @method $this setContext(array $context)
 * @method array getContext()
 * @package Civi\Api4\Action\SearchDisplay
 */
class GetDefault extends \Civi\Api4\Generic\AbstractAction {

  use SavedSearchInspectorTrait;
  use \Civi\Api4\Generic\Traits\ArrayQueryActionTrait;
  use \Civi\Api4\Generic\Traits\SelectParamTrait;

  /**
   * Either the name of the savedSearch or an array containing the savedSearch definition (for preview mode)
   * @var string|array|null
   */
  protected $savedSearch;

  /**
   * @var string
   * @optionsCallback getDisplayTypes
   */
  protected $type = 'table';

  /**
   * Provide context information; passed through to `civi.search.defaultDisplay` subscribers
   * @var array
   */
  protected $context = [];

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \CRM_Core_Exception
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    // Only SearchKit admins can use this in unsecured "preview mode"
    $this->checkPermissionToLoadSearch();
    $this->loadSavedSearch();
    $this->expandSelectClauseWildcards();
    // Use label from saved search
    $label = $this->savedSearch['label'] ?? '';
    // Fall back on entity title as label
    if (!strlen($label) && !empty($this->savedSearch['api_entity'])) {
      $label = CoreUtil::getInfoItem($this->savedSearch['api_entity'], 'title_plural');
    }
    // Initialize empty display
    $display = [
      'id' => NULL,
      'name' => NULL,
      'saved_search_id' => $this->savedSearch['id'] ?? NULL,
      'label' => $label,
      'type' => $this->type ?: 'table',
      'acl_bypass' => FALSE,
      'settings' => [],
    ];

    // Allow the default display to be modified
    // @see \Civi\Api4\Event\Subscriber\DefaultDisplaySubscriber
    \Civi::dispatcher()->dispatch('civi.search.defaultDisplay', GenericHookEvent::create([
      'savedSearch' => $this->savedSearch,
      'display' => &$display,
      'apiAction' => $this,
      'context' => $this->context,
    ]));

    $fields = $this->entityFields();
    // Allow implicit-join-style selection of saved search fields
    if ($this->savedSearch) {
      $display += \CRM_Utils_Array::prefixKeys($this->savedSearch, 'saved_search_id.');
      $fields += \CRM_Utils_Array::prefixKeys(SavedSearch::get()->entityFields(), 'saved_search_id.');
    }
    // Fill pseudoconstant keys with raw values for replacement
    foreach ($this->select as $fieldExpr) {
      [$fieldName, $suffix] = array_pad(explode(':', $fieldExpr), 2, NULL);
      if ($suffix && array_key_exists($fieldName, $display)) {
        $display[$fieldExpr] = $display[$fieldName];
      }
    }
    $displays = [$display];
    // Replace pseudoconstants e.g. type:icon
    FormattingUtil::formatOutputValues($displays, $fields);
    $result->exchangeArray($this->selectArray($displays));
  }

  /**
   * @param array{fields: array, expr: \Civi\Api4\Query\SqlExpression, dataType: string} $clause
   * @param string $key
   * @return array
   */
  public function configureColumn($clause, $key) {
    $col = [
      'type' => 'field',
      'key' => $key,
      'sortable' => !empty($clause['fields']),
      'label' => $this->getColumnLabel($clause['expr']),
    ];
    $this->getColumnLink($col, $clause);
    return $col;
  }

  /**
   * @param array $col
   * @param array{fields: array, expr: \Civi\Api4\Query\SqlExpression, dataType: string} $clause
   */
  private function getColumnLink(&$col, $clause) {
    if ($clause['expr'] instanceof SqlField || $clause['expr'] instanceof SqlFunctionGROUP_CONCAT) {
      $field = \CRM_Utils_Array::first($clause['fields'] ?? []);
      if ($field &&
        in_array($field['name'], array_merge(CoreUtil::getSearchFields($field['entity']), [CoreUtil::getInfoItem($field['entity'], 'label_field')]), TRUE) &&
        !empty(CoreUtil::getInfoItem($field['entity'], 'paths')['view'])
      ) {
        $col['link'] = [
          'entity' => $field['entity'],
          'join' => implode('.', array_filter([$field['explicit_join'], $field['implicit_join']])),
          'action' => 'view',
        ];
        // Hack to support links to relationships
        if ($col['link']['entity'] === 'RelationshipCache') {
          $col['link']['entity'] = 'Relationship';
        }
        $col['title'] = E::ts('View %1', [1 => CoreUtil::getInfoItem($field['entity'], 'title')]);
      }
    }
  }

  /**
   * return array[]
   */
  public function getLinksMenu() {
    $menu = [];
    $exclude = ['add', 'browse'];
    $mainEntity = $this->savedSearch['api_entity'] ?? NULL;
    if ($mainEntity && !$this->canAggregate(CoreUtil::getIdFieldName($mainEntity))) {
      foreach (Display::getEntityLinks($mainEntity, TRUE, $exclude) as $link) {
        $link['join'] = NULL;
        $menu[] = $link;
      }
    }
    if ($this->getField('is_active')) {
      $menu[] = [
        'entity' => $mainEntity,
        'task' => 'enable',
        'icon' => 'fa-toggle-on',
        'text' => E::ts('Enable'),
      ];
      $menu[] = [
        'entity' => $mainEntity,
        'task' => 'disable',
        'icon' => 'fa-toggle-off',
        'text' => E::ts('Disable'),
      ];
    }
    $keys = ['entity' => TRUE, 'bridge' => TRUE];
    foreach ($this->getJoins() as $join) {
      if (!$this->canAggregate($join['alias'] . '.' . CoreUtil::getIdFieldName($join['entity']))) {
        foreach (array_filter(array_intersect_key($join, $keys)) as $joinEntity) {
          $joinLabel = $this->getJoinLabel($join['alias']);
          foreach (Display::getEntityLinks($joinEntity, $joinLabel, $exclude) as $link) {
            $link['join'] = $join['alias'];
            $menu[] = $link;
          }
        }
      }
    }
    return $menu;
  }

  /**
   * Options callback for $this->type
   * @return array
   */
  public static function getDisplayTypes(): array {
    return array_column(\CRM_Core_OptionValue::getValues(['name' => 'search_display_type']), 'value');
  }

}
