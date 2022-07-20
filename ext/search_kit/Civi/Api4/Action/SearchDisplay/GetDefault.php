<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Api4\SavedSearch;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Search\Display;
use CRM_Search_ExtensionUtil as E;
use Civi\Api4\Query\SqlEquation;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\Query\SqlField;
use Civi\Api4\Query\SqlFunction;
use Civi\Api4\Query\SqlFunctionGROUP_CONCAT;
use Civi\Api4\Utils\CoreUtil;
use Civi\API\Exception\UnauthorizedException;

/**
 * Return the default results table for a saved search.
 *
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
   * @var array
   */
  private $_joinMap;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws UnauthorizedException
   * @throws \API_Exception
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    // Only SearchKit admins can use this in unsecured "preview mode"
    if (
      is_array($this->savedSearch) && $this->checkPermissions &&
      !\CRM_Core_Permission::check([['administer CiviCRM data', 'administer search_kit']])
    ) {
      throw new UnauthorizedException('Access denied');
    }
    $this->loadSavedSearch();
    $this->expandSelectClauseWildcards();
    // Use label from saved search
    $label = $this->savedSearch['label'] ?? '';
    // Fall back on entity title as label
    if (!strlen($label) && !empty($this->savedSearch['api_entity'])) {
      $label = CoreUtil::getInfoItem($this->savedSearch['api_entity'], 'title_plural');
    }
    $display = [
      'id' => NULL,
      'name' => NULL,
      'saved_search_id' => $this->savedSearch['id'] ?? NULL,
      'label' => $label,
      'type' => 'table',
      'acl_bypass' => FALSE,
      'settings' => [
        'actions' => TRUE,
        'limit' => \Civi::settings()->get('default_pager_size'),
        'classes' => ['table', 'table-striped'],
        'pager' => [
          'show_count' => TRUE,
          'expose_limit' => TRUE,
        ],
        'placeholder' => 5,
        'sort' => [],
        'columns' => [],
      ],
    ];
    // Supply default sort if no orderBy given in api params
    if (!empty($this->savedSearch['api_entity']) && empty($this->savedSearch['api_params']['orderBy'])) {
      $defaultSort = CoreUtil::getInfoItem($this->savedSearch['api_entity'], 'order_by');
      if ($defaultSort) {
        $display['settings']['sort'][] = [$defaultSort, 'ASC'];
      }
    }
    foreach ($this->getSelectClause() as $key => $clause) {
      $display['settings']['columns'][] = $this->configureColumn($clause, $key);
    }
    $display['settings']['columns'][] = [
      'label' => '',
      'type' => 'menu',
      'icon' => 'fa-bars',
      'size' => 'btn-xs',
      'style' => 'secondary-outline',
      'alignment' => 'text-right',
      'links' => $this->getLinksMenu(),
    ];
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
    $results = [$display];
    // Replace pseudoconstants
    FormattingUtil::formatOutputValues($results, $fields);
    $result->exchangeArray($this->selectArray($results));
  }

  /**
   * @param array{fields: array, expr: SqlExpression, dataType: string} $clause
   * @param string $key
   * @return array
   */
  private function configureColumn($clause, $key) {
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
   * @param \Civi\Api4\Query\SqlExpression $expr
   * @return string
   */
  private function getColumnLabel(SqlExpression $expr) {
    if ($expr instanceof SqlFunction) {
      $args = [];
      foreach ($expr->getArgs() as $arg) {
        foreach ($arg['expr'] ?? [] as $ex) {
          $args[] = $this->getColumnLabel($ex);
        }
      }
      return '(' . $expr->getTitle() . ')' . ($args ? ' ' . implode(',', array_filter($args)) : '');
    }
    if ($expr instanceof SqlEquation) {
      $args = [];
      foreach ($expr->getArgs() as $arg) {
        $args[] = $this->getColumnLabel($arg['expr']);
      }
      return '(' . implode(',', array_filter($args)) . ')';
    }
    elseif ($expr instanceof SqlField) {
      $field = $this->getField($expr->getExpr());
      $label = '';
      if (!empty($field['explicit_join'])) {
        $label = $this->getJoinLabel($field['explicit_join']) . ': ';
      }
      if (!empty($field['implicit_join']) && empty($field['custom_field_id'])) {
        $field = $this->getField(substr($expr->getAlias(), 0, -1 - strlen($field['name'])));
      }
      return $label . $field['label'];
    }
    else {
      return NULL;
    }
  }

  /**
   * @param string $joinAlias
   * @return string
   */
  private function getJoinLabel($joinAlias) {
    if (!isset($this->_joinMap)) {
      $this->_joinMap = [];
      $joinCount = [$this->savedSearch['api_entity'] => 1];
      foreach ($this->savedSearch['api_params']['join'] ?? [] as $join) {
        [$entityName, $alias] = explode(' AS ', $join[0]);
        $num = '';
        if (!empty($joinCount[$entityName])) {
          $num = ' ' . (++$joinCount[$entityName]);
        }
        else {
          $joinCount[$entityName] = 1;
        }
        $label = CoreUtil::getInfoItem($entityName, 'title');
        $this->_joinMap[$alias] = $label . $num;
      }
    }
    return $this->_joinMap[$joinAlias];
  }

  /**
   * @param array $col
   * @param array{fields: array, expr: SqlExpression, dataType: string} $clause
   */
  private function getColumnLink(&$col, $clause) {
    if ($clause['expr'] instanceof SqlField || $clause['expr'] instanceof SqlFunctionGROUP_CONCAT) {
      $field = $clause['fields'][0] ?? NULL;
      if ($field &&
        CoreUtil::getInfoItem($field['entity'], 'label_field') === $field['name'] &&
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
  private function getLinksMenu() {
    $menu = [];
    $mainEntity = $this->savedSearch['api_entity'] ?? NULL;
    if ($mainEntity && !$this->canAggregate(CoreUtil::getIdFieldName($mainEntity))) {
      foreach (CoreUtil::getInfoItem($mainEntity, 'paths') as $action => $path) {
        $link = $this->formatMenuLink($mainEntity, $action);
        if ($link) {
          $menu[] = $link;
        }
      }
    }
    $keys = ['entity' => TRUE, 'bridge' => TRUE];
    foreach ($this->getJoins() as $join) {
      if (!$this->canAggregate($join['alias'] . '.' . CoreUtil::getIdFieldName($join['entity']))) {
        foreach (array_filter(array_intersect_key($join, $keys)) as $joinEntity) {
          foreach (CoreUtil::getInfoItem($joinEntity, 'paths') as $action => $path) {
            $link = $this->formatMenuLink($joinEntity, $action, $join['alias']);
            if ($link) {
              $menu[] = $link;
            }
          }
        }
      }
    }
    return $menu;
  }

  /**
   * @param string $entity
   * @param string $action
   * @param string $joinAlias
   * @return array|NULL
   */
  private function formatMenuLink(string $entity, string $action, string $joinAlias = NULL) {
    if ($joinAlias && $entity === $this->getJoin($joinAlias)['entity']) {
      $entityLabel = $this->getJoinLabel($joinAlias);
    }
    else {
      $entityLabel = TRUE;
    }
    $link = Display::getEntityLinks($entity, $entityLabel)[$action] ?? NULL;
    return $link ? $link + ['join' => $joinAlias] : NULL;
  }

}
