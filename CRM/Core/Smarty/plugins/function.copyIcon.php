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
 * @author Andie Hunt, AGH Strategies
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
  $text = ts('Click to copy %1 from row one to all rows.', [1 => $params['title'], 'escape' => 'htmlattribute']);
  return <<<HEREDOC
<span class="action-icon" fname="{$params['name']}" aria-label="$text" title="$text" role="button">
  <i class="crm-i fa-clone" role="img" aria-hidden="true"></i>
</span>
HEREDOC;
}
