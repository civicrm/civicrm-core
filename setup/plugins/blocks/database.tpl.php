<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>

<h2><?php echo ts('Database'); ?></h2>

<p style="margin-left: 2em">
  <label for="server"><span><?php echo ts('Server:'); ?></span></label>
  <input type="text" id="dbServer" name="civisetup[db][server]" value="<?php echo htmlentities($model->db['server'] ?? '') ?>">
</p>

<p style="margin-left: 2em">
  <label for="database"><span><?php echo ts('Database:'); ?></span></label>
  <input type="text" id="dbDatabase" name="civisetup[db][database]" value="<?php echo htmlentities($model->db['database'] ?? '') ?>">
</p>

<p style="margin-left: 2em">
  <label for="username"><span><?php echo ts('Username:'); ?></span></label>
  <input type="text" id="dbUsername" name="civisetup[db][username]" value="<?php echo htmlentities($model->db['username'] ?? '') ?>">
</p>

<p style="margin-left: 2em">
  <label for="password"><span><?php echo ts('Password:'); ?></span></label>
  <input type="password" id="dbPassword" name="civisetup[db][password]" value="<?php echo htmlentities($model->db['password'] ?? '') ?>">
</p>
