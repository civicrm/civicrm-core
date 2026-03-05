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
      ->setReadonly(TRUE)
      ->addOutputFormatter([__CLASS__, 'formatFileUrl']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('icon', $spec->getEntity(), 'String');
    $field->setLabel(ts('Icon'))
      ->setTitle(ts('Filetype Icon'))
      ->setColumnName('mime_type')
      ->setDescription(ts('Icon associated with this filetype'))
      ->setReadonly(TRUE)
      ->setType('Extra')
      ->addOutputFormatter([__CLASS__, 'formatFileIcon']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('is_image', $spec->getEntity(), 'Boolean');
    $field->setLabel(ts('Is Image'))
      ->setTitle(ts('File is Image'))
      ->setColumnName('mime_type')
      ->setReadonly(TRUE)
      ->setDescription(ts('Is this a recognized image type file'))
      ->setType('Extra')
      ->setSqlRenderer([__CLASS__, 'renderFileIsImage']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('content', $spec->getEntity(), 'String');
    $field->setLabel(ts('Content'))
      ->setTitle(ts('Content'))
      ->setColumnName('id')
      ->setDescription(ts('Contents of file'))
      ->setType('Extra')
      ->addOutputFormatter([__CLASS__, 'formatFileContent']);
    $spec->addFieldSpec($field);

    if ($spec->getAction() === 'create') {
      $spec->getFieldByName('mime_type')->setRequired(TRUE);

      $field = new FieldSpec('move_file', $spec->getEntity(), 'String');
      $field->setLabel(ts('Move File'))
        ->setTitle(ts('Move File'))
        ->setDescription(ts('Name of temporary uploaded file'))
        ->setType('Extra');
      $spec->addFieldSpec($field);
    }
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

  public static function formatFileContent(&$content, $file) {
    // Virtual field fetches the id and expects it to be transformed into file contents by this function
    $fileId = $content;
    $uri = $file['uri'] ?? ($fileId ? \CRM_Core_DAO_File::getDbVal('is_public', $fileId) : NULL);
    if ($uri && is_string($uri)) {
      $isPublic = $file['is_public'] ?? \CRM_Core_DAO_File::getDbVal('is_public', $fileId);
      $settingName = $isPublic ? 'imageUploadDir' : 'customFileUploadDir';
      $dir = \CRM_Core_Config::singleton()->$settingName;
      $path = $dir . DIRECTORY_SEPARATOR . $uri;
      if (file_exists($path)) {
        $content = file_get_contents($path);
        return;
      }
    }
    $content = NULL;
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

  public static function renderFileIsImage(array $mimeTypeField, Api4SelectQuery $query): string {
    $uriField = $query->getFieldSibling($mimeTypeField, 'uri');
    return "IF(($mimeTypeField[sql_name] LIKE 'image/%') AND ($uriField[sql_name] NOT LIKE '%.unknown'), 1, 0)";
  }

  public static function formatFileUrl(&$value) {
    if ($value && is_numeric($value)) {
      $value = (string) \CRM_Core_BAO_File::getFileUrl($value);
    }
  }

  public static function formatFileIcon(&$value) {
    if (is_string($value)) {
      $value = \CRM_Utils_File::getIconFromMimeType($value);
    }
  }

  public function applies($entity, $action): bool {
    return $entity === 'File';
  }

}
