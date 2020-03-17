<?php
/**
 * @file
 *
 * Display a block with opt-in settings.
 *
 * The file `opt-in.disabled.php` is ignored by default. To enable, you should
 * either rename it to `opt-in.civi-setup.php` or symlink it.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

// First pass: initialize 'settings' block.
\Civi\Setup::dispatcher()
  ->addListener('civi.setupui.boot', function (\Civi\Setup\UI\Event\UIBootEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Register blocks', basename(__FILE__)));

    $e->getCtrl()->blocks['opt-in'] = array(
    // FIXME
      'is_active' => TRUE,
      'file' => __DIR__ . DIRECTORY_SEPARATOR . 'opt-in.tpl.php',
      'class' => 'if-no-errors',
      'weight' => 55,
    );
  }, \Civi\Setup::PRIORITY_PREPARE);

// Second pass: Parse any settings that have been approved for use in this form.
\Civi\Setup::dispatcher()
  ->addListener('civi.setupui.boot', function (\Civi\Setup\UI\Event\UIBootEvent $e) {
    if (!$e->getCtrl()->blocks['opt-in']['is_active']) {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Parse inputs', basename(__FILE__)));
    $values = $e->getField('opt-in', array());
    $e->getModel()->extras['opt-in']['empoweredBy'] = !empty($values['empoweredBy']);
    $e->getModel()->extras['opt-in']['versionCheck'] = !empty($values['versionCheck']);

     // echo '<pre>'; print_r(['model'=> $e->getModel()->getValues(), 'v'=>$values]); echo '</pre>';

  }, \Civi\Setup::PRIORITY_LATE);

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    $m = $e->getModel();

    if (isset($m->extras['opt-in']['empoweredBy'])) {
      \Civi\Setup::log()->info(sprintf('[%s] Set empoweredBy', basename(__FILE__)));
      \Civi::settings()->set('empoweredBy', (bool) $m->extras['opt-in']['empoweredBy']);
    }

    if (isset($m->extras['opt-in']['versionCheck'])) {
      \Civi\Setup::log()->info(sprintf('[%s] Set versionCheck', basename(__FILE__)));
      \CRM_Core_DAO::executeQuery('UPDATE civicrm_job SET is_active = %1 WHERE api_entity LIKE "job" AND api_action LIKE "version_check"', array(
        1 => array($m->extras['opt-in']['versionCheck'] ? 1 : 0, 'Int'),
      ));
    }
  }, \Civi\Setup::PRIORITY_LATE + 100);
