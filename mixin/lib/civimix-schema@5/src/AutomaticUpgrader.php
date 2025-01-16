<?php

namespace CiviMix\Schema;

use Civi\Test\Invasive;

/**
 * The "AutomaticUpgrader" will create and destroy the SQL tables
 * using schema files (`SchemaHelper`). It also calls-out to any custom
 * upgrade code (eg `CRM_Myext_Upgrader`).
 *
 * To simplify backport considerations, `AutomaticUpgrader` does not have formal name.
 * It is accessed via aliases like "CiviMix\Schema\*\AutomaticUpgrader".
 *
 * Target: CiviCRM v5.38+
 */
return new class() implements \CRM_Extension_Upgrader_Interface {

  use \CRM_Extension_Upgrader_IdentityTrait {

    init as initIdentity;

  }

  /**
   * Optionally delegate to "CRM_Myext_Upgrader" or "Civi\Myext\Upgrader".
   *
   * @var \CRM_Extension_Upgrader_Interface|null
   */
  private $customUpgrader;

  public function init(array $params) {
    $this->initIdentity($params);
    if ($info = $this->getInfo()) {
      if ($class = $this->getDelegateUpgraderClass($info)) {
        $this->customUpgrader = new $class();
        $this->customUpgrader->init($params);
        if ($errors = $this->checkDelegateCompatibility($this->customUpgrader)) {
          throw new \CRM_Core_Exception("AutomaticUpgrader is not compatible with $class:\n" . implode("\n", $errors));
        }
      }
    }
  }

  public function notify(string $event, array $params = []) {
    $info = $this->getInfo();
    if (!$info) {
      return;
    }

    if ($event === 'install') {
      $GLOBALS['CiviMixSchema']->getHelper($this->getExtensionKey())->install();
    }

    if ($this->customUpgrader) {
      $result = $this->customUpgrader->notify($event, $params);
      // for upgrade checks, we need to pass check results up to the caller
      // (for now - could definitely be more elegant!)
      if ($event === 'upgrade') {
        return $result;
      }
    }

    if ($event === 'uninstall') {
      $GLOBALS['CiviMixSchema']->getHelper($this->getExtensionKey())->uninstall();
    }
  }

  /**
   * Civix-based extensions have a conventional name for their upgrader class ("CRM_Myext_Upgrader"
   * or "Civi\Myext\Upgrader"). Figure out if this class exists.
   *
   * @param \CRM_Extension_Info $info
   * @return string|null
   *   Ex: 'CRM_Myext_Upgrader' or 'Civi\Myext\Upgrader'
   */
  public function getDelegateUpgraderClass(\CRM_Extension_Info $info): ?string {
    $candidates = [];

    if (!empty($info->civix['namespace'])) {
      $namespace = $info->civix['namespace'];
      $candidates[] = sprintf('%s_Upgrader', str_replace('/', '_', $namespace));
      $candidates[] = sprintf('%s\\Upgrader', str_replace('/', '\\', $namespace));
    }

    foreach ($candidates as $candidate) {
      if (class_exists($candidate)) {
        return $candidate;
      }
    }

    return NULL;
  }

  public function getInfo(): ?\CRM_Extension_Info {
    try {
      return \CRM_Extension_System::singleton()->getMapper()->keyToInfo($this->extensionName);
    }
    catch (\CRM_Extension_Exception_ParseException $e) {
      \Civi::log()->error("Parse error in extension " . $this->extensionName . ": " . $e->getMessage());
      return NULL;
    }
  }

  /**
   * @param \CRM_Extension_Upgrader_Interface $upgrader
   * @return array
   *   List of error messages.
   */
  public function checkDelegateCompatibility($upgrader): array {
    $class = get_class($upgrader);

    $errors = [];

    if (!($upgrader instanceof \CRM_Extension_Upgrader_Base)) {
      $errors[] = "$class is not based on CRM_Extension_Upgrader_Base.";
      return $errors;
    }

    // In the future, we will probably modify AutomaticUpgrader to build its own
    // sequence of revisions (based on other sources of data). AutomaticUpgrader
    // is only regarded as compatible with classes that strictly follow the standard revision-model.
    $methodNames = [
      'appendTask',
      'onUpgrade',
      'getRevisions',
      'getCurrentRevision',
      'setCurrentRevision',
      'enqueuePendingRevisions',
      'hasPendingRevisions',
    ];
    foreach ($methodNames as $methodName) {
      $method = new \ReflectionMethod($upgrader, $methodName);
      if ($method->getDeclaringClass()->getName() !== 'CRM_Extension_Upgrader_Base') {
        $errors[] = "To ensure future interoperability, AutomaticUpgrader only supports {$class}::{$methodName}()  if it's inherited from CRM_Extension_Upgrader_Base";
      }
    }

    return $errors;
  }

  public function __set($property, $value) {
    switch ($property) {
      // _queueAdapter() needs these properties.
      case 'ctx':
      case 'queue':
        if (!$this->customUpgrader) {
          throw new \RuntimeException("AutomaticUpgrader($this->extensionName): Cannot assign delegated property: $property (No custom-upgrader found)");
        }
        // "Invasive": unlike QueueTrait, we are not in the same class as the recipient. And we can't replace previously-published QueueTraits.
        Invasive::set([$this->customUpgrader, $property], $value);
        return;
    }

    throw new \RuntimeException("AutomaticUpgrader($this->extensionName): Cannot assign unknown property: $property");
  }

  public function __get($property) {
    switch ($property) {
      // _queueAdapter() needs these properties.
      case 'ctx':
      case 'queue':
        if (!$this->customUpgrader) {
          throw new \RuntimeException("AutomaticUpgrader($this->extensionName): Cannot read delegated property: $property (No custom-upgrader found)");
        }
        // "Invasive": Unlike QueueTrait, we are not in the same class as the recipient. And we can't replace previously-published QueueTraits.
        return Invasive::get([$this->customUpgrader, $property]);
    }
    throw new \RuntimeException("AutomaticUpgrader($this->extensionName): Cannot read unknown property: $property");
  }

  public function __call($name, $arguments) {
    if ($this->customUpgrader) {
      return call_user_func_array([$this->customUpgrader, $name], $arguments);
    }
    else {
      throw new \RuntimeException("AutomaticUpgrader($this->extensionName): Cannot delegate method $name (No custom-upgrader found)");
    }
  }

};
