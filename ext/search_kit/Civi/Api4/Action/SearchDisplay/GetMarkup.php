<?php

namespace Civi\Api4\Action\SearchDisplay;

use CRM_Search_ExtensionUtil as E;

/**
 * Generate markup for embedding a SearchDisplay in a html document
 *
 * @method $this setFilters(array $filters)
 * @method array getFilters()
 * @package Civi\Api4\Action\SearchDisplay
 * @since 6.12
 */
class GetMarkup extends \Civi\Api4\Generic\BasicBatchAction {

  /**
   * Filter values to be passed to the search display e.g. `['first_name' => 'Sue']`
   * @var array
   */
  protected $filters = [];

  /**
   * @param array $display
   * @return array{module: string, markup: string}
   */
  protected function doTask($display): array {
    // E.g. "crmSearchDisplayTable"
    $module = \CRM_Utils_String::convertStringToCamel($display['type:name'], FALSE);

    // Note: Should be kept in-sync with \Civi\Search\AfformSearchMetadataInjector::preprocess
    $markup = sprintf('<%s search="%s" display="%s" api-entity="%s" settings="%s" filters="%s"></%s>',
      $display['type:name'],
      htmlspecialchars(\CRM_Utils_JS::encode($display['saved_search_id.name']), ENT_COMPAT),
      htmlspecialchars(\CRM_Utils_JS::encode($display['name']), ENT_COMPAT),
      htmlspecialchars($display['saved_search_id.api_entity'], ENT_COMPAT),
      htmlspecialchars(\CRM_Utils_JS::encode($display['settings']), ENT_COMPAT),
      htmlspecialchars(\CRM_Utils_JS::encode($this->filters), ENT_COMPAT),
      $display['type:name']
    );

    return [
      'module' => $module,
      'markup' => $markup,
    ];
  }

  /**
   * @return string[]
   */
  protected function getSelect() {
    return ['name', 'type:name', 'settings', 'saved_search_id.name', 'saved_search_id.api_entity'];
  }

  /**
   * Add a filter value to be passed to the search display.
   *
   * @param string $key
   * @param mixed $value
   * @return $this
   */
  public function addFilter(string $key, $value) {
    $this->filters[$key] = $value;
    return $this;
  }

}
