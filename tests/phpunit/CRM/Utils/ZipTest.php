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
 * Class CRM_Utils_ZipTest
 * @group headless
 */
class CRM_Utils_ZipTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->file = FALSE;
  }

  public function tearDown() {
    parent::tearDown();
    if ($this->file) {
      unlink($this->file);
    }
  }

  public function testFindBaseDirName_normal() {
    $this->_doFindBaseDirName('author-com.example.foo-random/',
      ['author-com.example.foo-random'],
      ['author-com.example.foo-random/README.txt' => 'hello']
    );
  }

  public function testFindBaseDirName_0() {
    $this->_doFindBaseDirName('0/',
      ['0'],
      []
    );
  }

  public function testFindBaseDirName_plainfile() {
    $this->_doFindBaseDirName(FALSE,
      [],
      ['README.txt' => 'hello']
    );
  }

  public function testFindBaseDirName_twodir() {
    $this->_doFindBaseDirName(FALSE,
      ['dir-1', 'dir-2'],
      ['dir-1/README.txt' => 'hello']
    );
  }

  public function testFindBaseDirName_dirfile() {
    $this->_doFindBaseDirName(FALSE,
      ['dir-1'],
      ['dir-1/README.txt' => 'hello', 'MANIFEST.MF' => 'extra']
    );
  }

  public function testFindBaseDirName_dot() {
    $this->_doFindBaseDirName(FALSE,
      ['.'],
      ['./README.txt' => 'hello']
    );
  }

  public function testFindBaseDirName_dots() {
    $this->_doFindBaseDirName(FALSE,
      ['..'],
      ['../README.txt' => 'hello']
    );
  }

  public function testFindBaseDirName_weird() {
    $this->_doFindBaseDirName(FALSE,
      ['foo/../'],
      ['foo/../README.txt' => 'hello']
    );
  }

  public function testGuessBaseDir_normal() {
    $this->_doGuessBaseDir('author-com.example.foo-random',
      ['author-com.example.foo-random'],
      ['author-com.example.foo-random/README.txt' => 'hello'],
      'com.example.foo'
    );
  }

  public function testGuessBaseDir_MACOSX() {
    $this->_doGuessBaseDir('com.example.foo',
      ['com.example.foo', '__MACOSX'],
      ['author-com.example.foo-random/README.txt' => 'hello', '__MACOSX/foo' => 'bar'],
      'com.example.foo'
    );
  }

  public function testGuessBaseDir_0() {
    $this->_doGuessBaseDir('0',
      ['0'],
      [],
      'com.example.foo'
    );
  }

  public function testGuessBaseDir_plainfile() {
    $this->_doGuessBaseDir(FALSE,
      [],
      ['README.txt' => 'hello'],
      'com.example.foo'
    );
  }

  public function testGuessBaseDirTwoDir() {
    $this->_doGuessBaseDir(FALSE,
      ['dir-1', 'dir-2'],
      ['dir-1/README.txt' => 'hello'],
      'com.example.foo'
    );
  }

  public function testGuessBaseDirWeird() {
    $this->_doGuessBaseDir(FALSE,
      ['foo/../'],
      ['foo/../README.txt' => 'hello'],
      'com.example.foo'
    );
  }

  /**
   * @param string $expectedBaseDirName
   * @param $dirs
   * @param $files
   */
  public function _doFindBaseDirName($expectedBaseDirName, $dirs, $files) {
    $this->file = tempnam(sys_get_temp_dir(), 'testzip-');
    $this->assertTrue(CRM_Utils_Zip::createTestZip($this->file, $dirs, $files));

    $zip = new ZipArchive();
    $this->assertTrue($zip->open($this->file));
    $this->assertEquals($expectedBaseDirName, CRM_Utils_Zip::findBaseDirName($zip));
  }

  /**
   * @param $expectedResult
   * @param $dirs
   * @param $files
   * @param $expectedKey
   */
  public function _doGuessBaseDir($expectedResult, $dirs, $files, $expectedKey) {
    $this->file = tempnam(sys_get_temp_dir(), 'testzip-');
    $this->assertTrue(CRM_Utils_Zip::createTestZip($this->file, $dirs, $files));

    $zip = new ZipArchive();
    $this->assertTrue($zip->open($this->file));
    $this->assertEquals($expectedResult, CRM_Utils_Zip::guessBaseDir($zip, $expectedKey));
  }

}
