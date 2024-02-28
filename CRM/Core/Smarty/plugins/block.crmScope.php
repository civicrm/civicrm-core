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
 * @param \Smarty_Internal_Template $smarty
 *   The Smarty object.
 *
 * @param $repeat
 *
 * @return string
 */
function smarty_block_crmScope($params, $content, &$smarty, &$repeat) {
  /** @var CRM_Core_Smarty $smarty */

  if (!array_key_exists(__FUNCTION__, \Civi::$statics)) {
    \Civi::$statics[__FUNCTION__] = [];
  }
  $backupFrames = &\Civi::$statics[__FUNCTION__];
  if ($repeat) {
    $templateVars = $smarty->getTemplateVars();
    $backupFrame = [];
    foreach ($params as $name => $value) {
      $backupFrame[$name] = array_key_exists($name, $templateVars) ? $templateVars[$name] : NULL;
      $smarty->assign($name, $value);
    }
    $backupFrames[] = $backupFrame;
  }
  else {
    $backedUpVariables = array_pop($backupFrames);
    foreach ($backedUpVariables as $key => $value) {
      if (array_key_exists($key, $params)) {
        $smarty->assign($key, $value);
      }
      else {
        $smarty->clearAssign($key);
      }
    }
  }

  return $content;
}
