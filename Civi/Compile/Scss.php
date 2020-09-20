<?php
namespace Civi\Compile;

class Scss {

  /**
   * Compile some SCSS file(s).
   *
   * NOTE: This function runs during 'composer install', which is a pre-boot
   * environment. The composer autoloader has been configured, but no other
   * Civi services are online.
   *
   * @param array $task
   *   With keys:
   *   - scss-includes: string[], list of paths with SCSS helper files
   *   - scss-files: array, key-value mapping with input-files and output-files
   *
   * @see composer.json
   * @link https://github.com/civicrm/composer-compile-plugin/blob/master/doc/tasks.md
   */
  public static function build(array $task) {
    $scssCompiler = new \ScssPhp\ScssPhp\Compiler();
    $includes = $task['scss-includes'] ?? [];
    foreach ($includes as $include) {
      $scssCompiler->addImportPath($include);
    }

    if (empty($task['scss-files'])) {
      throw new \InvalidArgumentException("Invalid task: required argument 'scss-files' is missing");
    }
    foreach ($task['scss-files'] as $inputFile => $outputFile) {
      if (!file_exists($inputFile)) {
        throw new \InvalidArgumentException("File does not exist: " . $inputFile);
      }
      $inputScss = file_get_contents($inputFile);
      $css = $scssCompiler->compile($inputScss);
      $autoprefixer = new \Padaliyajay\PHPAutoprefixer\Autoprefixer($css);

      if (!file_exists(dirname($outputFile))) {
        mkdir(dirname($outputFile), 0777, TRUE);
      }
      $outputCss = $autoprefixer->compile();
      if (!file_put_contents($outputFile, $outputCss)) {
        throw new \RuntimeException("Failed to write file: $outputFile");
      }
    }
  }

}
