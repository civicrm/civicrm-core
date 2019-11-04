<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\FormDataModel;
use Civi\Api4\Generic\Result;

/**
 * Shared functionality for form submission processing.
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
   * @var \Civi\Afform\FormDataModel
   *   List of entities declared by this form.
   */
  protected $_formDataModel;

  public function _run(Result $result) {
    // This will throw an exception if the form doesn't exist
    $this->_afform = (array) civicrm_api4('Afform', 'get', ['checkPermissions' => FALSE, 'where' => [['name', '=', $this->name]]], 0);
    $this->_formDataModel = FormDataModel::create($this->_afform['layout']);
    $this->validateArgs();
    $result->exchangeArray($this->processForm());
  }

  /**
   * Strip out arguments that are not allowed on this form
   */
  protected function validateArgs() {
    $rawArgs = $this->args;
    $entities = $this->_formDataModel->getEntities();
    $this->args = [];
    foreach ($rawArgs as $arg => $val) {
      if (!empty($entities[$arg]['url-autofill'])) {
        $this->args[$arg] = $val;
      }
    }
  }

  /**
   * @return array
   */
  abstract protected function processForm();

}
