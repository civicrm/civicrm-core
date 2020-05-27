<?php

/**
 * @param array $params
 * @param $text
 * @param $smarty
 *
 * @return string
 */
function smarty_block_ts($params, $text, &$smarty) {
  return ts($text, $params);
}
