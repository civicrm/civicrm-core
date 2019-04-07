<?php
/**
 *  File for the CiviTestSuite class
 *
 *  (PHP 5)
 *
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 * Parent class for test suites
 *
 * @package   CiviCRM
 */
class CiviTestSuite extends PHPUnit_Framework_TestSuite {

  /**
   * Simple name based constructor.
   * @param string $theClass
   * @param string $name
   */
  public function __construct($theClass = '', $name = '') {
    if (empty($name)) {
      $name = str_replace('_',
        ' ',
        get_class($this)
      );

      // also split AllTests to All Tests
      $name = str_replace('AllTests', 'All Tests', $name);
    }
    parent::__construct($name);

    // also load the class loader
    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();
  }

  /**
   *  Test suite setup.
   */
  protected function setUp() {
    //print __METHOD__ . "\n";
  }

  /**
   *  Test suite teardown.
   */
  protected function tearDown() {
    //print __METHOD__ . "\n";
  }

  /**
   *  suppress failed test error issued by phpunit when it finds.
   *  a test suite with no tests
   */
  public function testNothing() {
  }

  /**
   * @param $myfile
   * @return \PHPUnit_Framework_TestSuite
   */
  protected function implSuite($myfile) {
    $name = str_replace('_',
      ' ',
      get_class($this)
    );

    // also split AllTests to All Tests
    $name = str_replace('AllTests', 'All Tests', $name);

    $suite = new PHPUnit_Framework_TestSuite($name);
    $this->addAllTests($suite, $myfile,
      new SplFileInfo(dirname($myfile))
    );
    return $suite;
  }

  /**
   *  Add all test classes *Test and all test suites *Tests in subdirectories
   *
   * @param PHPUnit_Framework_TestSuite $suite
   *   Test suite object to add tests to
   * @param $myfile
   * @param SplFileInfo $dirInfo
   *   object to scan
   *
   * @return void
   */
  protected function addAllTests(
    PHPUnit_Framework_TestSuite &$suite,
    $myfile, SplFileInfo $dirInfo
  ) {
    //echo get_class($this)."::addAllTests($myfile,".$dirInfo->getRealPath().")\n";
    if (!$dirInfo->isReadable()
      || !$dirInfo->isDir()
    ) {
      return;
    }

    //  Pass 1:  Check all *Tests.php files
    // array(callable)
    $addTests = array();
    //echo "start Pass 1 on {$dirInfo->getRealPath()}\n";
    $dir = new DirectoryIterator($dirInfo->getRealPath());
    foreach ($dir as $fileInfo) {
      if ($fileInfo->isReadable() && $fileInfo->isFile()
        && preg_match('/Tests.php$/',
          $fileInfo->getFilename()
        )
      ) {
        if ($fileInfo->getRealPath() == $myfile) {
          //  Don't create an infinite loop
          //echo "ignoring {$fileInfo->getRealPath()}\n";
          continue;
        }
        //echo "checking file ".$fileInfo->getRealPath( )."\n";
        //  This is a file with a name ending in 'Tests.php'.
        //  Get all classes defined in the file and add those
        //  with a class name ending in 'Test' to the test suite
        $oldClassNames = get_declared_classes();
        require_once $fileInfo->getRealPath();
        $newClassNames = get_declared_classes();
        foreach (array_diff($newClassNames,
          $oldClassNames
                 ) as $name) {
          if (preg_match('/Tests$/', $name)) {
            $addTests[] = $name . '::suite';
          }
        }
      }
    }
    sort($addTests);
    foreach ($addTests as $addTest) {
      $suite->addTest(call_user_func($addTest));
    }

    //  Pass 2:  Scan all subdirectories
    // array(array(0 => $suite, 1 => $file, 2 => SplFileinfo))
    $addAllTests = array();
    $dir = new DirectoryIterator($dirInfo->getRealPath());
    //echo "start Pass 2 on {$dirInfo->getRealPath()}\n";
    foreach ($dir as $fileInfo) {
      if ($fileInfo->isDir()
        && (substr($fileInfo->getFilename(), 0, 1) != '.')
      ) {
        //  This is a directory that may contain tests so scan it
        $addAllTests[] = clone $fileInfo;
      }
    }
    //$addAllTests = CRM_Utils_Array::crmArraySortByField($addAllTests, '1');
    usort($addAllTests, function ($a, $b) {
      return strnatcmp($a->getRealPath(), $b->getRealPath());
    });
    foreach ($addAllTests as $addAllTest) {
      $this->addAllTests($suite, $myfile, $addAllTest);
    }

    //  Pass 3:  Check all *Test.php files in this directory
    //echo "start Pass 3 on {$dirInfo->getRealPath()}\n";
    // array(className)
    $addTestSuites = array();
    $dir = new DirectoryIterator($dirInfo->getRealPath());
    foreach ($dir as $fileInfo) {
      if ($fileInfo->isReadable() && $fileInfo->isFile()
        && preg_match('/Test.php$/',
          $fileInfo->getFilename()
        )
      ) {
        //echo "checking file ".$fileInfo->getRealPath( )."\n";
        //  This is a file with a name ending in 'Tests?.php'.
        //  Get all classes defined in the file and add those
        //  with a class name ending in 'Test' to the test suite
        $oldClassNames = get_declared_classes();
        require_once $fileInfo->getRealPath();
        $newClassNames = get_declared_classes();
        foreach (array_diff($newClassNames,
          $oldClassNames
                 ) as $name) {
          if (strpos($fileInfo->getRealPath(), strtr($name, '_\\', '//') . ".php") !== FALSE) {
            if (preg_match('/Test$/', $name)) {
              $addTestSuites[] = $name;
            }
          }
        }
      }
    }
    sort($addTestSuites);
    foreach ($addTestSuites as $addTestSuite) {
      $suite->addTestSuite($addTestSuite);
    }

    // print_r(array($prefix, 'addTests' => $addTests, 'addAllTests' => $addAllTests, 'addTestSuites' => $addTestSuites));
  }

}
