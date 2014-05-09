<?php

/**
 * Maximum time an extdir page should be cached (seconds).
 *
 * Note: This overrides the system default.
 */
// define('EXTDIR_MAX_AGE', 30*60);

/**
 * (Page Callback)
 *
 * Get list of available extensions
 */
function extdir_pages_listing($filterExpr) {
  $filters = _extdir_pages_filters_parse($filterExpr);
  list($query, $helper) = _extdir_pages_filters_query($filters);
  $helper->innerJoinFieldAPI('extension', 'field_extension_fq_name');
  $query->fields('field_extension_fq_name', array('field_extension_fq_name_value'));
  $query->distinct();

  // deliver
  print '<html><body>';
  print theme('extdir_list', array(
    'extensions' => $query->execute(),
  ));
  print '</body></html>';
  return NULL;
}

/**
 * (Page Callback)
 *
 * Get the metadata for one extension
 */
function extdir_pages_xml($filterExpr, $xmlFile) {
  $filters = _extdir_pages_filters_parse($filterExpr);
  if (!preg_match('/^(.*)\.xml$/', $xmlFile, $matches)) {
    return MENU_NOT_FOUND;
  }
  $filters['field_extension_fq_name_value'] = $matches[1];

  list($query, $helper) = _extdir_pages_filters_query($filters);
  $query->fields('field_extension_nr_crm', array('entity_id', 'field_extension_nr_crm_nid'));
  $helper->innerJoinFieldAPI('release', 'field_extension_release_vc');
  $query->orderBy('field_extension_release_vc_value', 'DESC');
  // $query->orderBy('extrelease.title', 'DESC'); // FIXME dictionary sort != version sort
  $query->range(0,1);

  $count = 0;
  foreach ($query->execute() as $row) {
    $extension = node_load($row->field_extension_nr_crm_nid);
    $release = node_load($row->entity_id);
    if (!file_exists($release->field_extension_release_xml['und'][0]['uri'])) {
      throw new Exception('Failed to read extension XML: file not found: ');
    }
    $xml = simplexml_load_file($release->field_extension_release_xml['und'][0]['uri']);
    _extdir_pages_xml_cleanup($extension, $release, $xml);
    $count++;
  }

  if ($count == 0) {
    return MENU_NOT_FOUND;
  } else {
//    dpm(array('filters' => $filters, 'query' => $query->__toString(), 'extension' => $extension, 'release' => $release, 'xml' => $xml->asXML()));
//    return '';

    // deliver
    $output = $xml->asXML();
    drupal_add_http_header('Content-Type', 'text/xml; utf-8');
    print $output;
    return NULL;

    /*
    $modified = max($extension->changed, $extension->created, $release->changed, $release->created);
    drupal_add_http_header('Content-Type', 'text/xml; utf-8');
    drupal_add_http_header('Expires', gmdate(DATE_RFC1123, REQUEST_TIME + EXTDIR_MAX_AGE));
    drupal_add_http_header('Last-Modified', gmdate(DATE_RFC1123, $modified));
    drupal_add_http_header('Cache-Control', sprintf('max-age=%d, public', EXTDIR_MAX_AGE));
    drupal_add_http_header('ETag', md5($output));
    drupal_page_header();
    print $output;
    exit();
    */
  }
}

/**
 * Modify a raw uploaded XML document, mixing in details
 * from the extension and release.
 *
 * @param $extension stdClass, node
 * @param $release stdClass, node
 * @param $xml SimpleXMLElement, <extension>
 */
function _extdir_pages_xml_cleanup($extension, $release, $xml) {
  // lots of overlap between node and XML; we'll side with the node
  if (!empty($extension->field_extension_fq_name['und'][0]['value'])) {
    $xml['key'] = (string) $extension->field_extension_fq_name['und'][0]['value'];
  }
  if (!empty($release->field_extension_release_url['und'][0]['url'])) {
    $xml->downloadUrl = (string) $release->field_extension_release_url['und'][0]['url'];
  }
  if (!empty($release->field_extension_release_status['und'][0]['value'])) {
    $xml->develStage = (string) $release->field_extension_release_status['und'][0]['value'];
  }
  //if (!empty($release->field_extension_release_ver['und'][0]['value'])) {
  //  $xml->version = (string) $release->field_extension_release_ver['und'][0]['value'];
  //}
  //The title field gets munged, so don't use it
  //if (!empty($release->title)) {
  //  $xml->version = (string) $release->title;
  //}
  $xml->releaseDate = date('Y-m-d', $release->created);
}

/**
 * Parse the filter expression
 *
 * @param $filterExpr string, eg 'ver=4.2.1|cms=Drupal6'
 * @return array, keys:
 *  - field_extension_cms_tid
 *  - field_extension_release_status_value
 *  - field_extension_release_civicrm_tid
 *  - field_extension_ready_value
 */
