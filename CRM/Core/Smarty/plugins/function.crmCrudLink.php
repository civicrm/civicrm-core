<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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

  $link = CRM_Utils_System::createDefaultCrudLink(array(
    'action' => constant('CRM_Core_Action::' . $params['action']),
    'entity_table' => $params['table'],
    'entity_id' => $params['id'],
  ));

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
