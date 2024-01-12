<?php

/**
 * The content of an "{htxt}" block should not be evaluated unless
 * the active request is relevant. Otherwise, it will try to
 * evaluate unassigned variables.
 *
 * @param string $tpl_source
 * @return string
 */
function smarty_prefilter_htxtFilter($tpl_source) {
  if (strpos($tpl_source, '{htxt') === FALSE) {
    return $tpl_source;
  }

  $htxts = 0;
  $_htxts = 0;

  $result = preg_replace_callback_array([
    '/\{htxt id=(\"[-\w]+\")[ }]/' => function ($m) use (&$htxts) {
      $htxts++;
      return sprintf('{if $id == %s}%s', $m[1], $m[0]);
    },
    '/\{htxt id=(\'[-\w]+\')[ }]/' => function ($m) use (&$htxts) {
      $htxts++;
      return sprintf('{if $id == %s}%s', $m[1], $m[0]);
    },
    '/\{htxt id=(\$\w+)[ }]/' => function ($m) use (&$htxts) {
      $htxts++;
      return sprintf('{if $id == %s}%s', $m[1], $m[0]);
    },
    ';\{/htxt\};' => function($m) use (&$_htxts) {
      $_htxts++;
      return '{/htxt}{/if}';
    },
  ], $tpl_source);

  if ($htxts !== $_htxts) {
    throw new \RuntimeException(sprintf('Invalid {htxt} tag. Wrapped %d opening-tags and %d closing-tags.', $htxts, $_htxts));
  }

  return $result;
}
