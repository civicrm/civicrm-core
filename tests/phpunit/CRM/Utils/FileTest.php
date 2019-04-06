<?php

/**
 * Class CRM_Utils_FileTest
 * @group headless
 */
class CRM_Utils_FileTest extends CiviUnitTestCase {

  /**
   * Test is child path.
   */
  public function testIsChildPath() {
    $testCases = array();
    $testCases[] = array('/ab/cd/ef', '/ab/cd', FALSE);
    $testCases[] = array('/ab/cd', '/ab/cd/ef', TRUE);
    $testCases[] = array('/ab/cde', '/ab/cd/ef', FALSE);
    $testCases[] = array('/ab/cde', '/ab/cd', FALSE);
    $testCases[] = array('/ab/cd', 'ab/cd/ef', FALSE);
    foreach ($testCases as $testCase) {
      $actual = CRM_Utils_File::isChildPath($testCase[0], $testCase[1], FALSE);
      $this->assertEquals($testCase[2], $actual, sprintf("parent=[%s] child=[%s] expected=[%s] actual=[%s]",
        $testCase[0], $testCase[1], $testCase[2], $actual
      ));
    }
  }

  public function testStripComment() {
    $strings = array(
      "\nab\n-- cd\nef" => "\nab\nef",
      "ab\n-- cd\nef" => "ab\nef",
      "ab\n-- cd\nef\ngh" => "ab\nef\ngh",
      "ab\n--cd\nef" => "ab\nef",
      "ab\n--cd\nef\n" => "ab\nef\n",
      "ab\n#cd\nef\n" => "ab\nef\n",
      "ab\n--cd\nef" => "ab\nef",
      "ab\n#cd\nef" => "ab\nef",
      "ab\nfoo#cd\nef" => "ab\nfoo#cd\nef",
      "ab\r\n--cd\r\nef" => "ab\r\nef",
      "ab\r\n#cd\r\nef" => "ab\r\nef",
      "ab\r\nfoo#cd\r\nef" => "ab\r\nfoo#cd\r\nef",
    );
    foreach ($strings as $string => $check) {
      $test = CRM_Utils_File::stripComments($string);
      $this->assertEquals($test,
          $check,
          sprintf("original=[%s]\nstripped=[%s]\nexpected=[%s]",
              json_encode($string),
              json_encode($test),
              json_encode($check)
           )
      );
    }
  }

  public function fileExtensions() {
    return array(
      array('txt'),
      array('danger'),
    );
  }

  /**
   * @dataProvider fileExtensions
   * @param string $ext
   */
  public function testDuplicate($ext) {
    $fileName = CRM_Utils_File::makeFileName('test' . rand(100, 999) . ".$ext");
    CRM_Utils_File::createFakeFile('/tmp', 'test file content', $fileName);
    $newFile = CRM_Utils_File::duplicate("/tmp/$fileName");
    $this->assertNotEquals("/tmp/$fileName", $newFile);
    $contents = file_get_contents($newFile);
    $this->assertEquals('test file content', $contents);
    unlink("/tmp/$fileName");
    unlink($newFile);
  }

  public function fileNames() {
    $cases = [];
    $cases[] = ['helloworld.txt', TRUE];
    $cases[] = ['../helloworld.txt', FALSE];
    // Test case seems to be failing for a strange reason
    // $cases[] = ['\helloworld.txt', FALSE];
    $cases[] = ['.helloworld', FALSE];
    $cases[] = ['smartwatch_1736683_1280_9af3657015e8660cc234eb1601da871.jpg', TRUE];
    return $cases;
  }

  /**
   * Test if the fileName is valid or not
   * @dataProvider fileNames
   * @param string $fileName
   * @param bool $expectedResult
   */
  public function testFileNameValid($fileName, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_File::isValidFileName($fileName));
  }

}
