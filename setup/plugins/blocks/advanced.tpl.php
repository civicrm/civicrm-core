<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>
<h2 id="environment"><?php echo ts('Environment'); ?></h2>

<p>
  <?php echo ts('The system settings were auto-detected. CiviCRM will be installed with:'); ?>
</p>

<div style="">
  <table class="settingsTable">
    <tbody>
    <?php if ($model->cms !== 'Standalone'): ?>
    <tr>
      <th><?php echo ts('CMS Database'); ?></th>
      <td>
        <code><?php echo htmlentities(\Civi\Setup\DbUtil::encodeDsn(array_merge($model->cmsDb, ['password' => 'HIDDEN']))); ?></code>
      </td>
    </tr>
    <tr>
      <th><?php echo ts('CiviCRM Database'); ?></th>
      <td class="advanced-db">
        <div class="ro">
          <code><?php echo htmlentities(\Civi\Setup\DbUtil::encodeDsn(array_merge($model->db, ['password' => 'HIDDEN']))); ?></code>
          <a href="" onclick="csj$('.advanced-db .ro').hide(); csj$('.advanced-db .rw').show(); return false;" title="<?php echo htmlentities(ts('Edit')) ?>"><i class="fa fa-pencil"></i></a>
        </div>
        <div class="rw" style="display: none;">
          <div>

          <input type="text" name="civisetup[advanced][db]" value="<?php echo htmlentities($model->extras['advanced']['db']); ?>" data-original="<?php echo htmlentities($model->extras['advanced']['db']); ?>">
          <button id="db_apply_button" type="submit" name="civisetup[action][Start]"><?php echo htmlentities(ts('Apply')); ?></button>
          <a href="" onclick="civisetupAdvancedDbCancel(); return false;" title="<?php echo htmlentities(ts('Cancel')) ?>"><i class="fa fa-close"></i></a>
          <script type="text/javascript">
            function civisetupAdvancedDbCancel() {
              csj$('.advanced-db .rw').hide();
              csj$('.advanced-db .ro').show();
              csj$('.advanced-db .rw input[type=text]').val(csj$('.advanced-db .rw input[type=text]').attr('data-original'));
            }
          </script>
          </div>
          <p><?php echo ts('By default, CiviCRM uses the same database as your website. You may install on a separate database if you need more fine-grained control over permissions, replication, hardware capacity, etc.'); ?></p>
          <p><?php echo ts('<strong>Example</strong>: <code>%1</code>', array(1 => 'mysql://admin:secret@localhost/civicrm')); ?></p>
          <p><?php echo ts('<strong>Example</strong>: <code>%1</code>', array(1 => 'mysql://admin:secret@127.0.0.1:3306/otherdb')); ?></p>
          <p><?php echo ts('<strong>Example</strong>: <code>%1</code>', array(1 => 'mysql://admin:secret@unix(/var/lib/mysql/mysql.sock)/otherdb')); ?></p>
          <p><?php echo ts('Tip: This uses URL notation. If the credentials require any special characters (e.g. "&" or "#"), then apply URL encoding (e.g. "%26" or "%23").'); ?></p>
        </div>
      </td>
    </tr>
    <?php endif; ?>
    <tr>
      <th><?php echo ts('CiviCRM Settings File'); ?></th>
      <td><code><?php echo htmlentities($model->settingsPath); ?></code></td>
    </tr>
    <tr>
      <th><?php echo ts('CiviCRM Source Code'); ?></th>
      <td><code><?php echo htmlentities($model->srcPath); ?></code></td>
    </tr>
    </tbody>
  </table>
</div>

<p class="tip">
  <strong><?php echo ts('Tip'); ?></strong>:
  <?php echo ts('Need more advanced control? You may alternatively use the <a href="%1" target="%2">command-line installer</a>.', array(1 => 'https://github.com/civicrm/cv', 2 => '_blank')); ?>
</p>
