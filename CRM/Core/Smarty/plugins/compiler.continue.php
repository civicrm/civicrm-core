<?php

/**
 * Skip remaining logic in the current iteration of a loop.
 * @param $contents
 * @param $smarty
 * @return string
 */
function smarty_compiler_continue($contents, &$smarty) {
  return 'continue;';
}
