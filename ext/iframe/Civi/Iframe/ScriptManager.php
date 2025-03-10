<?php

namespace Civi\Iframe;

use Civi\Core\HookInterface;
use Civi\Core\Service\AutoService;
use CRM_Iframe_ExtensionUtil as E;

/**
 * Manage the iframe entry-point script.
 *
 * @service iframe.script
 */
class ScriptManager extends AutoService implements HookInterface {

  /**
   * The symbolic of the system status-check which monitors the script.
   */
  const CHECK_NAME = 'iframeInstall';

  /**
   * @var \Civi\Iframe\Iframe
   * @inject
   */
  protected $iframe;

  /**
   * @see \CRM_Utils_Hook::check()
   */
  public function hook_civicrm_check(&$messages, $statusNames = [], $includeDisabled = FALSE): void {
    if ($statusNames && !in_array(static::CHECK_NAME, $statusNames)) {
      return;
    }

    if (CIVICRM_UF === 'WordPress') {
      // WP doesn't require installing a separate `/iframe.php`. Instead, it uses `?_cvwpif=1`.
      return;
    }

    $path = $this->getPath();
    $template = $this->iframe->getTemplate();

    if (!class_exists($template)) {
      $messages[] = new \CRM_Utils_Check_Message(
        static::CHECK_NAME,
        ts('The IFrame Connector cannot be deployed. The template class ("<code>%1</code>") is missing.', [1 => htmlentities($template)]),
        ts('IFrame Connector: Deployment'),
        \Psr\Log\LogLevel::ERROR,
        'fa-download'
      );
      return;
    }

    if (file_exists($path) && file_get_contents($path) === $this->render($template)) {
      return;
    }

    $tsVars = [
      1 => htmlentities(basename($this->getPath())),
    ];
    $message = new \CRM_Utils_Check_Message(
      static::CHECK_NAME,
      file_exists($path)
        ? ts('The IFrame Connector requires a utility script ("<code>%1</code>"). This script needs to be updated.', $tsVars)
        : ts('The IFrame Connector requires a utility script ("<code>%1</code>"). This script needs to be installed.', $tsVars),
      ts('IFrame Connector: Deployment'),
      \Psr\Log\LogLevel::ERROR,
      'fa-download'
    );
    if ($this->isInstallable()) {
      $message->addAction(ts('Deploy now'), FALSE, 'api4', ['Iframe', 'installScript']);
    }
    $message->addAction(ts('Deploy instructions'), FALSE, 'href', ['path' => 'civicrm/admin/iframe/install', 'query' => 'reset=1']);
    $messages[] = $message;
  }

  public function isInstallable(): bool {
    $path = $this->getPath();
    $parent = dirname($path);
    if (file_exists($path) && is_writable($path)) {
      return TRUE;
    }
    if (!file_exists($path) && file_exists($parent) &&  is_writable($parent)) {
      return TRUE;
    }
    return FALSE;
  }

  public function install(): void {
    $content = $this->render($this->iframe->getTemplate());
    $path = $this->getPath();
    if (!file_put_contents($path, $content)) {
      throw new \CRM_Core_Exception("Failed to install $path");
    }
  }

  /**
   * Generate the literal code for the entry-point script.
   *
   * @param string $sourceClass
   *  Ex: 'Civi\Iframe\EntryPoint\Drupal'
   * @return string
   */
  public function render(string $sourceClass): string {
    $meta = [
      'uf' => CIVICRM_UF,
      'sourceClass' => $sourceClass,
      'extPath' => static::relativize(E::path(), dirname($this->getPath())),
      'scriptUrl' => (string) \Civi::url('iframe://'),
    ];

    $metaCode =
      "// This file is auto-generated by the CiviCRM `iframe` extension.\n"
      . sprintf("\$GLOBALS['CIVICRM_IFRAME_META'] = %s;\n", var_export($meta, 1))
      . sprintf("if (!empty(\$GLOBALS['CIVICRM_IFRAME_READ'])) {\n  return \$GLOBALS['CIVICRM_IFRAME_META'];\n}\n");

    $class = new \ReflectionClass($sourceClass);
    $classCode = file_get_contents($class->getFileName());

    if (!str_contains($classCode, '//TEMPLATE:START') || !str_contains($classCode, '//TEMPLATE:END')) {
      throw new \RuntimeException("Template class ($sourceClass) is malformed. Missing TEMPLATE flags.");
    }

    return strtr($classCode, [
      '//TEMPLATE:START' => $metaCode,
      '//TEMPLATE:END' => sprintf("%s::main();\n", $class->getShortName()),
    ]);
  }

  public function getCurrent(): ?string {
    $path = $this->getPath();
    return file_exists($path) ? file_get_contents($path) : NULL;
  }

  /**
   * Read metadata about the existing script.
   *
   * @return array|null
   */
  public function getMeta(): ?array {
    $path = $this->getPath();
    if (!file_exists($path)) {
      return NULL;
    }

    try {
      $GLOBALS['CIVICRM_IFRAME_READ'] = TRUE;
      return (include $this->getPath());
    }
    finally {
      unset($GLOBALS['CIVICRM_IFRAME_READ']);
    }
  }

  /**
   * Convert the absolute path of $target to a relative path (as seen from $base).
   * This variant will attempt to use `../`.
   *
   * @param string $target
   * @param string $base
   * @return string
   */
  protected function relativize(string $target, string $base): string {
    $prefix = '..' . DIRECTORY_SEPARATOR;
    while ($base && !\CRM_Utils_File::isChildPath($base, $target)) {
      $base = dirname($base);
    }
    $relPath = \CRM_Utils_File::relativize($target, $base . DIRECTORY_SEPARATOR);
    return $prefix . $relPath;
  }

  /**
   * Get the local path to the IFRAME entry-point.
   *
   * @return string
   */
  public function getPath(): string {
    return \Civi::paths()->getVariable('civicrm.iframe', 'path');
  }

}
