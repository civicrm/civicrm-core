<?php
/**
 * @file
 *
 * Enable Riverlea by default on Standalone installs.
 * - this does not *select* a Riverlea theme but removes one step for doing so
 * - we set dark_mode setting to inherit instead of always light (which is the default for other CMS)
 *
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'Standalone') {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    $e->getModel()->extensions[] = 'riverlea';
    $e->getModel()->settings['riverlea_dark_mode_frontend'] = 'inherit';
    $e->getModel()->settings['riverlea_dark_mode_backend'] = 'inherit';
  });
