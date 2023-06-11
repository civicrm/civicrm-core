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
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provide autocomplete searches tailored to the CiviMail recipients widget
 * @service
 * @internal
 */
class MailingRecipientsAutocompleteProvider extends AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.search.autocompleteDefault' => ['mailingAutocompleteDefaultSearch', 50],
      'civi.search.defaultDisplay' => ['mailingAutocompleteDefaultDisplay', 50],
    ];
  }

  /**
   * Construct a special-purpose SavedSearch for the Mailing.recipients autocomplete
   *
   * It uses a UNION to combine groups with mailings
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @return void
   */
  public function mailingAutocompleteDefaultSearch(GenericHookEvent $e) {
    if (
      !is_array($e->savedSearch) ||
      $e->savedSearch['api_entity'] !== 'Group' ||
      ($e->fieldName !== 'Mailing.recipients_include' && $e->fieldName !== 'Mailing.recipients_exclude') ||
      strpos($e->formName ?? '', 'crmMailing.') !== 0
    ) {
      return;
    }
    $mailingId = (int) (explode('.', $e->formName)[1] ?? 0);
    // Mode is "include" or "exclude"
    $mode = explode('_', $e->fieldName)[1];
    $e->savedSearch['api_params'] = [
      'version' => 4,
      'select' => [
        'CONCAT("groups_", id) AS key',
        'IF(is_hidden, "' . ts('Search Results') . '", title) AS label',
        'description',
        '"group" AS entity',
        'created_id.display_name',
        'IF(saved_search_id, "' . ts('Smart Group') . '", IF(children, "' . ts('Parent Group') . '", "' . ts('Group') . '")) AS type',
        'IF(saved_search_id, "fa-lightbulb-o", "fa-group") AS icon',
        'DATE(saved_search_id.created_date) AS date',
        '(' . ($mode === 'include' ? 'mailing_group.id' : 'NULL') . ' IS NOT NULL) AS locked',
      ],
      'join' => [],
      'where' => [
        ['group_type:name', 'CONTAINS', 'Mailing List'],
        ['OR', [['saved_search_id.expires_date', 'IS NULL'], ['saved_search_id.expires_date', '>', 'NOW()', TRUE]]],
        ['OR', [['is_hidden', '=', FALSE], [($mode === 'include' ? 'mailing_group.id' : '(NULL)'), 'IS NOT NULL']]],
      ],
      'union' => [
        [
          'Mailing', 'get', [
            'select' => [
              'CONCAT("mailings_", id) AS key',
              'name',
              'subject',
              '"mailing" AS entity',
              'created_id.display_name',
              'IF(is_archived, "' . ts('Archived Mailing') . '", IF(is_completed, "' . ts('Sent Mailing') . '", "' . ts('Unsent Mailing') . '")) AS type',
              'IF(is_archived, "fa-archive", IF(is_completed, "fa-envelope", "fa-file-o")) AS icon',
              'COALESCE(DATE(scheduled_date), DATE(created_date))',
              '0',
            ],
            'where' => [
              ['id', '!=', $mailingId],
              ['domain_id', '=', 'current_domain'],
            ],
          ],
        ],
      ],
    ];
    // Join is only needed for "include" mode to fetch the hidden search group if any
    if ($mode === 'include') {
      $e->savedSearch['api_params']['join'][] = ['MailingGroup AS mailing_group', 'LEFT',
        ['id', '=', 'mailing_group.entity_id'],
        ['mailing_group.group_type', '=', '"Include"'],
        ['mailing_group.entity_table', '=', '"civicrm_group"'],
        ['is_hidden', '=', TRUE],
        ['mailing_group.mailing_id', '=', $mailingId],
      ];
    }
  }

  /**
   * Construct a SearchDisplay for the above SavedSearch
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @return void
   */
  public function mailingAutocompleteDefaultDisplay(GenericHookEvent $e) {
    if (
      // Early return if display has already been overridden
      $e->display['settings'] ||
      // Check display type
      $e->display['type'] !== 'autocomplete'
      // Check entity
      || $e->savedSearch['api_entity'] !== 'Group' ||
      // Check that this is the correct SavedSearch
      empty($e->savedSearch['api_params']['union']) ||
      $e->savedSearch['api_params']['select'][0] !== 'CONCAT("groups_", id) AS key'
    ) {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['entity', 'ASC'],
        ['label', 'ASC'],
      ],
      'keyField' => 'key',
      'extra' => [
        'locked' => 'locked',
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'label',
          'empty_value' => '(' . ts('no name') . ')',
          'icons' => [
            [
              'field' => 'icon',
              'side' => 'left',
            ],
          ],
        ],
        [
          'type' => 'field',
          'key' => 'date',
          'rewrite' => '[type] ' . ts('by %1', [1 => '[created_id.display_name]']) . ' ([date])',
          'empty_value' => '[type] ' . ts('by %1', [1 => '[created_id.display_name]']),
        ],
        [
          'type' => 'field',
          'key' => 'description',
        ],
      ],
    ];
  }

}
