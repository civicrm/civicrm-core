<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class FileGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  public function modifySpec(RequestSpec $spec): void {
    $field = new FieldSpec('file_name', $spec->getEntity(), 'String');
    $field->setLabel(ts('Filename'))
      ->setTitle(ts('Filename'))
      ->setColumnName('uri')
      ->setDescription(ts('Name of uploaded file'))
      ->setType('Extra')
      ->addOutputFormatter([__CLASS__, 'formatFileName']);
    // Uncomment this line and delete the function `formatFileName` when bumping min mysql version
    // ->setSqlRenderer([__CLASS__, 'renderFileName']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('url', $spec->getEntity(), 'String');
    $field->setLabel(ts('Download Url'))
      ->setTitle(ts('File Url'))
      ->setColumnName('id')
      ->setDescription(ts('Url at which this file can be downloaded'))
      ->setType('Extra')
      ->setSqlRenderer([__CLASS__, 'renderFileUrl'])
      ->addOutputFormatter([__CLASS__, 'formatFileUrl']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('icon', $spec->getEntity(), 'String');
    $field->setLabel(ts('Icon'))
      ->setTitle(ts('Filetype Icon'))
      ->setColumnName('mime_type')
      ->setDescription(ts('Icon associated with this filetype'))
      ->setType('Extra')
      ->addOutputFormatter([__CLASS__, 'formatFileIcon']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('is_image', $spec->getEntity(), 'Boolean');
    $field->setLabel(ts('Is Image'))
      ->setTitle(ts('File is Image'))
      ->setColumnName('mime_type')
      ->setDescription(ts('Is this a recognized image type file'))
      ->setType('Extra')
      ->setSqlRenderer([__CLASS__, 'renderFileIsImage']);
    $spec->addFieldSpec($field);
  }

  public static function formatFileName(&$uri) {
    if (version_compare(\CRM_Upgrade_Incremental_General::MIN_INSTALL_MYSQL_VER, 8, '>=')) {
      // Warning to make the unit test fail after we bump min sql version to one that supports REGEX_REPLACE
      \CRM_Core_Error::deprecatedWarning('Update FileGetSpecProvider to use renderFileName instead of formatFileName.');
    }
    if ($uri && is_string($uri)) {
      $uri = \CRM_Utils_File::cleanFileName($uri);
    }
  }

  /**
   * Unused until we bump min sql version to 8
   * @see formatFileName
   */
  public static function renderFileName(array $field): string {
    // MySql doesn't use preg delimiters
    $pattern = str_replace('/', "'", \CRM_Utils_File::HASH_REMOVAL_PATTERN);
    return "REGEX_REPLACE({$field['sql_name']}, $pattern, '.')";
  }

  public static function renderFileUrl(array $idField, Api4SelectQuery $query): string {
    // Getting a link to the file requires the `entity_id` from the `civicrm_entity_file` table
    // If the file was implicitly joined, the joined-from-entity has the id we want
    if ($idField['implicit_join']) {
      $joinField = $query->getField($idField['implicit_join']);
      $entityIdField = $query->getFieldSibling($joinField, 'id');
    }
    // If it's explicitly joined FROM another entity, get the id of the parent
    elseif ($idField['explicit_join']) {
      $parent = $query->getJoinParent($idField['explicit_join']);
      $joinPrefix = $parent ? "$parent." : '';
      $entityIdField = $query->getField($joinPrefix . 'id');
    }
    // If it's explicitly joined TO another entity, use the id of the other
    if (!isset($entityIdField)) {
      foreach ($query->getExplicitJoins() as $join) {
        if ($join['bridge'] === 'EntityFile') {
          $entityIdField = $query->getField($join['alias'] . '.id');
        }
      }
    }
    if (isset($entityIdField)) {
      return "CONCAT('civicrm/file?reset=1&id=', {$idField['sql_name']}, '&eid=', {$entityIdField['sql_name']})";
    }
    // Couldn't find an `entity_id` in the query so add a subquery instead.
    return "CONCAT('civicrm/file?reset=1&id=', {$idField['sql_name']}, '&eid=', (SELECT `entity_id` FROM `civicrm_entity_file` WHERE `file_id` = {$idField['sql_name']} LIMIT 1))";
  }

  public static function renderFileIsImage(array $mimeTypeField, Api4SelectQuery $query): string {
    $uriField = $query->getFieldSibling($mimeTypeField, 'uri');
    return "IF(($mimeTypeField[sql_name] LIKE 'image/%') AND ($uriField[sql_name] NOT LIKE '%.unknown'), 1, 0)";
  }

  public static function formatFileUrl(&$value) {
    $args = [];
    // renderFileUrl() will have formatted the output in-sql to `civicrm/file?reset=1&id=id&eid=entity_id`
    if (is_string($value) && str_contains($value, '?')) {
      parse_str(explode('?', $value)[1], $args);
      $value .= '&fcs=' . \CRM_Core_BAO_File::generateFileHash($args['eid'], $args['id']);
      $value = (string) \Civi::url('frontend://' . $value, 'a');
    }
  }

  public static function formatFileIcon(&$value) {
    if (is_string($value)) {
      $value = \CRM_Utils_File::getIconFromMimeType($value);
    }
  }

  public function applies($entity, $action): bool {
    return $entity === 'File' && $action === 'get';
  }

}
