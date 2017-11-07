<?php
namespace Civi\Angular;

interface ChangeSetInterface {

  /**
   * Get the symbolic name of the changeset.
   *
   * @return string
   */
  public function getName();

  /**
   * Declare that $module requires additional dependencies.
   *
   * @param string $module
   * @param string|array $dependencies
   * @return ChangeSet
   */
  public function requires($module, $dependencies);

  /**
   * Declare a change to HTML.
   *
   * @param string $file
   *   A file name, wildcard, or regex.
   *   Ex: '~/crmHello/intro.html' (filename)
   *   Ex: '~/crmHello/*.html' (wildcard)
   *   Ex: ';(Edit|List)Ctrl\.html$;' (regex)
   * @param callable $callback
   *   Function which accepts up to two parameters:
   *    - phpQueryObject $doc
   *    - string $path
   * @return ChangeSet
   */
  public function alterHtml($file, $callback);

}
