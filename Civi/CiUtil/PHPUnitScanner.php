<?php
namespace Civi\CiUtil;

use Symfony\Component\Finder\Finder;

/**
 * Search for PHPUnit test cases
 */
class PHPUnitScanner {
  /**
   * @param $path
   * @return array <string> class names
   */
  public static function _findTestClasses($path) {
    //    print_r(array(
    //      'loading' => $path,
    //      get_included_files()
    //    ));
    $origClasses = get_declared_classes();
    require_once $path;
    $newClasses = get_declared_classes();

    return preg_grep('/Test$/', array_diff(
      $newClasses,
      $origClasses
    ));
  }

  /**
   * @param $paths
   * @return array (string $file => string $class)
   * @throws \Exception
   */
  public static function findTestClasses($paths) {
    $testClasses = array();
    $finder = new Finder();

    foreach ($paths as $path) {
      if (is_dir($path)) {
        foreach ($finder->files()->in($paths)->name('*Test.php') as $file) {
          $testClass = self::_findTestClasses((string) $file);
          if (count($testClass) == 1) {
            $testClasses[(string) $file] = array_shift($testClass);
          }
          elseif (count($testClass) > 1) {
            throw new \Exception("Too many classes in $file");
          }
          else {
            throw new \Exception("Too few classes in $file");
          }
        }
      }
      elseif (is_file($path)) {
        $testClass = self::_findTestClasses($path);
        if (count($testClass) == 1) {
          $testClasses[$path] = array_shift($testClass);
        }
        elseif (count($testClass) > 1) {
          throw new \Exception("Too many classes in $path");
        }
        else {
          throw new \Exception("Too few classes in $path");
        }
      }
    }

    return $testClasses;
  }

  /**
   * @param array $paths
   *
   * @return array
   *   each element is an array with keys:
   *   - file: string
   *   - class: string
   *   - method: string
   */
  public static function findTestsByPath($paths) {
    $r = array();
    $testClasses = self::findTestClasses($paths);
    foreach ($testClasses as $testFile => $testClass) {
      $clazz = new \ReflectionClass($testClass);
      foreach ($clazz->getMethods() as $method) {
        if (preg_match('/^test/', $method->name)) {
          $r[] = array(
            'file' => $testFile,
            'class' => $testClass,
            'method' => $method->name,
          );
        }
      }
    }
    return $r;
  }

}
