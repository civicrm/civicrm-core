<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\API\Request;
use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\Result\SearchDisplayRunResult;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\FormattingUtil;

/**
 * Load the results for rendering a SearchDisplay.
 *
 * @method $this setReturn(string $return)
 * @method string getReturn()
 * @method $this setLimit(int $limit)
 * @method int getLimit()
 * @package Civi\Api4\Action\SearchDisplay
 */
class Run extends AbstractRunAction {

  /**
   * What part of the result to return. Possible values are:
   * - "row_count": just the total number of rows
   * - "id": the 'key' of every row
   * - "page:x": a single page
   * - "scroll:x": one 'page' of autocomplete results
   * - "tally": summary row
   * - null: all rows
   * @var string
   */
  protected $return;

  /**
   * Number of results to return
   * @var int
   */
  protected $limit;

  /**
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   * @throws \CRM_Core_Exception
   */
  protected function processResult(SearchDisplayRunResult $result) {
    $entityName = $this->savedSearch['api_entity'];
    $apiParams =& $this->_apiParams;
    $settings = $this->display['settings'];
    $page = $index = NULL;
    $key = $this->return;
    // Pager can operate in "page" mode for traditional pager, or "scroll" mode for infinite scrolling
    $pagerMode = 'page';

    $this->preprocessLinks();
    $this->augmentSelectClause($apiParams);
    $this->applyFilters();

    switch ($this->return) {
      case 'id':
        $key = CoreUtil::getIdFieldName($entityName);
        $index = [$key];
      case 'row_count':
        if (empty($apiParams['having'])) {
          $apiParams['select'] = [];
        }
        if (!in_array($key, $apiParams['select'], TRUE)) {
          $apiParams['select'][] = $key;
        }
        unset($apiParams['orderBy'], $apiParams['limit']);
        break;

      case 'tally':
        $result[] = $this->getTally();
        return;

      default:
        // Pager mode: `page:n`
        // AJAX scroll mode: `scroll:n`
        // Or NULL for unlimited results
        if (($settings['pager'] ?? FALSE) !== FALSE && $key && preg_match('/^(page|scroll):\d+$/', $key)) {
          [$pagerMode, $page] = explode(':', $key);
          $limit = !empty($settings['pager']['expose_limit']) && $this->limit ? $this->limit : NULL;
        }
        $apiParams['debug'] = $this->debug;
        $apiParams['limit'] = $limit ?? $settings['limit'] ?? NULL;
        $apiParams['offset'] = $page ? $apiParams['limit'] * ($page - 1) : 0;
        // In scroll mode, add one extra to the limit as a lookahead to see if there are more results
        if ($apiParams['limit'] && $pagerMode === 'scroll') {
          $apiParams['limit']++;
        }
        $apiParams['orderBy'] = $this->getOrderByFromSort();
        // Add metadata needed for inline-editing
        if ($this->getActionName() === 'run' && $pagerMode === 'page') {
          $this->addEditableInfo($result);
        }
    }

    $apiResult = civicrm_api4($entityName, 'get', $apiParams, $index);
    // Copy over meta properties to this result
    $result->rowCount = $apiResult->rowCount;
    $result->debug = $apiResult->debug;

    if ($this->return === 'row_count' || $this->return === 'id') {
      $result->exchangeArray($apiResult->getArrayCopy());
    }
    else {
      if ($pagerMode === 'scroll') {
        // Remove the extra result appended for the sake of infinite scrolling
        $result->setCountMatched($apiResult->countFetched());
        $apiResult = array_slice((array) $apiResult, 0, $apiParams['limit'] - 1);
      }
      else {
        $result->toolbar = $this->formatToolbar();
      }
      $result->exchangeArray($this->formatResult($apiResult));
      $result->labels = $this->filterLabels;
    }
  }

