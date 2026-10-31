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

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Event\GenericHookEvent;

/**
 * @service
 * @internal
 */
class RelationshipAutocompleteProvider extends \Civi\Core\Service\AutoService {

  /**
   * Provide default SearchDisplay for Relationship autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  #[CiviEventListener(event: 'civi.search.defaultDisplay')]
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Relationship') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['contact_id_a.sort_name', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'relationship_type_id.label_a_b',
          'rewrite' => '[contact_id_a.sort_name] [relationship_type_id.label_a_b] [contact_id_b.sort_name]',
        ],
        [
          'type' => 'field',
          'key' => 'description',
          'rewrite' => '#[id] [description]',
          'empty_value' => '#[id]',
        ],
      ],
    ];
  }

}
