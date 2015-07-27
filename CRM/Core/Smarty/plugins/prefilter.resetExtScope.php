<?php

/**
 * Wrap every Smarty template in a {crmScope} tag that sets the
 * variable "extensionKey" to blank.
 * @param $tpl_source
 * @param $smarty
 * @return string
 */
function smarty_prefilter_resetExtScope($tpl_source, &$smarty) {
  return
    '{crmScope extensionKey=""}'
    . $tpl_source
    . '{/crmScope}';
}
