<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n"); endif; ?>
<h2 id="requirements"><?php echo ts('System Requirements'); ?></h2>

<?php
if (count($reqs->getErrors()) > 0):
  ?><p class="error"><?php echo ts('We are not able to install the software. Please review the errors and warnings below.'); ?></p><?php
elseif (count($reqs->getWarnings()) > 0):
  ?><p class="warning"><?php echo ts('There are some issues that we recommend you look at before installing. However, you are still able to install the software.'); ?></p><?php
else:
  ?><p class="good"><?php echo ts("You're ready to install!"); ?></p><?php
endif;
?>

<?php
$msgs = array_filter($reqs->getMessages(), function($m) {
  return $m['severity'] != 'info';
});
uasort($msgs, function($a, $b) {
  return strcmp(
    $a['severity'] . '-' . $a['section'] . '-' . $a['name'],
    $b['severity'] . '-' . $b['section'] . '-' . $b['name']
  );
});
?>

<table class="reqTable">
  <thead>
  <tr>
    <th width="10%"><?php echo ts('Severity'); ?></th>
    <th width="10%"><?php echo ts('Section'); ?></th>
    <th width="20%"><?php echo ts('Name'); ?></th>
    <th width="69%"><?php echo ts('Details'); ?></th>
  </tr>
  </thead>
  <tbody>
  <?php foreach ($msgs as $msg):?>
  <tr class="<?php echo 'reqSeverity-' . $msg['severity']; ?>">
    <td><?php echo htmlentities($_tpl_block['severity_labels'][$msg['severity']]); ?></td>
    <td><?php echo htmlentities(isset($_tpl_block['section_labels'][$msg['section']]) ? $_tpl_block['section_labels'][$msg['section']] : $msg['section']); ?></td>
    <td><?php echo htmlentities($msg['name']); ?></td>
    <td><?php echo htmlentities($msg['message']); ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div class="action-box">
  <input id="recheck_button" type="submit" name="civisetup[action][Start]" value="<?php echo htmlentities(ts('Refresh')); ?>" />
  <div class="advancedTip">
    <?php echo ts('After updating your system, refresh to test the requirements again.'); ?>
  </div>
</div>