  /**
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getTally(): array {
    $apiParams = $this->_apiParams;
    unset($apiParams['orderBy'], $apiParams['limit']);
    $api = Request::create($this->savedSearch['api_entity'], 'get', $apiParams);
    $api->setDefaultWhereClause();
    $queryObject = new Api4SelectQuery($api);
    $queryObject->forceSelectId = FALSE;
    $sql = $queryObject->getSql();
    $select = [];
    $columns = $this->display['settings']['columns'];
    foreach ($columns as $col) {
      $key = $col['key'] ?? '';
      $rawKey = str_replace(['.', ':'], '_', $key);
      if (!empty($col['tally']['fn']) && \CRM_Utils_Rule::mysqlColumnNameOrAlias($rawKey)) {
        /* @var \Civi\Api4\Query\SqlFunction $sqlFnClass */
        $sqlFnClass = '\Civi\Api4\Query\SqlFunction' . $col['tally']['fn'];
        $fnArgs = ["`$key`"];
        // Add default args (e.g. `GROUP_CONCAT(SEPARATOR)`)
        foreach ($sqlFnClass::getParams() as $param) {
          $name = $param['name'] ?? '';
          if (!empty($param['api_default']['expr'])) {
            $fnArgs[] = $name . ' ' . implode(' ', $param['api_default']['expr']);
          }
          // Feed field as order by
          elseif ($name === 'ORDER BY') {
            $fnArgs[] = "ORDER BY `$key`";
          }
        }
        $select[] = $sqlFnClass::renderExpression(implode(' ', $fnArgs)) . " `$rawKey`";
      }
    }
    $query = 'SELECT ' . implode(', ', $select) . "\nFROM (" . $sql . ")\n`api_query`";
    $dao = \CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $tally = [];
    foreach ($columns as $col) {
      if (!empty($col['tally']['fn']) && !empty($col['key'])) {
        $key = $col['key'];
        $rawKey = str_replace(['.', ':'], '_', $key);
        $tally[$key] = $dao->$rawKey ?? '';
        // Format value according to data type of function/field
        if (strlen($tally[$key])) {
          $sqlExpression = SqlExpression::convert($col['tally']['fn'] . "($key)");
          $selectExpression = $this->getSelectExpression($key);
          $fieldName = $selectExpression['expr']->getFields()[0] ?? '';
          $dataType = $selectExpression['dataType'] ?? NULL;
          $sqlExpression->formatOutputValue($dataType, $tally, $key);
          $field = $queryObject->getField($fieldName);
          // Expand pseudoconstant list
          if ($sqlExpression->supportsExpansion && $field && strpos($fieldName, ':')) {
            $fieldOptions = FormattingUtil::getPseudoconstantList($field, $fieldName);
            $tally[$key] = FormattingUtil::replacePseudoconstant($fieldOptions, $tally[$key]);
          }
          else {
            $tally[$key] = $this->formatViewValue($key, $tally[$key], $tally, $dataType, $col['format'] ?? NULL);
          }
        }
      }
    }
    $data = $tally;
    // Handle any rewrite tokens
    foreach ($columns as $col) {
      if (!empty($col['tally']['rewrite'])) {
        $key = $col['key'];
        $tally[$key] = $this->rewrite($col['tally']['rewrite'], $data, 'raw');
      }
    }
    return $tally;
  }

  /**
   * Add editable information to the SearchDisplayRunResult object.
   *
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   *   The SearchDisplayRunResult object to add editable info to.
   */
  private function addEditableInfo(SearchDisplayRunResult $result): void {
    foreach ($this->display['settings']['columns'] as $column) {
      if (!empty($column['editable'])) {
        $result->editable[$column['key']] = $this->getEditableInfo($column['key']);
      }
    }
  }

  private function formatToolbar(): array {
    $toolbar = [];
    $settings = $this->display['settings'];
    // If no toolbar, early return
    if (empty($settings['toolbar']) && empty($settings['addButton']['path'])) {
      return [];
    }
    // There is no row data, but some values can be inferred from query filters
    $data = $this->getQueryData();
    // Support legacy 'addButton' setting
    if (empty($settings['toolbar']) && !empty($settings['addButton']['path'])) {
      $settings['toolbar'][] = $settings['addButton'] + ['style' => 'primary', 'target' => 'crm-popup'];
    }
    foreach ($settings['toolbar'] ?? [] as $button) {
      if (!$this->checkLinkConditions($button, $data)) {
        continue;
      }
      $button = $this->formatLink($button, $data, TRUE);
      if ($button) {
        $toolbar[] = $button;
      }
    }
    return $toolbar;
  }

}
