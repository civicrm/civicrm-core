<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n"); endif; ?>
<div class="civicrm-logo"><strong><?php echo ts('Version %1', array(1 => "{$civicrm_version} {$model->cms}")); ?></strong><br/>
    <span><img src=<?php echo $installURLPath . "block_small.png"?> /></span>
  </div>

<h1><?php echo ts("CiviCRM Installer"); ?></h1>

<noscript>
<p class="error"><?php echo ts("Error: Javascipt appears to be disabled. The CiviCRM web-based installer requires Javascript.");?></p>
</noscript>

<p><?php echo ts("Thanks for choosing CiviCRM! Please follow the instructions below to install CiviCRM."); ?></p>
