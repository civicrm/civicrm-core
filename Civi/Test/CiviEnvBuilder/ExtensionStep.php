<?php
namespace Civi\Test\CiviEnvBuilder;
class ExtensionStep implements StepInterface {
  private $name;

  /**
   * ExtensionStep constructor.
   * @param $name
   */
  public function __construct($name) {
    $this->name = $name;
  }

  public function getSig() {
    return 'ext:' . $this->name;
  }

  public function isValid() {
    return is_string($this->name);
  }

  public function run($ctx) {
    \CRM_Extension_System::singleton()->getManager()->install(array(
      $this->name,
    ));
  }

}
