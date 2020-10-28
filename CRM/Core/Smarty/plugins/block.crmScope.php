<?php

/**
 * Smarty block function to temporarily define variables.
 *
 * Example:
 *
 * ```
 * {tsScope x=1}
 *   Expect {$x}==1
 *   {tsScope x=2}
 *     Expect {$x}==2
 *   {/tsScope}
 *   Expect {$x}==1
 * {/tsScope}
 * ```
 *
 * @param array $params
 *   Must define 'name'.
 * @param string $content
 *   Default content.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @param $repeat
 *
 * @return string
 */
function smarty_block_crmScope($params, $content, &$smarty, &$repeat) {
  /** @var CRM_Core_Smarty $smarty */

  if ($repeat) {
    // open crmScope
    $smarty->pushScope($params);
  }
  else {
    // close crmScope
    $smarty->popScope();
  }

  return $content;
}
