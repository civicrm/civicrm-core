<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Utils_ZipTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Zip Test',
      'description' => 'Test Zip Functions',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->file = FALSE;
  }
  
  function tearDown() {
    parent::tearDown();
    if ($this->file) {
      unlink($this->file);
    }
  }

  function testFindBaseDirName_normal() {
    $this->_doFindBaseDirName('author-com.example.foo-random/',
      array('author-com.example.foo-random'),
      array('author-com.example.foo-random/README.txt' => 'hello')
    );
  }

  function testFindBaseDirName_0() {
    $this->_doFindBaseDirName('0/',
      array('0'),
      array()
    );
  }
  
  function testFindBaseDirName_plainfile() {
    $this->_doFindBaseDirName(FALSE,
      array(),
      array('README.txt' => 'hello')
    );
  }

  function testFindBaseDirName_twodir() {
    $this->_doFindBaseDirName(FALSE,
      array('dir-1', 'dir-2'),
      array('dir-1/README.txt' => 'hello')
    );
  }

  function testFindBaseDirName_dirfile() {
    $this->_doFindBaseDirName(FALSE,
      array('dir-1'),
      array('dir-1/README.txt' => 'hello', 'MANIFEST.MF' => 'extra')
    );
  }

  function testFindBaseDirName_dot() {
    $this->_doFindBaseDirName(FALSE,
      array('.'),
      array('./README.txt' => 'hello')
    );
  }

  function testFindBaseDirName_dots() {
    $this->_doFindBaseDirName(FALSE,
      array('..'),
      array('../README.txt' => 'hello')
    );
  }

  function testFindBaseDirName_weird() {
    $this->_doFindBaseDirName(FALSE,
      array('foo/../'),
      array('foo/../README.txt' => 'hello')
    );
  }

  function testGuessBaseDir_normal() {
    $this->_doGuessBaseDir('author-com.example.foo-random',
      array('author-com.example.foo-random'),
      array('author-com.example.foo-random/README.txt' => 'hello'),
      'com.example.foo'
    );
  }

  function testGuessBaseDir_MACOSX() {
    $this->_doGuessBaseDir('com.example.foo',
      array('com.example.foo', '__MACOSX'),
      array('author-com.example.foo-random/README.txt' => 'hello', '__MACOSX/foo' => 'bar'),
      'com.example.foo'
    );
  }

  function testGuessBaseDir_0() {
    $this->_doGuessBaseDir('0',
      array('0'),
      array(),
      'com.example.foo'
    );
  }
  
  function testGuessBaseDir_plainfile() {
    $this->_doGuessBaseDir(FALSE,
      array(),
      array('README.txt' => 'hello'),
      'com.example.foo'
    );
  }

  function testGuessBaseDir_twodir() {
    $this->_doGuessBaseDir(FALSE,
      array('dir-1', 'dir-2'),
      array('dir-1/README.txt' => 'hello'),
      'com.example.foo'
    );
  }
  
  function testGuessBaseDir_weird() {
    $this->_doGuessBaseDir(FALSE,
      array('foo/../'),
      array('foo/../README.txt' => 'hello'),
      'com.example.foo'
    );
  }
  
  function _doFindBaseDirName($expectedBaseDirName, $dirs, $files) {
    $this->file = tempnam(sys_get_temp_dir(), 'testzip-');
    $this->assertTrue(CRM_Utils_Zip::createTestZip($this->file, $dirs, $files));
    
    $zip = new ZipArchive();
    $this->assertTrue($zip->open($this->file));
    $this->assertEquals($expectedBaseDirName, CRM_Utils_Zip::findBaseDirName($zip));
  }
  
  function _doGuessBaseDir($expectedResult, $dirs, $files, $expectedKey) {
    $this->file = tempnam(sys_get_temp_dir(), 'testzip-');
    $this->assertTrue(CRM_Utils_Zip::createTestZip($this->file, $dirs, $files));
    
    $zip = new ZipArchive();
    $this->assertTrue($zip->open($this->file));
    $this->assertEquals($expectedResult, CRM_Utils_Zip::guessBaseDir($zip, $expectedKey));
  }
}
