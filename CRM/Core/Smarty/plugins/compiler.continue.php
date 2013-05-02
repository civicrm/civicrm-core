<?php

/**
 * Skip remaining logic in the current iteration of a loop.
 */
function smarty_compiler_continue($contents, &$smarty){
  return 'continue;';
}
