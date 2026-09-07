<?php

use Civi\Import\DataSource\Spreadsheet;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class SpreadsheetDataSourceTest extends TestCase implements HeadlessInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * A missing or discarded upload fails the rule instead of crashing the form.
   */
  public function testMissingUploadIsNotValid(): void {
    $this->assertFalse(Spreadsheet::isValidSpreadsheet(['tmp_name' => '']));
    $this->assertFalse(Spreadsheet::isValidSpreadsheet(['tmp_name' => '/nonexistent/upload.xlsx']));
    $this->assertFalse(Spreadsheet::isValidSpreadsheet([]));
  }

  public function testTextFileIsNotValid(): void {
    $path = tempnam(sys_get_temp_dir(), 'civiimport');
    file_put_contents($path, "a,b\n1,2\n");
    $this->assertFalse(Spreadsheet::isValidSpreadsheet(['tmp_name' => $path]));
    unlink($path);
  }

}
