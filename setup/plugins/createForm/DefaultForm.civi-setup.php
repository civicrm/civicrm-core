<?php
/**
 * @file
 *
 * Generate the default web form.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setupui.construct', function (\Civi\Setup\UI\Event\UIConstructEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Construct default UI', basename(__FILE__)));

    $e->setCtrl(new \Civi\Setup\UI\SetupController(\Civi\Setup::instance()));

  }, \Civi\Setup::PRIORITY_PREPARE);
