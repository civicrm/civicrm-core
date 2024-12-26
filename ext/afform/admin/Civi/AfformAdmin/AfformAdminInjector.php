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
   * @see CRM_Utils_Hook::alterAngular()
   */
  public static function preprocess($e) {
    $changeSet = \Civi\Angular\ChangeSet::create('afformAdmin')
      ->alterHtml(';\\.aff\\.html$;', function($doc, $path) {
        try {
          // Inject gear menu with edit links which will be shown if the user has permission
          $afform = Afform::get(FALSE)
            ->addWhere('module_name', '=', basename($path, '.aff.html'))
            ->addSelect('name', 'search_displays', 'title')
            ->execute()->single();
          // Create a link to edit the form, plus all embedded SavedSearches
          $links = [
            [
              'url' => \CRM_Utils_System::url('civicrm/admin/afform', NULL, FALSE, "/edit/{$afform['name']}", TRUE, FALSE, TRUE),
              'text' => E::ts('Edit %1 in FormBuilder', [1 => "<em>{$afform['title']}</em>"]),
              'icon' => 'fa-pencil',
              'permission' => 'administer afform',
            ],
          ];
          if ($afform['search_displays']) {
            $searchNames = [];
            foreach ($afform['search_displays'] as $searchAndDisplayName) {
              $searchNames[] = explode('.', $searchAndDisplayName)[0];
            }
            $savedSearches = SavedSearch::get(FALSE)
              ->addWhere('name', 'IN', $searchNames)
              ->addSelect('id', 'label', 'COUNT(permissioned_display.id) AS is_locked')
              ->addGroupBy('id')
              ->addJoin('SearchDisplay AS permissioned_display', 'LEFT', ['id', '=', 'permissioned_display.saved_search_id'], ['permissioned_display.acl_bypass', '=', TRUE])
              ->execute();
            foreach ($savedSearches as $savedSearch) {
              $links[] = [
                'url' => \CRM_Utils_System::url('civicrm/admin/search', NULL, FALSE, "/edit/{$savedSearch['id']}", TRUE, FALSE, TRUE),
                'text' => E::ts('Edit %1 in SearchKit', [1 => "<em>{$savedSearch['label']}</em>"]),
                'icon' => 'fa-search-plus',
                // Saved Searches with "bypass_permission" displays are locked to non-super-admins
                'permission' => $savedSearch['is_locked'] ? 'all CiviCRM permissions and ACLs' : 'administer search_kit',
              ];
            }
          }
          $linksMarkup = '';
          foreach ($links as $link) {
            $linksMarkup .= <<<HTML
              <li ng-if="checkPerm('{$link['permission']}')">
                <a href="{$link['url']}" target="_blank">
                  <i class="crm-i fa-fw {$link['icon']}"></i> {$link['text']}
                </a>
              </li>
            HTML;
          }
          $editMenu = <<<HTML
            <div class="pull-right btn-group af-admin-edit-form-link" ng-if="checkPerm('{$links[0]['permission']}')">
              <button type="button" class="btn dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="crm-i fa-gear"></i> <span class="caret"></span><span class="sr-only">{{:: ts('Configure')}}</span>
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
