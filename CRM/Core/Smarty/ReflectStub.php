<?php

class CRM_Core_Smarty_ReflectStub {

  private $smarty;

  /**
   * @param \CRM_Core_Smarty $scope
   */
  public function __construct($scope) {
    $this->smarty = $scope;
  }

  /**
   * Lookup a Smarty variable.
   *
   * @param string $name
   * @param mixed|null $default
   * @return mixed|null
   *   The value of the Smarty variable, or else the $default.
   */
  public function get($name, $default = NULL) {
    $all = $this->smarty->get_template_vars();
    return $all[$name] ?? $default;
  }

}
