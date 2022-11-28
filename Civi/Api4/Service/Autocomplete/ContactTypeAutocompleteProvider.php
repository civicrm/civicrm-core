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

namespace Civi\Api4\Service\Autocomplete;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;

/**
 * @service
 * @internal
 */
class ContactTypeAutocompleteProvider extends \Civi\Core\Service\AutoService implements HookInterface {

  /**
   * Provide default SearchDisplay for ContactType autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'ContactType') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['label', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'label',
          'icons' => [
            ['field' => 'icon'],
          ],
        ],
        [
          'type' => 'field',
          'key' => 'description',
          'rewrite' => "{if '[description]'}[description]{elseif '[parent_id]'}" . ts('Subtype of %1', [1 => '[parent_id:label]']) . "{/if}",
        ],
      ],
    ];
  }

}
