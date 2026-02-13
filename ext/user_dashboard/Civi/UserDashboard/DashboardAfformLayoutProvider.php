<?php
namespace Civi\UserDashboard;

use Civi\Core\Event\GenericHookEvent;

/**
 * @service
 * @internal
 */
class DashboardAfformLayoutProvider extends \Civi\Core\Service\AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'civi.afform.get' => ['addDashboardLayout', -100],
    ];
  }

  public function addDashboardLayout(GenericHookEvent $event) {
    if (!$event->getLayout ||
      ($event->getTypes && !in_array('search', $event->getTypes)) ||
      (!empty($event->getNames['name']) && !in_array('afsearchUserDashboard', $event->getNames['name']))
    ) {
      return;
    }
    // Add displays for every SavedSearch tagged "UserDashboard"
    $searchDisplays = civicrm_api4('SearchDisplay', 'get', [
      'checkPermissions' => FALSE,
      'select' => ['name', 'label', 'type:name', 'saved_search_id.name'],
      'where' => [
        ['saved_search_id.is_current', '=', TRUE],
        ['saved_search_id.tags:name', 'IN', ['UserDashboard']],
      ],
      'orderBy' => ['name' => 'ASC'],
    ]);
    $afform = [
      'name' => 'afsearchUserDashboard',
      'layout' => '',
    ];
    foreach ($searchDisplays as $display) {
      $afform['layout'] .= <<<HTML
    <div af-fieldset="" class="af-container-style-pane" af-title="$display[label]">
      <{$display['type:name']} search-name="{$display['saved_search_id.name']}" display-name="$display[name]"></{$display['type:name']}>
    </div>
  HTML;
    }
    $event->afforms[] = $afform;
  }

}
