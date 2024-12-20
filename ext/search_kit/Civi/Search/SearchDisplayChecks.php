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

namespace Civi\Search;

use Civi\Api4\SearchDisplay;
use Civi\Core\Service\AutoService;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.check.search_display
 */
class SearchDisplayChecks extends AutoService implements EventSubscriberInterface {

  const PREFIX = 'search_display:';

  public static function getSubscribedEvents() {
    return ['&hook_civicrm_check' => 'runChecks'];
  }

  /**
   * @see \CRM_Utils_Hook::check()
   */
  public function runChecks(&$messages, $statusNames = [], $includeDisabled = FALSE) {
    if (!empty($statusNames) && !preg_grep('/^' . preg_quote(static::PREFIX, '/') . '/', $statusNames)) {
      return;
    }

    $displays = SearchDisplay::get(FALSE)
      ->addSelect('id', 'label', 'type', 'settings', 'saved_search_id', 'saved_search_id.api_entity', 'saved_search_id.api_params')
      ->execute();
    foreach ($displays as $display) {
      $issues = $this->validateSearchDisplay($display['saved_search_id.api_entity'], $display['saved_search_id.api_params'], $display['settings']);
      if (!empty($issues)) {
        $message = new \CRM_Utils_Check_Message(
          static::PREFIX . $display['id'],
          '<p>' . ts('This record appears to have invalid settings:') . '</p>' .
          '<ul>' . implode("", array_map(fn($i) => "<li>$i</li>", $issues)) . '</ul>',
          ts('Search Display "%1" (#%2)', [1 => $display['label'], 2 => $display['id']]),
          LogLevel::WARNING,
          'fa-search'
        );
        if (!empty($display['saved_search_id'])) {
          $message->addAction(ts('Edit search'), FALSE, 'href', [
            'url' => \Civi::url('backend://civicrm/admin/search#/edit/')->addFragment($display['saved_search_id']),
          ]);
        }

        $messages[] = $message;
      }
    }
  }

  public function validateSearchDisplay(string $entity, array $params, array $settings): array {
    $issues = [];

    $fields = civicrm_api4($entity, 'getFields', ['checkPermissions' => FALSE])->indexBy('name')->getArrayCopy();
    foreach ($settings['columns'] as $column) {
      if ($column['type'] !== 'field') {
        continue;
      }

      // TODO: A better way to validate subfields. For now, we'll just check the first layer. Much better than nothing.
      [$key] = preg_split('/[:\.]/', $column['key']);
      if (!isset($fields[$key])) {
        $issues[] = ts('Unrecognized field "<code>%1</code>"', [1 => htmlentities($column['key'])]);
      }
    }

    return $issues;
  }

}
