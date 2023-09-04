<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>

<h2><?php echo ts('Administrator'); ?></h2>

<p style="margin-left: 2em">
  <label for="adminUser"><span><?php echo ts('Administrative User:'); ?></span></label>
  <input type="text" id="adminUser" name="civisetup[adminUser]" value="<?php echo htmlentities($model->extras['adminUser'] ?? '') ?>">
</p>

<p style="margin-left: 2em">
  <label for="adminPass"><span><?php echo ts('Administrative Password:'); ?></span></label>
  <input type="password" id="adminPass" name="civisetup[adminPass]" value="<?php echo htmlentities($model->extras['adminPass'] ?? '') ?>">
</p>
