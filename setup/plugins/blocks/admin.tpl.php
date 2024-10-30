<?php if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
} ?>

<?php if (empty($reqs->getErrors())) : ?>

<h2><?php echo ts('Administrator Account'); ?></h2>

<div class="civicrm-setup-field-wrapper">
  <label for="adminUser"><span><?php echo ts('Administrative User:'); ?></span></label>
  <input type="text" id="adminUser" name="civisetup[adminUser]" required value="<?php echo htmlentities($model->extras['adminUser'] ?? '') ?>">
</div>

<div class="civicrm-setup-field-wrapper">
  <label for="adminPass"><span><?php echo ts('Administrative Password:'); ?></span></label>
  <input type="password" id="adminPass" name="civisetup[adminPass]" required />
    <?php if (!empty($model->extras['adminPass'])) {
      echo '<div>';
      echo ts('Suggestion: <code>%1</code>', [1 => $model->extras['adminPass']]);
      echo '</div>';
    } ?>
</div>

<div class="civicrm-setup-field-wrapper">
  <label for="adminEmail"><span><?php echo ts('Administrative Email:'); ?></span></label>
  <input type="email" required id="adminEmail" name="civisetup[adminEmail]" value="<?php htmlspecialchars($model->extras['adminEmail'] ?? '');?>" />
</div>

<?php endif;
