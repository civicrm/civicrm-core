<?php

/**
 * Wrap every Smarty template in a {crmScope} tag that sets the
 * variable "extensionKey" to blank.
 */
function smarty_prefilter_resetExtScope($tpl_source, &$smarty) {
  return
    '{crmScope extensionKey=""}'
    . $tpl_source
    .'{/crmScope}';
}