<?php
namespace Civi\Core;
use Hateoas\UrlGenerator\UrlGeneratorInterface;

class RestUrlGenerator implements UrlGeneratorInterface {
  /**
   * @param string  $routeName
   * @param array   $parameters
   * @param boolean $absolute
   *
   * @return string
   */
  public function generate($routeName, array $parameters, $absolute = FALSE) {
    $parts = explode('_', $routeName);
    $action = array_pop($parts);
    $entity = implode('_', $parts);

    if (\CRM_Utils_Rule::positiveInteger($parameters['id'])) {
      return \CRM_Utils_System::url("civicrm/rest/$entity/" . $parameters['id'], NULL, $absolute);
    }
    else {
      throw new \CRM_Core_Exception("Failed to generate URL for invalid ID");
    }
  }
}