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

namespace Civi\Api4\Action;

use Civi\API\Request;
use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;

/**
 * Get action links for the $ENTITY entity.
 *
 * Action links are paths to forms for e.g. view, edit, or delete actions.
 * @method string getEntityTitle()
 * @method $this setEntityTitle(string $entityTitle)
 * @method bool getExpandMultiple()
 * @method $this setExpandMultiple(bool $expandMultiple)
 */
class GetLinks extends BasicGetAction {
  use \Civi\Api4\Generic\Traits\GetSetValueTrait;

  /**
   * Links will be returned appropriate to the specified values (e.g. ['contact_type' => 'Individual'])
   *
   * @var array
   */
  protected $values = [];

  /**
   * @var bool|string
   */
  protected $entityTitle = TRUE;

  /**
   * Should multiple links e.g. to create different subtypes all be returned?
   * @var bool
   */
  protected bool $expandMultiple = FALSE;

  public function _run(Result $result) {
    parent::_run($result);
    // Do expensive processing *after* parent has filtered down the result per the WHERE clause
    if ($this->getSelect() !== ['row_count']) {
      $links = $result->getArrayCopy();
      $this->replaceTokens($links);
      $this->filterByPermission($links);
      $result->exchangeArray(array_values($links));
    }
  }

  protected function getRecords(): array {
    $entityName = $this->getEntityName();
    $locale = $GLOBALS['tsLocale'] ?? '';
    $cacheKey = "api4.$entityName.links.$locale";
    $links = \Civi::cache('metadata')->get($cacheKey);
    if (!isset($links)) {
      $links = $this->fetchLinks();
      \Civi::cache('metadata')->set($cacheKey, $links);
    }
    return $links;
  }

  /**
   * Get the full set of links for an entity
   *
   * This function sits behind a cache so the heavy processing happens here.
   * @return array
   */
  private function fetchLinks(): array {
    $links = [];
    $apiActionMap = [
      'add' => 'create',
      'delete' => 'delete',
      'view' => 'get',
      'browse' => 'get',
      'export' => 'get',
      'preview' => 'get',
    ];
    $entityName = $this->getEntityName();
    $paths = CoreUtil::getInfoItem($entityName, 'paths') ?? [];
    foreach ($paths as $actionName => $path) {
      $actionKey = \CRM_Core_Action::mapItem($actionName);
      $link = [
        'ui_action' => $actionName,
        'api_action' => $apiActionMap[$actionName] ?? 'update',
        'api_values' => NULL,
        'entity' => $entityName,
        'path' => $path,
        'text' => \CRM_Core_Action::getTitle($actionKey, '%1'),
        'icon' => \CRM_Core_Action::getIcon($actionKey),
        'weight' => (int) \CRM_Core_Action::getWeight($actionKey),
        'target' => 'crm-popup',
        'conditions' => [],
      ];
      $links[] = $link;
    }
    // Allow entity to override with extra links
    $event = GenericHookEvent::create(['entity' => $entityName, 'links' => &$links]);
    \Civi::dispatcher()->dispatch('civi.api4.getLinks', $event);
    // Fill in optional keys from hook links
    foreach ($links as $index => $link) {
      $links[$index] += [
        'api_values' => NULL,
        'entity' => $entityName,
        'weight' => 0,
        'target' => 'crm-popup',
        'conditions' => [],
      ];
    }
    usort($links, ['CRM_Utils_Sort', 'cmpFunc']);
    return $links;
  }

  /**
   * Replace [square_bracket] tokens in the path and `%1` placeholders in the text.
   * @param array $links
   * @return void
   */
  private function replaceTokens(array &$links): void {
    // Text was translated with `%1` placeholders preserved so it could be cached
    // Now we'll replace `%1` placeholders with the entityTitle, unless FALSE
    $entityTitle = $this->entityTitle === TRUE ? CoreUtil::getInfoItem($this->getEntityName(), 'title') : $this->entityTitle;
    foreach ($links as $index => &$link) {
      // Swap placeholders with $entityTitle (TRUE means use default title)
      if ($entityTitle !== FALSE && !empty($link['text'])) {
        $link['text'] = str_replace('%1', $entityTitle, $link['text']);
      }
      // Swap path tokens with values
      if ($this->getValues() && !empty($link['path'])) {
        $tokens = \CRM_Utils_String::getSquareTokens($link['path']);
        foreach ($tokens as $token) {
          $value = $this->getValue($token['content']);
          // A '?' in the token makes it optional
          if (!isset($value) && $token['qualifier'] === '?') {
            $value = '';
          }
          if (isset($value)) {
            $link['path'] = str_replace($token['token'], $value, $link['path']);
          }
          // If $values was supplied, remove links with missing required tokens
          // This hides invalid links from SearchKit e.g. `civicrm/group/edit?id=null`
          // Note: skip if expandMultiple is true to give hooks the chance to fill in missing tokens
          elseif (!$this->expandMultiple) {
            unset($links[$index]);
            break;
          }
        }
      }
    }
  }

  private function filterByPermission(array &$links): void {
    if (!$this->getCheckPermissions()) {
      return;
    }
    foreach ($links as $index => $link) {
      if (!$this->isActionAllowed($link['entity'], $link['api_action'])) {
        unset($links[$index]);
        continue;
      }
      $values = array_merge($this->values, (array) $link['api_values']);
      // These 2 lines are the heart of the `checkAccess` api action.
      // Calling this directly is more performant than going through the api wrapper
      $apiRequest = Request::create($link['entity'], $link['api_action'], ['version' => 4, 'checkPermissions' => TRUE]);
      if (!CoreUtil::checkAccessRecord($apiRequest, $values)) {
        unset($links[$index]);
      }
    }
  }

  private function isActionAllowed(string $entityName, string $actionName): bool {
    $allowedApiActions = $this->getAllowedEntityActions($entityName);
    return in_array($actionName, $allowedApiActions, TRUE);
  }

  private function getAllowedEntityActions(string $entityName): array {
    $uid = \CRM_Core_Session::getLoggedInContactID();
    if (!isset(\Civi::$statics[__CLASS__]['actions'][$entityName][$uid])) {
      \Civi::$statics[__CLASS__]['actions'][$entityName][$uid] = civicrm_api4($entityName, 'getActions', ['checkPermissions' => TRUE])->column('name');
    }
    return \Civi::$statics[__CLASS__]['actions'][$entityName][$uid];
  }

  public function fields() {
    return [
      [
        'name' => 'ui_action',
        'description' => 'Action corresponding to CRM_Core_Action',
      ],
      [
        'name' => 'api_action',
        'description' => 'Action corresponding to API.getActions',
      ],
      [
        'name' => 'api_values',
        'data_type' => 'Array',
        'description' => 'API values associated with this action (e.g. for a sub_type)',
      ],
      [
        'name' => 'entity',
        'description' => 'API entity name',
      ],
      [
        'name' => 'path',
        'description' => 'Link path',
      ],
      [
        'name' => 'text',
        'description' => 'Link text',
      ],
      [
        'name' => 'icon',
        'description' => 'Link icon css class',
      ],
      [
        'name' => 'weight',
        'data_type' => 'Integer',
        'description' => 'Sort order',
      ],
      [
        'name' => 'target',
        'description' => 'HTML target attribute',
      ],
      [
        'name' => 'conditions',
        'data_type' => 'Array',
        'description' => 'Conditions for displaying link',
      ],
    ];
  }

}
