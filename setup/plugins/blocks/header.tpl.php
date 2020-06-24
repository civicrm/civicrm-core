<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>
<div class="civicrm-setup-header">
    <div class="title">
        <h1><?php echo ts("Thanks for choosing CiviCRM. You're nearly there!"); ?><hr></h1>
    </div>
    <div class="civicrm-logo"><strong><?php echo ts('Version %1', array(1 => "{$civicrm_version} {$model->cms}")); ?></strong>
        <span><img src=<?php echo $installURLPath . "updated-logo.jpg"?> /></span>
     </div>
</div>
<h2><?php echo ts("CiviCRM Installer"); ?></h2>

<noscript>
<p class="error"><?php echo ts("Error: Javascipt appears to be disabled. The CiviCRM web-based installer requires Javascript.");?></p>
</noscript>

<p><?php echo ts("Thanks for choosing CiviCRM! Please follow the instructions below to install CiviCRM."); ?></p>
