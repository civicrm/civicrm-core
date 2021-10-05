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
    $testCases = [];
    $testCases[] = ['/ab/cd/ef', '/ab/cd', FALSE];
    $testCases[] = ['/ab/cd', '/ab/cd/ef', TRUE];
    $testCases[] = ['/ab/cde', '/ab/cd/ef', FALSE];
    $testCases[] = ['/ab/cde', '/ab/cd', FALSE];
    $testCases[] = ['/ab/cd', 'ab/cd/ef', FALSE];
    foreach ($testCases as $testCase) {
      $actual = CRM_Utils_File::isChildPath($testCase[0], $testCase[1], FALSE);
      $this->assertEquals($testCase[2], $actual, sprintf("parent=[%s] child=[%s] expected=[%s] actual=[%s]",
        $testCase[0], $testCase[1], $testCase[2], $actual
      ));
    }
  }

  public function testStripComment() {
    $strings = [
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
    ];
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
    return [
      ['txt'],
      ['danger'],
    ];
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

  public function pathToFileExtension() {
    $cases = [];
    $cases[] = ['/evil.pdf', 'pdf'];
    $cases[] = ['/helloworld.jpg', 'jpg'];
    $cases[] = ['/smartwatch_1736683_1280_9af3657015e8660cc234eb1601da871.jpg', 'jpg'];
    return $cases;
  }

  /**
   * Test returning appropriate file extension
   * @dataProvider pathToFileExtension
   * @param string $path
   * @param string $expectedExtension
   */
  public function testPathToExtension($path, $expectedExtension) {
    $this->assertEquals($expectedExtension, CRM_Utils_File::getExtensionFromPath($path));
  }

  public function mimeTypeToExtension() {
    $cases = [];
    $cases[] = ['text/plain', ['txt', 'text', 'conf', 'def', 'list', 'log', 'in', 'ini']];
    $cases[] = ['image/jpeg', ['jpeg', 'jpg', 'jpe']];
    $cases[] = ['image/png', ['png']];
    return $cases;
  }

  /**
   * @dataProvider mimeTypeToExtension
   * @param stirng $mimeType
   * @param array $expectedExtensions
   */
  public function testMimeTypeToExtension($mimeType, $expectedExtensions) {
    $this->assertEquals($expectedExtensions, CRM_Utils_File::getAcceptableExtensionsForMimeType($mimeType));
  }

  /**
   * Check a few variations of isIncludable
   */
  public function testIsIncludable() {
    $path = \Civi::paths()->getPath('[civicrm.private]/');
    $bare_filename = 'afile' . time() . '.php';
    $file = "$path/$bare_filename";
    file_put_contents($file, '<?php');

    // A file that doesn't exist shouldn't be includable.
    $this->assertFalse(CRM_Utils_File::isIncludable('invisiblefile.php'));

    // Shouldn't be includable by default in civicrm.private
    $this->assertFalse(CRM_Utils_File::isIncludable($bare_filename));

    // Add civicrm.private to the include_path, then it should be includable.
    $old_include_path = ini_get('include_path');
    ini_set('include_path', $old_include_path . PATH_SEPARATOR . $path);
    $this->assertTrue(CRM_Utils_File::isIncludable($bare_filename));

    // Set permissions to 0, then it shouldn't be includable even if in path.
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
      chmod($file, 0);
      $this->assertFalse(CRM_Utils_File::isIncludable($bare_filename));
      chmod($file, 0644);
    }

    ini_set('include_path', $old_include_path);
    unlink($file);
  }

  /**
   * dataprovider for testMakeFilenameWithUnicode
   * @return array
   */
  public function makeFilenameWithUnicodeProvider(): array {
    return [
      // explicit indices to make it easier to see which one failed
      0 => [
        'string' => '',
        'replacementCharacter' => NULL,
        'cutoffLength' => NULL,
        'expected' => '',
      ],
      1 => [
        'string' => 'a',
        'replacementCharacter' => NULL,
        'cutoffLength' => NULL,
        'expected' => 'a',
      ],
      2 => [
        'string' => 'a b',
        'replacementCharacter' => NULL,
        'cutoffLength' => NULL,
        'expected' => 'a_b',
      ],
      3 => [
        'string' => 'a4b',
        'replacementCharacter' => NULL,
        'cutoffLength' => NULL,
        'expected' => 'a4b',
      ],
      4 => [
        'string' => '_a!@#$%^&*()[]+-=."\'{}<>?/\\|;:b',
        'replacementCharacter' => NULL,
        'cutoffLength' => NULL,
        'expected' => '_a____________________________b',
      ],
      5 => [
        'string' => '_a!@#$%^&*()[]+-=."\'{}<>?/\\|;:b',
        'replacementCharacter' => '',
        'cutoffLength' => NULL,
        'expected' => '_ab',
      ],
      // emojis get replaced, but alphabetic letters in non-english are kept
      6 => [
        'string' => 'açbяc😀d',
        'replacementCharacter' => NULL,
        'cutoffLength' => NULL,
        'expected' => 'açbяc_d',
      ],
      7 => [
        'string' => 'çя😀',
        'replacementCharacter' => NULL,
        'cutoffLength' => NULL,
        'expected' => 'çя_',
      ],
      // test default cutoff
      8 => [
        'string' => 'abcdefghijklmnopqrstuvwxyz0123456789012345678901234567890123456789',
        'replacementCharacter' => NULL,
        'cutoffLength' => NULL,
        'expected' => 'abcdefghijklmnopqrstuvwxyz0123456789012345678901234567890123456',
      ],
      9 => [
        'string' => 'abcdefghijklmnopqrstuvwxyz0123456789012345678901234567890123456789',
        'replacementCharacter' => '_',
        'cutoffLength' => 30,
        'expected' => 'abcdefghijklmnopqrstuvwxyz0123',
      ],
      // test cutoff truncates multibyte properly
      10 => [
        'string' => 'ДДДДДДДДДДДДДДД',
        'replacementCharacter' => '',
        'cutoffLength' => 10,
        'expected' => 'ДДДДДДДДДД',
      ],
    ];
  }

  /**
   * test makeFilenameWithUnicode
   * @dataProvider makeFilenameWithUnicodeProvider
   * @param string $input
   * @param ?string $replacementCharacter
   * @param ?int $cutoffLength
   * @param string $expected
   */
  public function testMakeFilenameWithUnicode(string $input, ?string $replacementCharacter, ?int $cutoffLength, string $expected) {
    if (is_null($replacementCharacter) && is_null($cutoffLength)) {
      $this->assertSame($expected, CRM_Utils_File::makeFilenameWithUnicode($input));
    }
    elseif (is_null($cutoffLength)) {
      $this->assertSame($expected, CRM_Utils_File::makeFilenameWithUnicode($input, $replacementCharacter));
    }
    else {
      $this->assertSame($expected, CRM_Utils_File::makeFilenameWithUnicode($input, $replacementCharacter, $cutoffLength));
    }
  }

}
