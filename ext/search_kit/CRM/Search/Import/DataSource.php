<?php

/**
 * DataSource object for Api4-based imports via SearchKit
 *
 * Doesn't do anything because the data has already been saved by the search display.
 */
class CRM_Search_Import_DataSource extends CRM_Import_DataSource {

  public function checkPermission(): bool {
    // This dataSource is not for use outside SearchKit displays
    return FALSE;
  }

  public function getInfo(): array {
    return [];
  }

  public function buildQuickForm(\CRM_Import_Forms $form): void {
    // Unnecessary
  }

  public function initialize(): void {
    // Unnecessary
  }

}
