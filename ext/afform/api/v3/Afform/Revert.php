<?php
use CRM_Afform_ExtensionUtil as E;

/**
 * Afform.Revert API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_afform_revert_spec(&$spec) {
  require_once __DIR__ . DIRECTORY_SEPARATOR . 'Get.php';
  $getSpec = [];
  _civicrm_api3_afform_get_spec($getSpec);
  $spec['name'] = $getSpec['name'];
}

/**
 * Afform.Revert API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_revert_success
 * @see civicrm_api3_revert_error
 * @throws API_Exception
 */
function civicrm_api3_afform_revert($params) {
  $scanner = new CRM_Afform_AfformScanner();

  if (empty($params['name']) || !preg_match('/^[a-zA-Z][a-zA-Z0-9\-]*$/', $params['name'])) {
    throw new \API_Exception("Afform.revert: name is a mandatory field. It should use alphanumerics and dashes.");
  }
  $name = $params['name'];

  foreach ([CRM_Afform_AfformScanner::METADATA_FILE, 'layout.html'] as $file) {
    $metaPath = $scanner->createSiteLocalPath($name, $file);
    if (file_exists($metaPath)) {
      if (!@unlink($metaPath)) {
        throw new API_Exception("Failed to remove afform overrides in $file");
      }
    }
  }

  // We may have changed list of files covered by the cache.
  $scanner->clear();

  // FIXME if `server_route` changes, then flush the menu cache.
  // FIXME if asset-caching is enabled, then flush the asset cache.

  return civicrm_api3_create_success(1, $params, 'Afform', 'revert');
}
