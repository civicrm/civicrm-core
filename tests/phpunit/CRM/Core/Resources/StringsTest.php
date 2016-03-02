<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
      array('Hello from Javascript'),
      $strings->get('example', "$basedir/hello.js", "text/javascript")
    );
    $this->assertEquals(
      array('Hello from HTML'),
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
