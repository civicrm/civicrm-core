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

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The CiviCRM container will automatically load classes that implement
 * AutoServiceInterface.
 *
 * Formally, this resembles `hook_container` and `CompilerPassInterface`. However, the
 * build method must be `static` (running before CiviCRM has fully booted), and downstream
 * implementations should generally register concrete services (rather performing meta-services
 * like tag-evaluation).
 */
interface AutoServiceInterface {

  /**
   * Register any services with the container.
   */
  public static function buildContainer(ContainerBuilder $container): void;

}
