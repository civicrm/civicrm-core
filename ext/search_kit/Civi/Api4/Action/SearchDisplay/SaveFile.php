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
   * Requested file format.
   *
   * 'array' will save the output as a json file.
   * 'csv', etc. will save the file based on file type.
   *
   * @var string
   * @required
   * @options array,csv,xlsx,ods,pdf
   */
  protected $format = 'array';

  private $formats = [
    'array' => [
      'mime' => 'application/json',
    ],
    'xlsx' => [
      'writer' => 'Xlsx',
      'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
    'ods' => [
      'writer' => 'Ods',
      'mime' => 'application/vnd.oasis.opendocument.spreadsheet',
    ],
    'pdf' => [
      'writer' => 'Dompdf',
      'mime' => 'application/pdf',
    ],
  ];

  /**
   *
   * Note that the result class is that of the annotation below, not the h
   * in the method (which must match the parent class)
   *
   * @var \Civi\Api4\Generic\Result $result
   */
  protected function processResult(\Civi\Api4\Result\SearchDisplayRunResult $result) {
    $sk_download = \Civi\Api4\SearchDisplay::download(FALSE)
      ->setSavedSearch($this->savedSearch)
      ->setFormat($this->format)
      ->setDownloadAsFile(FALSE);

    if (!empty($this->seed)) {
      $sk_download->setSeed($this->seed);
    }

    if (!empty($this->afform)) {
      $sk_download->setAfform($this->afform);
    }

    if (!empty($this->sort)) {
      $sk_download->setSort($this->sort);
    }

    if (!empty($this->filters)) {
      $sk_download->setFilters($this->filters);
    }

    if (!empty($this->filterLabels)) {
      $sk_download->setFilterLabels($this->filterLabels);
    }

    $entity_table = 'civicrm_saved_search';
    $entity_id = $this->savedSearch['id'];
    if (!empty($this->display['id'])) {
      $entity_id = $this->display['id'];
      $entity_table = 'civicrm_search_display';
      $sk_download->setDisplay($this->display);
    }

    if ($this->appendDate) {
      $this->fileName .= date("_Ymd", time());
    }
    $this->fileName .= '.' . ('array' === $this->format ? 'json' : $this->format);

    // Download file contents.
    if ('array' === $this->format) {
      $content = $sk_download->execute();
      $searchDisplayContent = json_encode($content);
    }
    else {
      ob_start();
      $sk_download->execute();
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
        'uri' => $fileName,
        'document' => $searchDisplayContent,
        'description' => $this->reportName,
        'upload_date' => date('Y-m-d H:i:s', time()),
      ];

      $fileDao = \CRM_Core_BAO_File::writeRecord($fileParams);
      $fileDao->find(TRUE);

      $entityFileDao = new \CRM_Core_DAO_EntityFile();
      $entityFile['entity_table'] = $entity_table;
      $entityFile['entity_id'] = $entity_id;
      $entityFileDao->copyValues($entityFile);
      $entityFileDao->file_id = $fileDao->id;
      $entityFileDao->save();

      $path = $directoryName . $fileName;
      file_put_contents($path, $searchDisplayContent);

      $result['file'] = $fileDao;
    }
  }

}
