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
 * AutoService is a base-class for defining a service (in the service-container).
 * Classes which extend AutoService will have these characteristics:
 *
 * - The class is scanned automatically (if you enable `scan-classes@1`).
 * - The class is auto-registered as a service in Civi's container.
 * - The service is given a default name (derived from the class name).
 * - The service may subscribe to events (via `HookInterface` or `EventSubscriberInterface`).
 *
 * Additionally, the class will be scanned for various annotations:
 *
 * - Class annotations:
 *     - `@service <service.name>`: Customize the service name.
 *     - `@serviceTags <tag1,tag2>`: Declare additional tags for the service.
 * - Property annotations
 *     - `@inject [<service.name>]`: Inject another service automatically (by assigning this property).
 *       If the '<service.name>' is blank, then it loads an eponymous service.
 * - Method annotations
 *     - (TODO) `@inject <service.name>`: Inject another service automatically (by calling the setter-method).
 *
 * Note: Like other services in the container, AutoService cannot meaningfully subscribe to
 * early/boot-critical events such as `hook_entityTypes` or `hook_container`. However, you may
 * get a similar effect by customizing the `buildContainer()` method.
 */
abstract class AutoService implements AutoServiceInterface {

  use AutoServiceTrait;

}
