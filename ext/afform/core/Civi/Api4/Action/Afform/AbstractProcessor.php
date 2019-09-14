<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\Utils;
use Civi\Api4\Generic\Result;

/**
 * Class Prefill
 * @package Civi\Api4\Action\Afform
 */
abstract class AbstractProcessor extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Form name
   * @var string
   * @required
   */
  protected $name;

  /**
   * Arguments present when loading the form
   * @var array
   */
  protected $args;

  protected $_afform;

  /**
   * @var array
   *   List of entities declared by this form.
   */
  protected $_afformEntities;

  public function _run(Result $result) {
    // This will throw an exception if the form doesn't exist
    $this->_afform = (array) civicrm_api4('Afform', 'get', ['checkPermissions' => FALSE, 'where' => [['name', '=', $this->name]]], 0);
    $this->_afformEntities = Utils::getEntities($this->_afform['layout']);
    $this->validateArgs();
    $result->exchangeArray($this->processForm());
  }

  /**
   * Strip out arguments that are not allowed on this form
   */
  protected function validateArgs() {
    $rawArgs = $this->args;
    $this->args = [];
    foreach ($rawArgs as $arg => $val) {
      if (!empty($this->_afformEntities[$arg]['af-url-autofill'])) {
        $this->args[$arg] = $val;
      }
    }
  }

  /**
   * @return array
   */
  abstract protected function processForm();

}
