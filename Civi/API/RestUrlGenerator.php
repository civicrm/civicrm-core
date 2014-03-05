<?php
namespace Civi\API;
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
    /** @var $apiRegistry \Civi\API\Registry */
    $apiRegistry = \Civi\Core\Container::singleton()->get('civi_api_registry');

    $parts = explode('_', $routeName);
    $action = array_pop($parts);
    $entity = $apiRegistry->getSlugByName(implode('_', $parts));

    if ($entity === NULL) {
      throw new \CRM_Core_Exception("Failed to generate URL for unknown route [$routeName]");
    }

    if (\CRM_Utils_Rule::positiveInteger($parameters['id'])) {
      return \CRM_Utils_System::url("civicrm/rest/$entity/" . $parameters['id'], NULL, $absolute);
    }
    else {
      throw new \CRM_Core_Exception("Failed to generate URL for invalid ID");
    }
  }
}