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
   * Get records - filtering by permission
   * if required
   */
  public function getRecords(): array {
    $reports = \Civi::cache('metadata')->get('civi.api4.reports');

    if (!is_array($reports)) {
      $reports = $this->gatherReports();
      \Civi::cache('metadata')->set('civi.api4.reports', $reports);
    }

    if ($this->checkPermissions) {
      $reports = array_filter($reports, fn ($report) => $this->checkPermission($report));
    }

    return $reports;
  }

  protected function gatherReports(): array {
    // fetch afform reports
    $reports = $this->getAfformReports();

    // Hook to provide custom reports
    $event = GenericHookEvent::create(['reports' => &$reports]);
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
    if (($report['permission_operator'] ?? NULL) === 'OR') {
      $report['permission'] = [$report['permission']];
    }
    return \CRM_Core_Permission::check($report['permission']);
  }

  protected function getAfformReports(): array {
    $afforms = (array) \Civi\Api4\Afform::get(FALSE)
      ->addSelect(
        'name', 'icon', 'title', 'description', 'permission_operator', 'permission',
        'modified_date', 'tags', 'search_displays', 'base_module', 'server_route'
      )
      ->addWhere('placement', 'CONTAINS', 'reports')
      ->execute();

    // get underlying saved searches in order to determine the Primary Entites for each Afform Report
    foreach ($afforms as $i => $afform) {
      $afforms[$i]['saved_searches'] = array_map(fn ($searchDisplay) => explode('.', $searchDisplay)[0], $afform['search_displays'] ?? []);
    }

    $allSavedSearchNames = array_merge(...array_column($afforms, 'saved_searches'));
    $savedSearchEntities = \Civi\Api4\SavedSearch::get(FALSE)
      ->addWhere('name', 'IN', $allSavedSearchNames)
      ->addSelect('name', 'api_entity')
      ->execute()
      ->indexBy('name')
      ->column('api_entity');

    $reports = array_map(function ($afform) use ($savedSearchEntities) {
      $primaryEntities = array_map(fn ($searchDisplay) => $savedSearchEntities[$searchDisplay], $afform['saved_searches'] ?? []);

      return [
        'name' => $afform['name'],
        'type' => 'afform',
        'title' => $afform['title'],
        'description' => $afform['description'],
        'extension' => $afform['base_module'],
        'primary_entities' => $primaryEntities,
        'icon' => $afform['icon'],
        'permission_operator' => $afform['permission_operator'],
        'permission' => $afform['permission'],
        // not stored for afforms?
        // 'created_date' => $afform['created_date'],
        'modified_date' => $afform['modified_date'],
        'created_id' => $afform['created_id'],
        'tags' => $afform['tags'],
        'view_url' => $afform['server_route'],
        'edit_url' => "civicrm/admin/afform#/edit/{$afform['name']}",
      ];
    }, $afforms);

    return $reports;
  }

}
