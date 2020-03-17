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

/**
 * Tests for parsing translatable strings in HTML content.
 * @group headless
 */
class CRM_Core_Resources_StringsTest extends CiviUnitTestCase {

  /**
   * Get strings from files.
   */
  public function testGet() {
    $basedir = $this->createExamples();
    $strings = new CRM_Core_Resources_Strings(
      new CRM_Utils_Cache_Arraycache(NULL)
    );
    $this->assertEquals(
      ['Hello from Javascript'],
      $strings->get('example', "$basedir/hello.js", "text/javascript")
    );
    $this->assertEquals(
      ['Hello from HTML'],
      $strings->get('example', "$basedir/hello.html", "text/html")
    );
  }

  /**
   * @return string
   *   Path to the example dir.
   */
  public function createExamples() {
    $basedir = rtrim($this->createTempDir('ext-'), '/');
    file_put_contents("$basedir/hello.js", "alert(ts('Hello from Javascript'));");
    file_put_contents("$basedir/hello.html", "<div>{{ts('Hello from HTML')}}</div>");
    return $basedir;
  }

}
