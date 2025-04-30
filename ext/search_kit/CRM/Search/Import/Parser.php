<?php

/**
 * Class CRM_Custom_Import_Parser_Api
 */
class CRM_Search_Import_Parser extends CRM_Import_Parser {

  /**
   * Get information about the provided job.
   *
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'search_batch_import' => [
        'id' => 'search_batch_import',
        'name' => 'search_batch_import',
        'label' => ts('Import data from Search Kit'),
      ],
    ];
  }

  public function getBaseEntity(): string {
    if (!isset($this->baseEntity)) {
      $this->init();
    }
    return $this->baseEntity;
  }

}
