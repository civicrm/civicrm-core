<?php
namespace Civi\Setup\UI\Event;

use Civi\Setup\Event\BaseSetupEvent;
use Civi\Setup\Event\SetupControllerInterface;
use Civi\Setup\UI\SetupController;

/**
 * Create a web-based UI for handling the installation.
 *
 * Event Name: 'civi.setupui.construct'
 */
class UIConstructEvent extends BaseSetupEvent {

  protected $ctrl;

  /**
   * @return SetupControllerInterface
   */
  public function getCtrl() {
    return $this->ctrl;
  }

  /**
   * @param SetupControllerInterface $ctrl
   */
  public function setCtrl($ctrl) {
    $this->ctrl = $ctrl;
  }

}
