<?php
namespace Civi\Setup\UI\Event;

use Civi\Setup\Event\BaseSetupEvent;

/**
 * Create a web-based UI for handling the installation.
 *
 * Event Name: 'civi.setupui.construct'
 */
class UIConstructEvent extends BaseSetupEvent {

  protected $ctrl;

  /**
   * @return \Civi\Setup\Event\SetupControllerInterface
   */
  public function getCtrl() {
    return $this->ctrl;
  }

  /**
   * @param \Civi\Setup\Event\SetupControllerInterface $ctrl
   */
  public function setCtrl($ctrl) {
    $this->ctrl = $ctrl;
  }

}
