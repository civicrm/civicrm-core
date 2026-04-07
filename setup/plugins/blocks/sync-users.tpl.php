<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>

<h2><?php echo ts('Users'); ?></h2>

<p>
  <label for="syncUsers"><span><?php echo ts('Synchronize all existing users'); ?></span> <input id="syncUsers" type="checkbox" name="civisetup[syncUsers]" value=1 <?php echo $model->syncUsers ? "checked='checked'" : ""; ?> /></label> <br />
  <span class="advancedTip">
  <?php $cmsTitle = ($model->cms === 'Drupal8') ? 'Drupal' : $model->cms; ?>
  <?php echo ts("To help manage communication preferences, each \"<em>%1 User</em>\" should be internally linked to a \"<em>CiviCRM Contact</em>\".", [1 => $cmsTitle]); ?><br />
  </span>
</p>

<p class="tip">
  <strong><?php echo ts('Tip'); ?></strong>:
  <?php echo ts('You may skip this and run synchronization at any time in the future.'); ?>
</p>
