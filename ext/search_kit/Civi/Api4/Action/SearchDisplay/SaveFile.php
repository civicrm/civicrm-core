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

namespace Civi\Api4\Action\SearchDisplay;

/**
 * Runs the SearchDisplay and saves the output to a file and adds to the Document entity.
 *
 */
class SaveFile extends AbstractRunAction {
  use ResultDataTrait;
  /**
   * The name of the file for the report output that is saved to the file system.
   *
   * @var string
   * @required
   */
  protected $fileName;

  /**
   * The name of the report for the document being created.
   *
   * @var string
   * @required
   */
  protected $reportName;

  /**
   * If provided, the folder name will be used when saving the file.
   *
   * @var string
   */
  protected $folderName;

  /**
   * Whether to append the date to the file name.
   *
   * @var bool
   */
  protected $appendDate = FALSE;

  /**
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   * @throws \CRM_Core_Exception
   */
  protected function processResult(\Civi\Api4\Result\SearchDisplayRunResult $result) {
    $entityName = $this->savedSearch['api_entity'];
    $apiParams =& $this->_apiParams;
    $settings =& $this->display['settings'];
    $fileName = '';

    // Checking permissions for menu, link or button columns is costly, so remove them early
    foreach ($settings['columns'] as $index => $col) {
      // Remove buttons/menus and other column types that cannot be rendered in a spreadsheet
      if (empty($col['key'])) {
        unset($settings['columns'][$index]);
      }
      // Avoid wasting time processing links, editable and other non-printable items from spreadsheet
      else {
        \CRM_Utils_Array::remove($settings['columns'][$index], 'link', 'editable', 'icons', 'cssClass');
      }
    }
    // Reset indexes as some items may have been removed
    $settings['columns'] = array_values($settings['columns']);

    // Displays are only exportable if they have actions enabled
    if (empty($settings['actions'])) {
      \CRM_Utils_System::permissionDenied();
    }

    // Force limit if the display has no pager
    if (!isset($settings['pager']) && !empty($settings['limit'])) {
      $apiParams['limit'] = $settings['limit'];
    }
    $apiParams['orderBy'] = $this->getOrderByFromSort();
    $this->augmentSelectClause($apiParams, $settings);

    $this->applyFilters();

    $apiResult = civicrm_api4($entityName, 'get', $apiParams);

    $rows = $this->formatResult($apiResult);

    if ($this->appendDate) {
      $this->fileName .= date("_Ymd", time());
    }
    $this->fileName .= '.' . ('array' === $this->format ? 'json' : $this->format);

    // Download file contents.
    if ('array' === $this->format) {
      $content = $this->processData($this->display['label'], $this->display['settings']['columns'], $rows, $fileName);
      $searchDisplayContent = json_encode($content);
    }
    else {
      ob_start();
      $this->processData($this->display['label'], $this->display['settings']['columns'], $rows, $fileName);
      $searchDisplayContent = ob_get_contents();
      ob_end_clean();
    }

    if (!empty($searchDisplayContent)) {
      $config = \CRM_Core_Config::singleton();
      $directoryName = $config->customFileUploadDir;
      $fileName = \CRM_Utils_File::makeFileName($this->fileName);
      $info = pathinfo($fileName);
      // Regex in makeFileName blocks json due to check for js file names. Need to fix for saving as json.
      if ('unknown' === $info['extension'] && 'array' === $this->format) {
        $fileName = str_replace('unknown', 'json', $fileName);
        $fileName = str_replace('_json', '', $fileName);
      }

      // Append folder name if provided.
      if (!empty($this->folderName)) {
        $folderName = preg_replace('/[-\s]/', '_', preg_replace('/[^\w\s_]/', '', $this->folderName));
        // After replacement make sure we still have something for the folder name.
        if (!empty($folderName)) {
          $directoryName .= $folderName . '/';
        }
      }
      \CRM_Utils_File::createDir($directoryName);
      $fileParams = [
        'name' => $this->fileName,
        'mime_type' => $this->formats[$this->format]['mime'],
        'uri' => $directoryName . $fileName,
        'description' => $this->reportName,
        'upload_date' => date('Y-m-d H:i:s', time()),
      ];

      $fileDao = \CRM_Core_BAO_File::writeRecord($fileParams);
      $fileDao->find(TRUE);

      $entityFileDao = new \CRM_Core_DAO_EntityFile();
      $entityFile['entity_table'] = 'civicrm_saved_search';
      $entityFile['entity_id'] = $this->savedSearch['id'];
      $entityFileDao->copyValues($entityFile);
      $entityFileDao->file_id = $fileDao->id;
      $entityFileDao->save();

      $path = $directoryName . $fileName;
      file_put_contents($path, $searchDisplayContent);

      $result['file'] = $fileDao;
    }
  }

}
