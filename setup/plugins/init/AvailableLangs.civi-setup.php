<?php
/**
 * @file
 *
 * Build a list of available translations.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    /**
     * @var \Civi\Setup\Model $m
     */
    $m = $e->getModel();

    $langs = NULL;
    require implode(DIRECTORY_SEPARATOR, [$m->srcPath, 'setup', 'res', 'languages.php']);
    $m->setField('lang', 'options', $langs);

  }, \Civi\Setup::PRIORITY_PREPARE);
