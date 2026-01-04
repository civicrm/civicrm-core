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

namespace Civi\API\Subscriber;

use Civi\Api4\Generic\AbstractAction;
use Civi\Core\Service\AutoSubscriber;
use CRM_Core_Config;

/**
 * Wraps the OptionValue api (v3 and v4) to provide backward compatibility
 * with the SiteEmailAddress entity (formerly the `from_email_address` option group).
 */
class SiteEmailLegacyOptionValueAdapter extends AutoSubscriber {

  private static $apiIds = [];

  const CONCAT_LABEL = 'CONCAT(\'"\', display_name, \'" <\', email, \'>\')';

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => [
        ['onApiPrepare', 2000],
      ],
      'civi.api.respond' => [
        ['onApiRespond', 2000],
      ],
    ];
  }

  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['entity'] !== 'OptionValue') {
      return;
    }
    if ($apiRequest['version'] == 3 && $this->isApi3FromEmailOptionValueRequest($apiRequest)) {
      $this->preprocessApi3SiteEmailOptionValues($apiRequest);
      $event->setApiRequest($apiRequest);
    }
    elseif ($apiRequest['version'] == 4 && $this->isApi4FromEmailOptionValueRequest($apiRequest)) {
      $this->preprocessApi4SiteEmailOptionValues($apiRequest);
    }
  }

  public function onApiRespond(\Civi\API\Event\RespondEvent $event) {
    $apiRequest = $event->getApiRequest();
    // If ID was previously stashed by preprocessApi3SiteEmailOptionValues
    if (isset(self::$apiIds[$apiRequest['id']])) {
      unset(self::$apiIds[$apiRequest['id']]);
      $apiResponse = $event->getResponse();
      $this->postprocessApi3SiteEmailOptionValues($apiRequest, $apiResponse);
      $event->setResponse($apiResponse);
    }
  }

  /**
   * @param array $apiRequest
   * @return bool
   */
  private function isApi3FromEmailOptionValueRequest($apiRequest): bool {
    // In api3, 'create' also means 'update'; this supports both.
    // This also effectively supports `getsingle` and `getvalue` because they wrap `get`.
    // However, 'delete' is not supported because the params don't include option_group_id so we have no way to target them.
    $supportedActions = ['create', 'get'];
    if (!in_array($apiRequest['action'], $supportedActions, TRUE)) {
      return FALSE;
    }
    $apiParams = $apiRequest['params'];
    if (($apiParams['option_group_id'] ?? NULL) === 'from_email_address') {
      return TRUE;
    }
    // Update actions are tricky because they probably won't pass option_group_id in the params
    // So check if the label looks like a well-formed email, and if so, see if the id matches a record in civicrm_site_email_address
    if ($apiRequest['action'] === 'create' && !empty($apiParams['id']) && !empty($apiParams['label'])) {
      $pattern = '/^"[^"]+" <[^\s@<>]+@[^\s@<>]+\.[^\s@<>]+>$/';
      if (preg_match($pattern, $apiParams['label'])) {
        return (bool) \CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_site_email_address WHERE id = %1", [
          1 => [$apiParams['id'], 'Integer'],
        ]);
      }
    }
    return FALSE;
  }

  private function preprocessApi3SiteEmailOptionValues(&$apiRequest) {
    // Register request id for postprocessing
    self::$apiIds[$apiRequest['id']] = TRUE;
    // Modify internal variables of the api request... don't try this at home
    $apiRequest['function'] = str_replace('option_value', 'site_email_address', $apiRequest['function']);
    // Switch request to use SiteEmailAddress entity
    $apiRequest['entity'] = 'SiteEmailAddress';
    $apiParams = &$apiRequest['params'];
    unset($apiParams['option_group_id']);
    if (!empty($apiParams['return'])) {
      if (!is_array($apiParams['return'])) {
        $apiParams['return'] = array_map('trim', explode(',', $apiParams['return']));
      }
      $apiParams['return'][] = 'display_name';
      $apiParams['return'][] = 'email';
    }
    if (!empty($apiParams['value']) && $apiRequest['action'] === 'get') {
      $apiParams['id'] = $apiParams['value'];
    }
    if (isset($apiParams['name']) && !isset($apiParams['label'])) {
      $apiParams['label'] = $apiParams['name'];
    }
    unset($apiParams['option_group_id'], $apiParams['name'], $apiParams['value'], $apiParams['weight']);
    if (isset($apiParams['label'])) {
      $apiParams['email'] = \CRM_Utils_Mail::pluckEmailFromHeader(rtrim($apiParams['label']));
      $apiParams['display_name'] = trim(explode('"', $apiParams['label'])[1]);
    }
    if ($apiRequest['action'] === 'create' && !isset($apiParams['id']) && !isset($apiParams['domain_id'])) {
      $apiParams['domain_id'] = CRM_Core_Config::domainID();
    }
    // Weight no longer exists, sort by display_name instead
    if (isset($apiParams['options']['sort'])) {
      if (is_string($apiParams['options']['sort'])) {
        $apiParams['options']['sort'] = str_replace('weight', 'display_name', $apiParams['options']['sort']);
      }
      elseif (is_array($apiParams['options']['sort'])) {
        $apiParams['options']['sort'] = array_map(fn($sort) => str_replace('weight', 'display_name', $sort), $apiParams['options']['sort']);
      }
    }
    // Convert chains
    foreach (array_keys($apiParams) as $key) {
      if (str_starts_with(strtolower($key), 'api.option_value.') || str_starts_with(strtolower($key), 'api.optionvalue.')) {
        $action = explode('.', $key)[2];
        $apiParams["api.site_email_address.$action"] = $apiParams[$key];
        unset($apiParams[$key]);
      }
    }
  }

  private function postprocessApi3SiteEmailOptionValues(array $apiRequest, array &$apiResult) {
    $addWeights = FALSE;
    if (isset($apiResult['values']) && is_array($apiResult['values'])) {
      foreach ($apiResult['values'] as &$value) {
        if (isset($value['id'])) {
          $value['value'] = $value['id'];
        }
        if (isset($value['display_name']) && isset($value['email'])) {
          $addWeights = TRUE;
          $value['label'] = $value['name'] = \CRM_Utils_Mail::formatFromAddress($value);
        }
      }
    }
    // 'weight' no longer exists so return the sort order based on 'display_name'
    if ($addWeights) {
      $sorted = array_map(fn($value) => $value['display_name'], $apiResult['values']);
      sort($sorted);
      $weight = 1;
      foreach (array_keys($sorted) as $key) {
        $apiResult['values'][$key]['weight'] = (string) ($weight++);
      }
    }
  }

  /**
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   * @return bool
   */
  private function isApi4FromEmailOptionValueRequest($apiRequest): bool {
    $supportedActions = ['create', 'get', 'update', 'delete'];
    $action = $apiRequest->getActionName();
    if (!in_array($action, $supportedActions, TRUE)) {
      return FALSE;
    }
    if ($apiRequest->reflect()->hasProperty('where')) {
      foreach ($apiRequest->getWhere() as $clause) {
        if ($clause[0] === 'option_group_id:name' || $clause[0] === 'option_group_id.name') {
          if ($clause[1] === '=' && $clause[2] === 'from_email_address') {
            return TRUE;
          }
        }
      }
    }
    if ($action === 'create' || $action === 'update') {
      $values = $apiRequest->getValues();
      if (($values['option_group_id:name'] ?? $values['option_group_id.name'] ?? NULL) === 'from_email_address') {
        return TRUE;
      }
    }
    return FALSE;
  }

  private function preprocessApi4SiteEmailOptionValues(AbstractAction $apiRequest) {
    $action = $apiRequest->getActionName();
    // Modify internal variables of the api request... don't try this at home
    $reflection = $apiRequest->reflect();
    $entityNameProperty = $reflection->getProperty('_entityName');
    $entityNameProperty->setAccessible(TRUE);
    $entityNameProperty->setValue($apiRequest, 'SiteEmailAddress');
    $allowedFields = array_keys(\Civi::entity('SiteEmailAddress')->getFields());
    if ($reflection->hasProperty('where')) {
      $where = $apiRequest->getWhere();
      foreach ($where as $index => $clause) {
        if ($clause[0] === 'option_group_id:name' || $clause[0] === 'option_group_id.name') {
          unset($where[$index]);
        }
        if ($clause[0] === 'value') {
          $where[$index][0] = 'id';
        }
        if ($clause[0] === 'label' || $clause[0] === 'name') {
          $where[$index][0] = self::CONCAT_LABEL;
        }
        if ($clause[0] === 'weight') {
          unset($where[$index]);
        }
      }
      $apiRequest->setWhere(array_values($where));
    }
    if ($action === 'get') {
      $select = $apiRequest->getSelect() ?: ['*'];
      // Remove all non-supported fields from select clause
      $select = array_intersect($select, array_merge($allowedFields, ['*']));
      $select[] = self::CONCAT_LABEL . ' AS label';
      $select[] = self::CONCAT_LABEL . ' AS name';
      $select[] = '(id) AS value';
      // weight no longer exists but probably better to return "something"
      $select[] = '1 AS weight';
      $apiRequest->setSelect($select);
      $orderBy = $apiRequest->getOrderBy();
      if (isset($orderBy['weight'])) {
        \CRM_Utils_Array::crmReplaceKey($orderBy, 'weight', 'label');
      }
      $orderBy = array_intersect_key($orderBy, array_flip(array_merge($allowedFields, ['label', 'name', 'value'])));
      $apiRequest->setOrderBy($orderBy);
    }
    if (in_array($action, ['create', 'update'], TRUE)) {
      $values = $apiRequest->getValues();
      if (isset($values['value']) && $action === 'update' && !isset($values['id'])) {
        $values['id'] = $values['value'];
      }
      $values['label'] ??= $values['name'] ?? NULL;
      if (isset($values['label'])) {
        $values['email'] = \CRM_Utils_Mail::pluckEmailFromHeader(rtrim($values['label']));
        $values['display_name'] = trim(explode('"', $values['label'])[1]);
      }
      unset($values['label'], $values['value'], $values['option_group_id:name'], $values['option_group_id.name']);
      $apiRequest->setValues($values);
    }
  }

}
