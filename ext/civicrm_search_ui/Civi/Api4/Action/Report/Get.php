<?php

namespace Civi\Api4\Action\Report;

use Civi\Core\Event\GenericHookEvent;
use CRM_CivicrmSearchUi_ExtensionUtil as E;

/**
 * @inheritDoc
 * @package Civi\Api4\Action\Report
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  /**
   * Get the records
   *
   * TODO: we do some optimised gets based on itemsToGet and isFieldSelected
   *
   * But it may be better to just fetch the whole list and chuck it in a cache?
   */
  public function getRecords() {
    // check if we only need specific types of form
    $getTypes = $this->_itemsToGet('type');
    if (is_null($getTypes)) {
      // null means all active types
      $getTypes = \Civi\Api4\OptionValue::get(FALSE)
        ->addWhere('option_group_id:name', '=', 'report_type')
        ->addWhere('is_active', '=', TRUE)
        ->execute()
        ->column('value');
    }

    // check if we only need specific names
    $getNames = $this->_itemsToGet('name');
    $getIsActive = $this->_itemsToGet('is_active');

    $fetchTags = $this->_isFieldSelected('tags');

    $reports = [];

    // fetch afform reports
    if (\in_array('afform', $getTypes)) {
      $reports = array_merge($reports, $this->getAfformReports($getNames, $getIsActive));
    }

    // Hook to provide custom reports
    $hookParams = [
      'reports' => &$reports,
      'getNames' => $getNames,
      'getTypes' => $getTypes,
      'getIsActive' => $getIsActive,
      'fetchTags' => $fetchTags,
    ];
    $event = GenericHookEvent::create($hookParams);
    \Civi::dispatcher()->dispatch('civi.api4.report.get', $event);

    foreach ($reports as &$report) {
      $report['id'] = "{$report['type']}_{$report['name']}";
    }

    return $reports;
  }

  /**
   * Assert that a form is authorized.
   *
   * @return bool
   */
  protected function checkPermission($report) {
    if (!$this->checkPermissions) {
      return TRUE;
    }
    if (($report['permission_operator'] ?? NULL) === 'OR') {
      $report['permission'] = [$report['permission']];
    }
    return \CRM_Core_Permission::check($report['permission']);
  }

  protected function getAfformReports(?array $getNames, ?array $getIsActive): array {
    if (!is_null($getIsActive) && !in_array(TRUE, $getIsActive)) {
      // afform reports are always active so dont fetch any
      return [];
    }

    $fetch = \Civi\Api4\Afform::get(FALSE)
      ->addSelect(
        'name', 'icon', 'title', 'description', 'permission_operator', 'permission',
        'modified_date', 'tags', 'search_displays', 'base_module', 'server_route'
      )
      ->addWhere('placement', 'CONTAINS', 'reports');

    if (!is_null($getNames)) {
      $fetch->addWhere('name', 'IN', $getNames);
    }

    $afforms = (array) $fetch->execute();

    foreach ($afforms as $i => $afform) {
      $afforms[$i]['saved_searches'] = array_map(fn ($searchDisplay) => explode('.', $searchDisplay)[0], $afform['search_displays'] ?? []);
    }

    $savedSearchNames = array_merge(...array_column($afforms, 'saved_searches'));
    $savedSearchEntities = \Civi\Api4\SavedSearch::get(FALSE)
      ->addWhere('name', 'IN', $savedSearchNames)
      ->addSelect('name', 'api_entity')
      ->execute()
      ->indexBy('name')
      ->column('api_entity');

    $reports = [];

    foreach ($afforms as $afform) {
      $primaryEntities = array_map(fn ($searchDisplay) => $savedSearchEntities[$searchDisplay], $afform['saved_searches'] ?? []);

      $reports[] = [
        'name' => $afform['name'],
        'type' => 'afform',
        'title' => $afform['title'],
        'description' => $afform['description'],
        'extension' => $afform['base_module'],
        'primary_entities' => $primaryEntities,
        'icon' => $afform['icon'],
        'permission_operator' => $afform['permission_operator'],
        'permission' => $afform['permission'],
        // is this stored for afforms?
        'created_date' => NULL,
        'modified_date' => $afform['modified_date'],
        'created_id' => $afform['created_id'],
        // populated below if required
        'tags' => $afform['tags'],
        'view_url' => $afform['server_route'],
        'edit_url' => "civicrm/admin/afform#/edit/{$afform['name']}",
      ];
    }

    return $reports;
  }

}
