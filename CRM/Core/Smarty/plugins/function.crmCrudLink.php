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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC
 */

/**
 * Dynamically construct a link based on an entity-type and entity-id.
 *
 * @param array $params
 *   Array with keys:
 *   - entity|table: string
 *   - id: int
 *   - action: string, 'view', 'update', 'delete', etc [default: view]
 *   - title: string [optionally override default title]
 * @param CRM_Core_Smarty $smarty
 *
 * @return string
 */
function smarty_function_crmCrudLink($params, &$smarty) {
  $link = CRM_Utils_System::createDefaultCrudLink([
    'action' => $params['action'] ?? 'view',
    'entity' => $params['entity'] ?? NULL,
    'entity_table' => $params['table'] ?? NULL,
    'id' => $params['id'],
  ]);

  if ($link) {
    return sprintf('<a href="%s">%s</a>',
      htmlspecialchars($link['url']),
      htmlspecialchars($params['title'] ?? $link['title'])
    );
  }
  else {
    return sprintf('[%s, %s]', $params['table'], $params['id']);
  }
}
