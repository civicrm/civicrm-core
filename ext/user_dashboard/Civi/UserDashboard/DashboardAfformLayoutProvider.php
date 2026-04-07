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
      'civi.afform.get' => [
        ['addDashboardLayout', -100],
        ['addGroupSubscriptionLayout', -100],
      ],
    ];
  }

  /**
   * Provides markup for the dashboard layout.
   *
   * Dynamically retrieves search displays tagged as "UserDashboard" and includes them in the generated layout.
   */
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

  /**
   * Generates dynamic form layout for subscribing to public groups.
   */
  public function addGroupSubscriptionLayout(GenericHookEvent $event) {
    if (!$event->getLayout ||
      ($event->getTypes && !in_array('form', $event->getTypes)) ||
      (!empty($event->getNames['name']) && !in_array('afformUpdateGroupSubscriptions', $event->getNames['name']))
    ) {
      return;
    }
    $publicGroups = civicrm_api4('Group', 'get', [
      'checkPermissions' => FALSE,
      'select' => ['name'],
      'where' => [
        ['visibility', '=', 'Public Pages'],
        ['is_active', '=', TRUE],
        ['is_hidden', '=', FALSE],
      ],
    ])->column('name');
    $fieldsMarkup = array_reduce($publicGroups, function($fieldsMarkup, $groupName) {
      return ltrim($fieldsMarkup) . "    <af-field name=\"$groupName\" />\n";
    }, '');

    $layout = <<<HTML
<af-form ctrl="afform">
  <af-entity data="{}" type="Individual" name="Individual1" label="User" actions="{create: false, update: true}" security="FBAC" autofill="user" />
  <af-entity data="{contact_id: 'Individual1'}" actions="{create: true, update: true}" security="FBAC" type="GroupSubscription" name="GroupSubscription1" label="Groups" group-subscription="no-confirm" />
  <fieldset af-fieldset="GroupSubscription1" class="af-container" af-title="Select Groups">
    $fieldsMarkup
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()" ng-if="afform.showSubmitButton">Save</button>
</af-form>
HTML;

    $event->afforms[] = [
      'name' => 'afformUpdateGroupSubscriptions',
      'layout' => $layout,
    ];
  }

}
