<?php
use CRM_Afform_ExtensionUtil as E;

/**
 * Afform.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_afform_create_spec(&$spec) {
  require_once __DIR__ . DIRECTORY_SEPARATOR . 'Get.php';
  _civicrm_api3_afform_get_spec($spec);
}

/**
 * Afform.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_afform_create($params) {
  $scanner = new CRM_Afform_AfformScanner();
  $converter = new CRM_Afform_ArrayHtml();

  if (empty($params['name']) || !preg_match('/^[a-zA-Z][a-zA-Z0-9\-]*$/', $params['name'])) {
    throw new \API_Exception("Afform.create: name is a mandatory field. It should use alphanumerics and dashes.");
  }
  $name = $params['name'];

  // FIXME validate all field data.
  $updates = _afform_fields_filter($params);

  // Create or update layout.html.
  if (isset($updates['layout'])) {
    $layoutPath = $scanner->createSiteLocalPath($name, 'layout.html');
    // printf("[%s] Update layout %s\n", $name, $layoutPath);
    CRM_Utils_File::createDir(dirname($layoutPath));
    file_put_contents($layoutPath, $converter->convertArrayToHtml($updates['layout']));
    // FIXME check for writability then success. Report errors.
  }

  // Create or update meta.json.
  $orig = civicrm_api('afform', 'get', ['name' => $name, 'sequential' => 1]);
  if (is_array($orig['values'][0])) {
    $meta = _afform_fields_filter(array_merge($orig['values'][0], $updates));
  }
  else {
    $meta = $updates;
  }
  unset($meta['definition']);
  unset($meta['name']);
  if (!empty($meta)) {
    $metaPath = $scanner->createSiteLocalPath($name, CRM_Afform_AfformScanner::METADATA_FILE);
    // printf("[%s] Update meta %s: %s\n", $name, $metaPath, print_R(['updates'=>$updates, 'meta'=>$meta], 1));
    CRM_Utils_File::createDir(dirname($metaPath));
    file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
    // FIXME check for writability then success. Report errors.
  }

  return civicrm_api3_create_success($updates, $params, 'Afform', 'create');
}
