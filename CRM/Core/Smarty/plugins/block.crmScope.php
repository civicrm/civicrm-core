<?php

/**
 * Smarty block function to temporarily define variables.
 *
 * Example:
 *
 * @code
 * {tsScope x=1}
 *   Expect {$x}==1
 *   {tsScope x=2}
 *     Expect {$x}==2
 *   {/tsScope}
 *   Expect {$x}==1
 * {/tsScope}
 * @endcode
 *
 * @param array $params   must define 'name'
 * @param string $content    Default content
 * @param object $smarty  the Smarty object
 *
 * @return string
 */
function smarty_block_crmScope($params, $content, &$smarty, &$repeat) {
  // A list of variables/values to save temporarily
  static $backupFrames = array();

  if ($repeat) {
    // open crmScope
    $vars = $smarty->get_template_vars();
    $backupFrame = array();
    foreach ($params as $key => $value) {
      $backupFrame[$key] = isset($vars[$key]) ? $vars[$key] : NULL;
    }
    $backupFrames[] = $backupFrame;
    _smarty_block_crmScope_applyFrame($smarty, $params);
  }
  else {
    // close crmScope
    _smarty_block_crmScope_applyFrame($smarty, array_pop($backupFrames));
  }

  return $content;
}

function _smarty_block_crmScope_applyFrame(&$smarty, $frame) {
  foreach ($frame as $key => $value) {
    $smarty->assign($key, $value);
  }
}