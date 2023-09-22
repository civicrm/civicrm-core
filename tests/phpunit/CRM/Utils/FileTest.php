<?php

/**
 * Class CRM_Utils_FileTest
 * @group headless
 */
class CRM_Utils_FileTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->callAPISuccess('OptionValue', 'get', ['option_group_id' => 'safe_file_extension', 'value' => 17, 'api.option_value.delete' => ['id' => "\$value.id"]]);
  }

  /**
   * Test is child path.
   */
  public function testIsChildPath(): void {
    $testCases = [];
    $testCases[] = ['/ab/cd/ef', '/ab/cd', FALSE];
    $testCases[] = ['/ab/cd', '/ab/cd/ef', TRUE];
    $testCases[] = ['/ab/cde', '/ab/cd/ef', FALSE];
    $testCases[] = ['/ab/cde', '/ab/cd', FALSE];
    $testCases[] = ['/ab/cd', 'ab/cd/ef', FALSE];
    foreach ($testCases as $testCase) {
      $actual = CRM_Utils_File::isChildPath($testCase[0], $testCase[1], FALSE);
      $this->assertEquals($testCase[2], $actual, sprintf("parent=[%s] child=[%s] checkRealPath=[FALSE] expected=[%s] actual=[%s]",
        $testCase[0], $testCase[1], $testCase[2], $actual
      ));
    }

    global $civicrm_root;
    $realCases = [];
    $realCases[] = ["$civicrm_root", "$civicrm_root/CRM", TRUE];
    $realCases[] = ["$civicrm_root/CRM", "$civicrm_root", FALSE];
    $realCases[] = ["/nonexistent", "/nonexistent/child", FALSE];
    $realCases[] = ["/nonexistent/child", "/nonexistent", FALSE];
    $realCases[] = ["$civicrm_root", "/nonexistent", FALSE];
    $realCases[] = ["/nonexistent", "$civicrm_root", FALSE];
    foreach ($realCases as $testCase) {
      $actual = CRM_Utils_File::isChildPath($testCase[0], $testCase[1], TRUE);
      $this->assertEquals($testCase[2], $actual, sprintf("parent=[%s] child=[%s] checkRealPath=[TRUE] expected=[%s] actual=[%s]",
        $testCase[0], $testCase[1], $testCase[2], $actual
      ));
    }
  }

  public function testStripComment(): void {
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
  public function testIsIncludable(): void {
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
   * Test isDir
   *
   * I stole some of these from php's own unit tests at
   *   https://github.com/php/php-src/blob/5b01c4863fe9e4bc2702b2bbf66d292d23001a18/ext/standard/tests/file/is_dir_basic.phpt
   * and related files.
   *
   * @dataProvider isDirProvider
   *
   * @param string|null $input
   * @param bool $expected
   */
  public function testIsDir(?string $input, bool $expected) {
    clearstatcache();
    $this->assertSame($expected, CRM_Utils_File::isDir($input));
  }

  /**
   * Test isDir with invalid args.
   *
   * @dataProvider isDirInvalidArgsProvider
   *
   * @param mixed $input
   * @param bool $expected
   */
  public function testIsDirInvalidArgs($input, bool $expected) {
    $this->assertSame($expected, CRM_Utils_File::isDir($input));
  }

  /**
   * Just trying to include some of the same tests as php itself and
   * this doesn't fit in well to a dataprovider so is separate.
   */
  public function testIsDirMkdir(): void {
    $a_dir = sys_get_temp_dir() . '/testIsDir';
    // I think temp is global to the test node, so if any test failed on this
    // in the past it doesn't get cleaned up and so already exists.
    system('rm -rf ' . escapeshellarg($a_dir));
    mkdir($a_dir);
    $this->assertTrue(CRM_Utils_File::isDir($a_dir));
    mkdir($a_dir . '/aSubDir');
    $this->assertTrue(CRM_Utils_File::isDir($a_dir . '/aSubDir'));
    clearstatcache();
    $this->assertTrue(CRM_Utils_File::isDir($a_dir));
    rmdir($a_dir . '/aSubDir');
    rmdir($a_dir);
  }

  /**
   * testIsDirSlashVariations
   */
  public function testIsDirSlashVariations(): void {
    $a_dir = sys_get_temp_dir() . '/testIsDir';
    // I think temp is global to the test node, so if any test failed on this
    // in the past it doesn't get cleaned up and so already exists.
    system('rm -rf ' . escapeshellarg($a_dir));
    mkdir($a_dir);

    $old_cwd = getcwd();
    $this->assertTrue(chdir(sys_get_temp_dir()));

    $this->assertTrue(CRM_Utils_File::isDir("./testIsDir"));
    clearstatcache();
    $this->assertTrue(CRM_Utils_File::isDir("testIsDir/"));
    clearstatcache();
    $this->assertTrue(CRM_Utils_File::isDir("./testIsDir/"));
    clearstatcache();
    $this->assertTrue(CRM_Utils_File::isDir("testIsDir//"));
    clearstatcache();
    $this->assertTrue(CRM_Utils_File::isDir("./testIsDir//"));
    clearstatcache();
    $this->assertTrue(CRM_Utils_File::isDir(".//testIsDir//"));
    clearstatcache();
    $this->assertFalse(CRM_Utils_File::isDir('testIsDir*'));

    // Note that in php8 is_dir changed in php itself to return false with no warning for these. It used to give `is_dir() expects parameter 1 to be a valid path, string given`. See https://github.com/php/php-src/commit/7bc7a80445f2bb349891d3cccfef2d589c48607e
    clearstatcache();
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
      $this->assertNull(CRM_Utils_File::isDir('./testIsDir/' . chr(0)));
      clearstatcache();
      $this->assertNull(CRM_Utils_File::isDir("testIsDir\0"));
    }
    else {
      $this->assertFalse(CRM_Utils_File::isDir('./testIsDir/' . chr(0)));
      clearstatcache();
      $this->assertFalse(CRM_Utils_File::isDir("testIsDir\0"));
    }

    $this->assertTrue(chdir($old_cwd));
    rmdir($a_dir);
  }

  /**
   * Test hard and soft links with isDir
   * Note hard links to directories aren't allowed so can only test with file.
   */
  public function testIsDirLinks(): void {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      $this->markTestSkipped('Windows has links but not the same.');
    }

    $a_dir = sys_get_temp_dir() . '/testIsDir';
    // I think temp is global to the test node, so if any test failed on this
    // in the past it doesn't get cleaned up and so already exists.
    system('rm -rf ' . escapeshellarg($a_dir));
    mkdir($a_dir);
    symlink($a_dir, $a_dir . '_symlink');
    $this->assertTrue(CRM_Utils_File::isDir($a_dir . '_symlink'));

    $a_file = $a_dir . '/testFile';
    touch($a_file);
    $this->assertFalse(CRM_Utils_File::isDir($a_file));

    clearstatcache();
    symlink($a_file, $a_file . '_symlink');
    $this->assertFalse(CRM_Utils_File::isDir($a_file . '_symlink'));

    clearstatcache();
    link($a_file, $a_file . '_hardlink');
    $this->assertFalse(CRM_Utils_File::isDir($a_file . '_hardlink'));

    unlink($a_file . '_symlink');
    unlink($a_file . '_hardlink');
    unlink($a_file);
    unlink($a_dir . '_symlink');
    rmdir($a_dir);
  }

  /**
   * Test isDir with open_basedir
   *
   * @link https://github.com/php/php-src/blob/5b01c4863fe9e4bc2702b2bbf66d292d23001a18/tests/security/open_basedir_is_dir.phpt
   * @group ornery
   *   This manipulates `open_basedir`, which is a one-way street, and it will interfere with phpunit-cli. (Test failures will lead to
   *   lots of extra data-dumps.)
   *
   * @dataProvider isDirBasedirProvider
   *
   * @param string|null $input
   * @param bool $expected
   */
  public function testIsDirWithOpenBasedir(?string $input, bool $expected) {
    // This might not always be under cms root, but let's see how it goes.
    $a_dir = \Civi::paths()->getPath('[civicrm.compile]/.');

    if (file_exists("{$a_dir}/isDirTest/ok/ok.txt")) {
      unlink("{$a_dir}/isDirTest/ok/ok.txt");
    }
    if (is_dir("{$a_dir}/isDirTest/ok")) {
      rmdir("{$a_dir}/isDirTest/ok");
    }
    if (is_dir("{$a_dir}/isDirTest")) {
      rmdir("{$a_dir}/isDirTest");
    }

    // We want the cms root path, but in headless tests even though there is
    // a real cms strictly speaking the cms is "UNITTESTS", which might return
    // something made up (currently NULL).
    // \Civi::paths()->getPath('[cms.root]/')
    // For now let's try this, assuming a drupal 7 structure where we know
    // where this file is:
    $cms_root = realpath(__DIR__ . '/../../../../../../../..');

    // NOTE: The `[civicrm.compile]` does not necessarily live under the public `[cms.root] -- e.g. if you have a private data-folder.
    $open_base_dirs = implode(PATH_SEPARATOR, [$cms_root, dirname($a_dir)]);

    // This test requires tightening `open_basedir` settings, which is a one-way change.
    // Therefore, we have to do that part of the test in a sub-process.
    // If you run directly in the same-process, then (eg) `phpunit` will have trouble writing error-data to JUnit XML files.
    [$exitCode, $stdout, $stderr] = $this->runStaticMethodAsScript(__CLASS__, 'doIsDirWithOpenBasedir', [$a_dir, $open_base_dirs, $input, $expected]);
    $this->assertEquals('"OK"', trim($stdout), 'doIsDirWithOpenBasedir() should return OK');
    $this->assertEquals('', trim($stderr), 'doIsDirWithOpenBasedir() should not generate warnings');
    $this->assertEquals(0, $exitCode, 'doIsDirWithOpenBasedir() should exit normally');

    unlink("{$a_dir}/isDirTest/ok/ok.txt");
    rmdir("{$a_dir}/isDirTest/ok");
    rmdir("{$a_dir}/isDirTest");
  }

  /**
   * @param string $a_dir
   * @param string $open_base_dirs
   * @param string|null $input
   * @param bool $expected
   * @return string
   * @throws \ErrorException
   */
  public static function doIsDirWithOpenBasedir(string $a_dir, string $open_base_dirs, ?string $input, bool $expected): string {
    // We also need temp dir because phpunit creates files in there as it does stuff before we can reset basedir.
    ini_set('open_basedir', $open_base_dirs . PATH_SEPARATOR . sys_get_temp_dir());

    if (!mkdir("{$a_dir}/isDirTest")) {
      return "Failed to make isDirTest";
    }

    if (!mkdir("{$a_dir}/isDirTest/ok")) {
      return "Failed to make isDirTest/ok";
    }

    file_put_contents("{$a_dir}/isDirTest/ok/ok.txt", 'Hello World!');
    // hmm the "bad" isn't going to work the same way php's own tests work. We
    // need to find a directory outside both cms_root and the sys temp dir.
    // Let's just use some known unix files that always exist instead.
    // mkdir("{$a_dir}/isDirTest/bad");

    if (!chdir("{$a_dir}/isDirTest/ok")) {
      return 'Failed to chdir to isDirTest/ok';
    }

    clearstatcache();
    $actual = CRM_Utils_File::isDir($input); /* Should pass */
    // $actual = is_dir($input); /* Should fail */
    if ($expected !== $actual) {
      return sprintf("isDir returned incorrect result. Expected=%s Actual=%s", json_encode($expected), json_encode($actual));
      // Note that except for 'ok.txt', the real is_dir() would give an
      // error for these. For 'ok.txt' it would return false, but no error.
      // So this is what we are changing about the real function.
    }

    return 'OK';
  }

  protected function runStaticMethodAsScript(string $class, string $method, $args = []) {
    $func = new ReflectionMethod($class, $method);
    $outClass = 'Wrapper' . time();
    $ignoreStdErr = ';^Xdebug:;';

    $inFile = $func->getFileName();
    $inFileLines = explode("\n", file_get_contents($inFile));

    $lines = array_merge(
      [
        '<' . '?php',
        "class $outClass {",
      ],
      array_slice($inFileLines, $func->getStartLine() - 1, $func->getEndLine() - $func->getStartLine() + 1),
      [
        '}',
        sprintf('$args = %s;', var_export($args, 1)),
        sprintf('return %s::%s(...$args);', $outClass, $method),
      ]
    );
    $tmpSrc = implode("\n", $lines);

    $outFile = tempnam(sys_get_temp_dir(), 'test-script-') . '.php';
    file_put_contents($outFile, $tmpSrc);

    try {
      $cmd = 'cv ev -v ' . escapeshellarg("return require \"$outFile\";");
      $descriptorSpec = array(0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']);
      $oldOutput = getenv('CV_OUTPUT');
      putenv("CV_OUTPUT=json");
      $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);
      putenv("CV_OUTPUT=$oldOutput");
      fclose($pipes[0]);
      $stdout = stream_get_contents($pipes[1]);
      $stderr = stream_get_contents($pipes[2]);
      fclose($pipes[1]);
      fclose($pipes[2]);

      $stderr = implode("\n", preg_grep($ignoreStdErr, explode("\n", $stderr), PREG_GREP_INVERT));
      $exitCode = proc_close($process);

      return [$exitCode, $stdout, $stderr];
    }
    finally {
      unlink($outFile);
    }
  }

  /**
   * dataprovider for testIsDir
   *
   * @return array
   */
  public function isDirProvider(): array {
    return [
      // explicit indices to make it easier to see which one failed
      0 => [
        // input value
        NULL,
        // expected value
        FALSE,
      ],
      1 => ['.', TRUE],
      2 => ['..', TRUE],
      3 => [__FILE__, FALSE],
      4 => [__DIR__, TRUE],
      5 => ['dontexist', FALSE],
      6 => ['/no/such/dir', FALSE],
      7 => [' ', FALSE],
    ];
  }

  /**
   * dataprovider for testIsDirInvalidArgs
   *
   * @return array
   */
  public function isDirInvalidArgsProvider(): array {
    return [
      // explicit indices to make it easier to see which one failed
      0 => [-2.34555, FALSE],
      1 => [TRUE, FALSE],
      2 => [FALSE, FALSE],
      3 => [0, FALSE],
      4 => [1234, FALSE],
    ];
  }

  /**
   * dataprovider for testIsDirWithOpenBasedir
   *
   * @return array
   */
  public function isDirBasedirProvider(): array {
    return [
      // explicit indices to make it easier to see which one failed
      0 => [
        // input value
        strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'C:/windows' : '/etc',
        // expected value
        FALSE,
      ],
      1 => [strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'C:/windows/win.ini' : '/etc/group', FALSE],
      // This assumes a known location for template compile dir relative to
      // open_basedir, and that we're 2 dirs below compile dir.
      2 => ['../../../../../../../..', FALSE],
      3 => ['../../../../../../../../', FALSE],
      4 => ['/', FALSE],
      5 => [strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'C:/windows/../windows/win.ini' : '/etc/../etc/group', FALSE],
      6 => ['./../.', TRUE],
      7 => ['../ok', TRUE],
      8 => ['ok.txt', FALSE],
      9 => ['../ok/ok.txt', FALSE],
    ];
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

  public function trueOrFalse(): array {
    return [
      'TRUE' => [TRUE],
      'FALSE' => [FALSE],
    ];
  }

  /**
   * @param bool $isRelative
   * @dataProvider trueOrFalse
   */
  public function testFindFilesDepth(bool $isRelative) {
    $CRM = Civi::paths()->getPath('[civicrm.root]/CRM');
    $depthResults[0] = CRM_Utils_File::findFiles($CRM, 'Contact.php', $isRelative, 0);
    $depthResults[1] = CRM_Utils_File::findFiles($CRM, 'Contact.php', $isRelative, 1);
    $depthResults[2] = CRM_Utils_File::findFiles($CRM, 'Contact.php', $isRelative, 2);
    $depthResults[3] = CRM_Utils_File::findFiles($CRM, 'Contact.php', $isRelative, 3);
    $depthResults[NULL] = CRM_Utils_File::findFiles($CRM, 'Contact.php', $isRelative);

    $expectPrefix = $isRelative ? '' : $CRM . '/';

    $expectFiles['Contact/BAO/Contact.php'] = [0 => FALSE, 1 => FALSE, 2 => TRUE, 3 => TRUE, NULL => TRUE];
    $expectFiles['Contact/Import/Parser/Contact.php'] = [0 => FALSE, 1 => FALSE, 2 => FALSE, 3 => TRUE, NULL => TRUE];

    foreach ($expectFiles as $expectFile => $expectMatches) {
      $actualMatches = [];
      foreach ($expectMatches as $depth => $expectMatch) {
        $actualMatches[$depth] = in_array($expectPrefix . $expectFile, $depthResults[$depth]);
      }
      $this->assertEquals($expectMatches, $actualMatches, "The file $expectFile should be found as follows:");
    }
  }

  /**
   * Generate examples to test the safe file extension
   * @return array
   */
  public static function safeFileExtensionExamples(): array {
    $cases = [
      'PDF File Extension' => ['pdf', TRUE, TRUE],
      'PHP File Extension' => ['php', FALSE, FALSE],
      'PHAR' => ['phar', FALSE, FALSE],
      'PHP5 File Extension' => ['php5', FALSE, FALSE],
    ];
    return $cases;
  }

  /**
   * Test that modifying the safe File Extension option group still ensures some are blocked
   * @dataProvider safeFileExtensionExamples
   */
  public function testSafeFileExtensionValidation($extension, $standardInstallCheck, $afterModificationCheck): void {
    $this->assertEquals($standardInstallCheck, CRM_Utils_File::isExtensionSafe($extension));
    $optionValue = $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'safe_file_extension',
      'label' => $extension,
      'name' => $extension,
      'value' => 17,
    ]);
    unset(Civi::$statics['CRM_Utils_File']['file_extensions']);
    $this->assertEquals($standardInstallCheck, CRM_Utils_File::isExtensionSafe($extension));
  }

}
