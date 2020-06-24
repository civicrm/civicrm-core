<?php
namespace Civi\Setup\Event;

use Symfony\Component\EventDispatcher\Event;

class BaseSetupEvent extends Event {

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
