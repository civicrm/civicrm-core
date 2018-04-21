<?php
/**
 * @file
 *
 * This is an example plugin which manipulates the installation options.
 *
 * Note: The filename `Example.disabled.php` indicates that the example is
 * a disabled. A real plugin must end in `*.civi-setup.php`.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    // Override the default list of Civi components.
    $e->getModel()->components = array('CiviEvent', 'CiviContribute', 'CiviMember', 'CiviMail');

    // Activate some extensions during installation.
    $e->getModel()->extensions[] = 'org.civicrm.flexmailer';

    // Manipulate some settings during installation.
    $e->getModel()->settings['max_attachments'] = 10;
  });
