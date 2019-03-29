<?php
namespace Civi\Test\CiviEnvBuilder;
class ExtensionsStep implements StepInterface {
  private $action;
  private $names;

  /**
   * ExtensionStep constructor.
   * @param string $action
   *   Ex: 'install', 'uninstall'.
   * @param string|array $names
   */
  public function __construct($action, $names) {
    $this->action = $action;
    $this->names = (array) $names;
  }

  public function getSig() {
    return 'ext:' . implode(',', $this->names);
  }

  public function isValid() {
    if (!in_array($this->action, ['install', 'uninstall'])) {
      return FALSE;
    }
    foreach ($this->names as $name) {
      if (!is_string($name)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function run($ctx) {
    $allKeys = \CRM_Extension_System::singleton()->getFullContainer()->getKeys();
    $names = \CRM_Utils_String::filterByWildcards($this->names, $allKeys, TRUE);

    $manager = \CRM_Extension_System::singleton()->getManager();
    switch ($this->action) {
      case 'install':
        $manager->install($names);
        break;

      case 'uninstall':
        $manager->disable($names);
        $manager->uninstall($names);
        break;
    }
  }

}
