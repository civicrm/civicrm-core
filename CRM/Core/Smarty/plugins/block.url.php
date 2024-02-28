<?php

/**
 * Generate a URL. This is thin wrapper for the Civi::url() helper.
 *
 * @see Civi::url()
 *
 * Ex: Generate a backend URL.
 *     {url}backend://civicrm/admin?reset=1{/url}
 *
 * Ex: Generate a backend URL. Assign it to a Smarty variable.
 *     {url assign=tmpVar}backend://civicrm/admin?reset=1{/url}
 *
 * Ex: Generate a backend URL. Set optional flags: (t)ext, (s)sl, (a)bsolute.
 *     {url flags=tsa}backend://civicrm/admin?reset=1{/url}
 *
 * Ex: Generate a URL in the current (active) routing scheme. Add named variables. (Values are escaped).
 *     {url verb="Eat" target="Apples and bananas"}//civicrm/fruit?method=[verb]&data=[target]{/url}
 *
 * Ex: As above, but use numerical variables.
 *     {url 1="Eat" 2="Apples and bananas"}//civicrm/fruit?method=[1]&data=[2]{/url}
 *
 * Ex: Generate a URL. Add some pre-escaped variables using Smarty {$foo}.
 *     {assign var=myEscapedAction value="Eat"}
 *     {assign var=myEscapedData value="Apples+and+bananas"}
 *     {url}//civicrm/fruit?method={$myEscapedAction}&data={$myEscapedData}{/url}
 *
 * @param array $params
 *   The following parameters have specific meanings:
 *   - "assign" (string) - Assign output to a Smarty variable
 *   - "flags" (string) - List of options, as per `Civi::url(...$flags)`
 *   All other parameters will be passed-through as variables for the URL.
 * @param string $text
 *   Contents of block.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 * @param bool $repeat
 *   Repeat is true for the opening tag, false for the closing tag
 *
 * @return string
 */
function smarty_block_url($params, $text, &$smarty, &$repeat) {
  if ($repeat || $text === NULL) {
    return NULL;
  }

  $flags = 'h' . ($params['flags'] ?? '');
  $assign = $params['assign'] ?? NULL;
  unset($params['flags'], $params['assign']);

  $url = (string) Civi::url($text, $flags)->addVars($params);

  // This could be neat, but see discussion in CRM_Core_Smarty_plugins_UrlTest for why it's currently off.
  // $url->setVarsCallback([$smarty, 'getTemplateVars']);

  if ($assign !== NULL) {
    $smarty->assign([$assign => $url]);
    return '';
  }
  else {
    return $url;
  }
}
