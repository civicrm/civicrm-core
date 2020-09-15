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
 * @author Andrew Hunt, AGH Strategies
 */

/**
 * Display a copy icon that copies the first row's values down.
 *
 * @param $params
 *   - name: the field name
 *   - title: the field title
 *
 * @param $smarty
 *
 * @return string
 */
function smarty_function_copyIcon($params, &$smarty) {
  $text = ts('Click to copy %1 from row one to all rows.', [1 => $params['title']]);
  return <<<HEREDOC
<i class="crm-i fa-clone action-icon" fname="{$params['name']}" title="$text"><span class="sr-only">$text</span></i>
HEREDOC;
}
