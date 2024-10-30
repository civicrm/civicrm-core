<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
namespace Civi\Core\Service;

/**
 * AutoService is a base-class for defining a service (in Civi's service-container).
 * Child classes are scanned automatically for various annotations and interfaces.
 *
 * = ANNOTATIONS =
 *
 * Annotations are used to declare and initialize services:
 *
 * - Declare a service. This can be done on a `class` or static factory-method. Supported annotations:
 *     - `@service NAME`: Set the name of the new service.
 *     - `@serviceTags TAG-1,TAG-2`: Declare additional tags for the service.
 *     - `@internal`: Generate a hidden/automatic name. Limit discovery of the service.
 * - Initialize the service by calling methods. This works with factory-, constructor-, and setter-methods.
 *     - `@inject SERVICE-1,SERVICE-2`: Call the method automatically. Pass a list of other services as parameters.
 * - Initialize the service by assigning properties. Any property may have these annotations:
 *     - `@inject SERVICE`: Lookup the SERVICE and set the property.
 *       If the `SERVICE` is blank, then it loads an eponymous service.
 *
 * For examples of using these annotations, consult the tests or the Developer Guide:
 *
 * @see \Civi\Core\Service\AutoDefinitionTest
 * @link https://docs.civicrm.org/dev/en/latest/framework/services/
 *
 * = INTERFACES =
 *
 * Additionally, some `interface`s will be detected automatically. If an AutoService implements
 * any of these interfaces, then it will be registered with appropriate parties:
 *
 * @see \Civi\Core\HookInterface
 * @see \Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface
 * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface
 *
 * = REQUIREMENTS / LIMITATIONS =
 *
 * - To scan an extension, one must use `<mixin>scan-classes@1.0.0</mixin>` or `hook_scanClasses`.
 * - AutoServices are part of the container. They cannot be executed until the container has
 *   started. Consequently, the services cannot subscribe to some early/boot-time events
 *   (eg `hook_entityTypes` or `hook_container`).
 */
abstract class AutoService implements AutoServiceInterface {

  use AutoServiceTrait;

}
