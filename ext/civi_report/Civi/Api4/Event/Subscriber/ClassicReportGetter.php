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
namespace Civi\Api4\Event\Subscriber;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;

/**
 * Expose classic ReportInstance reports to civi.api4.report.get
 */
class ClassicReportGetter extends AutoSubscriber {

  public static function getSubscribedEvents() {
    return [
      'civi.api4.report.get' => 'onGetReports',
    ];
  }

  public function onGetReports(GenericHookEvent $e) {
    $reportInstances = (array) \Civi\Api4\ReportInstance::get(FALSE)
      ->addSelect(
        'id', 'title', 'report_id', 'name', 'description', 'permission',
        'is_active', 'report_id:name', 'report_id:icon', 'base_module',
        'local_modified_date', 'created_id'
      )
      ->execute();

    $reports = [];

    foreach ($reportInstances as $reportInstance) {
      // could this be useful for deriving meta?
      // $class = $reportInstance['report_id:name'];

      $extension = $reportInstance['base_module'] ?? 'civi_report';
      $entity = $this->getPrimaryEntityFromTemplateId($reportInstance['report_id']);
      $primaryEntities = $entity ? [$entity] : [];

      $reports[$reportInstance['id']] = [
        'name' => $reportInstance['name'] ?: "report_instance_{$reportInstance['id']}",
        'type' => 'classic',
        'title' => $reportInstance['title'],
        'description' => $reportInstance['description'],
        'extension' => $extension,
        'primary_entities' => $primaryEntities,
        'icon' => $reportInstance['report_id:icon'],
        // report instances can only have a single permission
        'permission_operator' => 'AND',
        'permission' => [$reportInstance['permission']],
        // not stored for ReportInstances
        'created_date' => NULL,
        'modified_date' => $reportInstance['local_modified_date'],
        'created_id' => $reportInstance['created_id'],
        'view_url' => "civicrm/report/instance/{$reportInstance['id']}?force=1&reset=1",
        'edit_url' => NULL,
        // populated below if required
        'tags' => [],
      ];
    }

    $tags = \Civi\Api4\EntityTag::get(FALSE)
      ->addSelect('entity_id', 'tag_id.name')
      ->addWhere('entity_table', '=', 'civicrm_report_instance')
      ->addWhere('entity_id', 'IN', array_keys($reports))
      ->execute();

    foreach ($tags as $tag) {
      $reports[$tag['entity_id']]['tags'][] = $tag['tag_id.name'];
    }

    // pass back to the hook
    $e->reports = array_merge($e->reports, array_values($reports));
  }

  private function getPrimaryEntityFromTemplateId(string $templateId) {
    // works most of the time
    $entity = \ucfirst(explode('/', $templateId)[0]);

    $exceptionMap = [
      'ActivitySummary' => 'Activity',
      'Contribute' => 'Contribution',
    ];

    return $exceptionMap[$entity] ?? $entity;
  }

}
