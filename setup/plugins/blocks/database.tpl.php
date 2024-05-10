<?php if (!defined('CIVI_SETUP')) :
  exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>

<h2><?php echo ts('Database'); ?></h2>
<div class="civicrm-setup-field-wrapper">
  <label for="server"><span><?php echo ts('Server:'); ?></span></label>
  <input type="text" id="dbServer" name="civisetup[db][server]" value="<?php echo htmlentities($model->db['server'] ?? ''); ?>" >
</div>

<div class="civicrm-setup-field-wrapper">
  <label for="database"><span><?php echo ts('Database:'); ?></span></label>
  <input type="text" id="dbDatabase" name="civisetup[db][database]" value="<?php echo htmlentities($model->db['database'] ?? ''); ?>">
</div>

<div class="civicrm-setup-field-wrapper">
  <label for="username"><span><?php echo ts('Username:'); ?></span></label>
  <input type="text" id="dbUsername" name="civisetup[db][username]" value="<?php echo htmlentities($model->db['username'] ?? ''); ?>">
</div>

<div class="civicrm-setup-field-wrapper">
    <label for="password"><span><?php echo ts('Password:'); ?></span></label>
    <?php if ($model->db['password_preset'] ?? false) : ?>
        <i><?php echo ts('Your database password has been preset in your settings files or environment variables.'); ?></i>
    <?php else : ?>
        <input type="password" id="dbPassword" name="civisetup[db][password]" value="<?php echo htmlentities($model->db['password'] ?? ''); ?>">
    <?php endif; ?>
</div>