function _extdir_pages_filters_parse($filterExpr) {
  $args = array();
  $keypairs = explode('|', $filterExpr);
  foreach ($keypairs as $keypair) {
    list ($key, $value) = explode('=', $keypair);
    $args[$key] = $value;
  }

  // defaults
  $filters = array(
    'field_extension_cms_tid' => 127, // Native extension
    'field_extension_release_status_value' => 'stable',
    'field_extension_ready_value' => 'ready',
  );

  if (array_key_exists('ver', $args)) {
    $versionParts = explode('.', $args['ver']);
    $termName = sprintf('CiviCRM %d.%d', $versionParts[0], $versionParts[1]);
    $terms = taxonomy_get_term_by_name($termName, 'extension_civicrm_compatibility');
    if (empty($terms)) {
      $filters['field_extension_release_civicrm_tid'] = -1;
    } else {
      foreach ($terms as $term) {
        $filters['field_extension_release_civicrm_tid'] = $term->tid;
      }
    }
  }

  if (array_key_exists('status', $args)) {
    if (empty($args['status'])) {
      unset($filters['field_extension_release_status_value']);
    } else {
      // TODO stable, stable+beta, stable+beta+alpha
      $filters['field_extension_release_status_value'] = $args['status'];
    }
  }

  return $filters;
}

/**
 * This is utterly ridiculous. Welcome to 2012.
 *
 * EntityFieldQuery doesn't appear to support JOIN, DISTINCT, etc,
 * so we reinvent a slightly more usable wheel.
 *
 * @param $filters array with keys:
 *  - field_extension_cms_tid
 *  - field_extension_release_status_value
 *  - field_extension_release_civicrm_tid
 * @return array(SelectQueryInterface,ExtdirQueryHelper)
 */
function _extdir_pages_filters_query($filters) {
  $query = db_select('node', 'extrelease');
  // two logical entities in this query
  $helper = ExtdirQueryHelper::create($query, array(
    'release' => 'extrelease.nid',
    'extension' => 'field_extension_nr_crm_nid',
  ));
  $helper->innerJoinFieldAPI('release', 'field_extension_nr_crm');

  if (isset($filters['field_extension_cms_tid'])) {
    $helper->innerJoinFieldAPI('extension', 'field_extension_cms');
    $query->condition('field_extension_cms_tid', $filters['field_extension_cms_tid']);
  }

  // FIXME allow releases which are compatible with multiple versions of Civi
  if (isset($filters['field_extension_release_civicrm_tid'])) {
    $helper->innerJoinFieldAPI('release', 'field_extension_release_civicrm');
    $query->condition('field_extension_release_civicrm_tid', $filters['field_extension_release_civicrm_tid']);
  }

  if (isset($filters['field_extension_release_status_value'])) {
    $helper->innerJoinFieldAPI('release', 'field_extension_release_status');
    $query->condition('field_extension_release_status_value', $filters['field_extension_release_status_value']);
  }

  if (isset($filters['field_release_extension_ready_value'])) {
    $helper->innerJoinFieldAPI('release', 'field_release_extension_ready');
    $query->condition('field_release_extension_ready_value', $filters['field_release_extension_ready_value']);
  }

  if (isset($filters['field_extension_ready_value'])) {
    $helper->innerJoinFieldAPI('extension', 'field_extension_ready');
    $query->condition('field_extension_ready_value', $filters['field_extension_ready_value']);
  }

  if (isset($filters['field_extension_fq_name_value'])) {
    $helper->innerJoinFieldAPI('extension', 'field_extension_fq_name');
    $query->condition('field_extension_fq_name_value', $filters['field_extension_fq_name_value']);
  }

  return array($query, $helper);
}

/**
 * Helper for mixing in DB columns generated by
 * Field API's SQL storage engine -- which works
 * with multiple entities, JOINs, GROUP BYs, ec
 */
class ExtdirQueryHelper {
  /**
   * @param $query SelectQueryInterface
   * @parm $entityAliases array($niceName => $sqlIdExpression) list of entities
   *   which are (mentally) part of the query -- and the SQL expressions which
   *   identify them
   * @param $entityAliases
   *
   * @return \ExtdirQueryHelper
   */
  function create($query, $entityAliases) {
    return new ExtdirQueryHelper($query, $entityAliases);
  }
  function __construct($query, $entityAliases) {
    $this->query = $query;
    $this->entityAliases = $entityAliases;
  }
  function innerJoinFieldAPI($entityAlias, $joinToField, $joinToAlias = NULL, $joinToColumn = 'entity_id') {
    return $this->joinFieldAPI('INNER', $entityAlias, $joinToField, $joinToAlias, $joinToColumn);
  }
  function joinFieldAPI($type, $entityAlias, $joinToField, $joinToAlias = NULL, $joinToColumn = 'entity_id') {
    if ($joinToAlias === NULL) {
      $joinToAlias = $joinToField;
    }

    $fieldInfo = field_info_field($joinToField);
    // assumes that field uses one table
    foreach ($fieldInfo['storage']['details']['sql']['FIELD_LOAD_CURRENT'] as $table => $columns) {
      $condition = $this->entityAliases[$entityAlias]." = ${joinToAlias}.${joinToColumn}";
      $this->query->addJoin($type, $table, $joinToAlias, $condition);
    }
    return $this;
  }
}

