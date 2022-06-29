<?php

/**
 * Smarty block function to temporarily fill-in missing variables
 *
 * Example:
 *
 * ```
 * {crmDefault name="Anonymous" birthdate=$now}
 *   Hello {$name}! I bet you were born on {$birthdate}.
 * {/crmDefault}
 * ```
 *
 * @param array $params
 *   List
 * @param string $content
 *   Default content.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @param $repeat
 *
 * @return string
 */
function smarty_block_crmDefault($params, $content, &$smarty, &$repeat) {
  /** @var CRM_Core_Smarty $smarty */

  if ($repeat) {
    // open crmDefault
    $all = $smarty->get_template_vars();
    foreach (array_keys($params) as $key) {
      if (isset($all[$key])) {
        unset($params[$key]);
      }
    }
    $smarty->pushScope($params);
  }
  else {
    // close crmDefault
    $smarty->popScope();
  }

  return $content;
}
