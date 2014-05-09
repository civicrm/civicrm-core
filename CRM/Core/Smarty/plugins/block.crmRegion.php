<?php

/**
 * Smarty block function for defining content-regions which can be dynamically-altered
 *
 * @see CRM_Core_Regions
 *
 * @param array $params must define 'name'
 * @param string $content Default content
 * @param object $smarty the Smarty object
 *
 * @param $repeat
 *
 * @return string
 */
function smarty_block_crmRegion($params, $content, &$smarty, &$repeat)
{
  if ($repeat) return;
  require_once 'CRM/Core/Region.php';
  $region = CRM_Core_Region::instance($params['name'], FALSE);
  if ($region) {
    $result = $region->render($content, CRM_Utils_Array::value('allowCmsOverride', $params, TRUE));
    return $result;
  } else {
    return $content;
  }
}
