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
 * $Id$
 *
 */

/**
 * Dynamically construct a link based on an entity-type and entity-id.
 *
 * @param array $params
 *   Array with keys:
 *   - table: string
 *   - id: int
 *   - action: string, 'VIEW' or 'UPDATE' [default: VIEW]
 *   - title: string [optionally override default title]
 * @param CRM_Core_Smarty $smarty
 *
 * @return string
 */
function smarty_function_crmCrudLink($params, &$smarty) {
  if (empty($params['action'])) {
    $params['action'] = 'VIEW';
  }

  $link = CRM_Utils_System::createDefaultCrudLink([
    'action' => constant('CRM_Core_Action::' . $params['action']),
    'entity_table' => $params['table'],
    'entity_id' => $params['id'],
  ]);

  if ($link) {
    return sprintf('<a href="%s">%s</a>',
      htmlspecialchars($link['url']),
      htmlspecialchars(CRM_Utils_Array::value('title', $params, $link['title']))
    );
  }
  else {
    return sprintf('[%s, %s]', $params['table'], $params['id']);
  }
}
