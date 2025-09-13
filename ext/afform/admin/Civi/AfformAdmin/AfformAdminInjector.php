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

namespace Civi\AfformAdmin;

use Civi\Api4\Afform;
use Civi\Api4\SavedSearch;
use Civi\Core\Service\AutoSubscriber;
use CRM_Afform_ExtensionUtil as E;

/**
 * @package Civi\AfformAdmin
 */
class AfformAdminInjector extends AutoSubscriber {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_alterAngular' => 'preprocess',
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   *
   * This injects static html to render a small admin-only menu at the top corner of each form.
   * Permissions are checked client-side.
   * @see afCoreDirective.checkLinkPerm
   */
  public static function preprocess($e) {
    $changeSet = \Civi\Angular\ChangeSet::create('afformAdmin')
      ->alterHtml(';\\.aff\\.html$;', function($doc, $path) {
        try {
          // Inject gear menu with edit links which will be shown if the user has permission
          $afform = Afform::get(FALSE)
            ->addWhere('module_name', '=', basename($path, '.aff.html'))
            ->addSelect('name', 'search_displays', 'title', 'created_id', 'type', 'create_submission')
            ->execute()->single();
          // Create a link to edit the form, plus all embedded SavedSearches
          $links = [
            [
              'url' => \CRM_Utils_System::url('civicrm/admin/afform', NULL, FALSE, "/edit/{$afform['name']}", TRUE, FALSE, TRUE),
              'text' => E::ts('Edit %1 in FormBuilder', [1 => "<em>{$afform['title']}</em>"]),
              'icon' => 'fa-pencil',
              'permission' => 'manage own afform',
              'created_id' => $afform['created_id'] ?: 'null',
            ],
          ];
          if ($afform['type'] === 'form' && $afform['create_submission']) {
            $links[] = [
              'url' => \CRM_Utils_System::url('civicrm/admin/afform/submissions', NULL, FALSE, "/?name={$afform['name']}", TRUE, FALSE, TRUE),
              'text' => E::ts('View Submissions'),
              'icon' => 'fa-list',
              'permission' => 'manage own afform',
              'created_id' => $afform['created_id'] ?: 'null',
            ];
          }
          if ($afform['search_displays']) {
            $searchNames = [];
            foreach ($afform['search_displays'] as $searchAndDisplayName) {
              $searchNames[] = explode('.', $searchAndDisplayName)[0];
            }
            $savedSearches = SavedSearch::get(FALSE)
              ->addWhere('name', 'IN', $searchNames)
              ->addSelect('id', 'label', 'created_id', 'COUNT(permissioned_display.id) AS is_locked')
              ->addGroupBy('id')
              ->addJoin('SearchDisplay AS permissioned_display', 'LEFT', ['id', '=', 'permissioned_display.saved_search_id'], ['permissioned_display.acl_bypass', '=', TRUE])
              ->execute();
            foreach ($savedSearches as $savedSearch) {
              $links[] = [
                'url' => \CRM_Utils_System::url('civicrm/admin/search', NULL, FALSE, "/edit/{$savedSearch['id']}", TRUE, FALSE, TRUE),
                'text' => E::ts('Edit %1 in SearchKit', [1 => "<em>{$savedSearch['label']}</em>"]),
                'icon' => 'fa-search-plus',
                // Saved Searches with "bypass_permission" displays are locked to non-super-admins
                'permission' => $savedSearch['is_locked'] ? 'all CiviCRM permissions and ACLs' : 'manage own search_kit',
                'created_id' => $savedSearch['created_id'] ?: 'null',
              ];
            }
          }
          $linksMarkup = '';
          foreach ($links as $link) {
            $linksMarkup .= <<<HTML
              <li ng-if="checkLinkPerm('{$link['permission']}', {$link['created_id']})">
                <a href="{$link['url']}" target="_blank">
                  <i class="crm-i fa-fw {$link['icon']}" role="img" aria-hidden="true"></i> {$link['text']}
                </a>
              </li>
            HTML;
          }
          $editMenu = <<<HTML
            <div class="pull-right btn-group af-admin-edit-form-link" ng-if="checkLinkPerm('{$links[0]['permission']}', {$links[0]['created_id']})">
              <button type="button" class="btn dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="crm-i fa-gear" role="img" aria-hidden="true"></i> <span class="caret"></span><span class="sr-only">{{:: ts('Configure')}}</span>
              </button>
              <ul class="dropdown-menu">$linksMarkup</ul>
            </div>
          HTML;
          // Append link to end of afform markup so it has the highest z-index and is clickable.
          // afCore.css will control placement at the top of the form.
          pq($doc)->append($editMenu);
        }
        catch (\Exception $e) {
        }
      });
    $e->angular->add($changeSet);
  }

}
