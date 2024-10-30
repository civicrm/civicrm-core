<?php
namespace Civi\Setup\Event;

use Civi\Core\Event\GenericHookEvent;

class BaseSetupEvent extends GenericHookEvent {

  /**
   * @var \Civi\Setup\Model
   */
  protected $model;

  /**
   * BaseSetupEvent constructor.
   * @param \Civi\Setup\Model $model
   */
  public function __construct(\Civi\Setup\Model $model) {
    $this->model = $model;
  }

  /**
   * @return \Civi\Setup\Model
   */
  public function getModel() {
    return $this->model;
  }

  /**
   * @param \Civi\Setup\Model $model
   */
  public function setModel($model) {
    $this->model = $model;
  }

}
