<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>
<div class="civicrm-setup-header">
  <p><strong><?php echo ts('Version %1', array(1 => "{$civicrm_version} {$model->cms}")); ?></strong></p>
  <div class="civicrm-logo">
    <img src=<?php echo $installURLPath . "updated-logo.jpg"?> />
  </div>
</div>
<noscript>
<p class="error"><?php echo ts("Error: Javascipt appears to be disabled. The CiviCRM web-based installer requires Javascript.");?></p>
</noscript>
<h2><?php echo ts("CiviCRM Installer"); ?></h2>
<p class="crm-slide-effect"><?php echo ts("Thanks for choosing CiviCRM! You're nearly there!") . ' ' . ts("Please follow the instructions below to install CiviCRM."); ?> &#x1f331;</p>
