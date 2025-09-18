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
 *
 */

/**
 * Display a banned icon to flag privacy preferences
 *
 * @param $params
 *   - field: the applicable privacy field
 *     (one of CRM_Core_SelectValues::privacy() or `on_hold`)
 *   - condition: if present and falsey, return empty
 *
 * @param $smarty
 *
 * @return string
 */
function smarty_function_privacyFlag($params, &$smarty) {
  if (array_key_exists('condition', $params) && !$params['condition']) {
    return '';
  }
  $icons = [
    'do_not_phone' => 'fa-phone',
    'do_not_email' => 'fa-paper-plane',
    'do_not_mail' => 'fa-envelope',
    'do_not_sms' => 'fa-comments-o',
    'do_not_trade' => 'fa-exchange',
    'is_opt_out' => 'fa-paper-plane-o',
  ];
  $titles = CRM_Core_SelectValues::privacy();
  $field = $params['field'] ?? 'do_not_mail';
  if ($field === 'on_hold') {
    $text = ts('Email on hold - generally due to bouncing.', ['escape' => 'htmlattribute']);
    return <<<HEREDOC
<span class="privacy-flag email-hold" title="$text">
  <i class="crm-i fa-exclamation-triangle fa-lg font-red" role="img" aria-hidden="true"></i>
</span>
<span class="sr-only">$text</span>
HEREDOC;
  }
  $class = str_replace('_', '-', $field);
  $text = ts('Privacy flag: %1', [1 => $titles[$field], 'escape' => 'htmlattribute']);
  return <<<HEREDOC
<span class="fa-stack privacy-flag $class" title="$text">
  <i class="crm-i {$icons[$field]} fa-stack-1x" role="img" aria-hidden="true"></i>
  <i class="crm-i fa-ban fa-stack-2x font-red" role="img" aria-hidden="true"></i>
</span>
<span class="sr-only">$text</span>
HEREDOC;
}
