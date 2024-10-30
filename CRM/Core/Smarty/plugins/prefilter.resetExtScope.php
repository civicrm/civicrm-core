<?php

/**
 * Wrap every Smarty template in a {crmScope} tag that sets the
 * variable "extensionKey" to blank.
 * @param string $tpl_source
 *
 * @return string
 */
function smarty_prefilter_resetExtScope($tpl_source) {
  return '{crmScope extensionKey=""}'
    . $tpl_source
    . '{/crmScope}';
}
