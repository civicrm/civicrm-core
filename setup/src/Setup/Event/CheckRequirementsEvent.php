<?php
namespace Civi\Setup\Event;

/**
 * Check if the local system meets the installation requirements.
 *
 * Event Name: 'civi.setup.checkRequirements'
 */
class CheckRequirementsEvent extends BaseSetupEvent {

  protected $messages;

  /**
   * Whether the page requires a reload, such as after downloading translation files.
   *
   * @var bool
   */
  protected $isReloadRequired = FALSE;

  /**
   * @param string $severity
   *   Severity/level.
   *   Ex: 'info', 'warning', 'error'.
   * @param string $section
   *   Symbolic machine name for this group of messages.
   *   Ex: 'database' or 'system'.
   * @param string $name
   *   Symbolic machine name for this particular message.
   *   Ex: 'mysqlThreadstack'
   * @param string $message
   *   Displayable explanation.
   *   Ex: 'The MySQL thread stack is too small.'
   * @return $this
   */
  public function addMessage($severity, $section, $name, $message) {
    $this->messages[$name] = array(
      'section' => $section,
      'name' => $name,
      'message' => $message,
      'severity' => $severity,
    );
    return $this;
  }

  public function addInfo($section, $name, $message = '') {
    return $this->addMessage('info', $section, $name, $message);
  }

  public function addError($section, $name, $message = '') {
    return $this->addMessage('error', $section, $name, $message);
  }

  public function addWarning($section, $name, $message = '') {
    return $this->addMessage('warning', $section, $name, $message);
  }

  /**
   * @param string|NULL $severity
   *   Filter by severity of the message.
   *   Ex: 'info', 'error', 'warning'.
   * @return array
   *   List of messages. Each has fields:
   *     - name: string, symbolic name.
   *     - message: string, displayable message.
   *     - severity: string, ex: 'info', 'warning', 'error'.
   */
  public function getMessages($severity = NULL) {
    if ($severity === NULL) {
      return $this->messages;
    }
    else {
      return array_filter($this->messages, function ($m) use ($severity) {
        return $m['severity'] == $severity;
      });
    }
  }

  public function getInfos() {
    return $this->getMessages('info');
  }

  public function getErrors() {
    return $this->getMessages('error');
  }

  public function getWarnings() {
    return $this->getMessages('warning');
  }

  public function isReloadRequired($isReloadRequired = FALSE) {
    if ($isReloadRequired) {
      $this->isReloadRequired = $isReloadRequired;
    }
    return $this->isReloadRequired;
  }

}
