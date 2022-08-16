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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

class CRM_Api4_Services {

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   */
  public static function hook_container($container) {
    $loader = new XmlFileLoader($container, new FileLocator(dirname(dirname(__DIR__))));
    $loader->load('Civi/Api4/services.xml');

    $container->getDefinition('civi_api_kernel')->addMethodCall(
      'registerApiProvider',
      [new Reference('action_object_provider')]
    );
  }

}
