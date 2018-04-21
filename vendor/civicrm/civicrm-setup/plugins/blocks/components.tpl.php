<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n"); endif; ?>
<h2 id="components"><?php echo ts('Components'); ?></h2>

<div>
  <?php foreach ($model->getField('components', 'options') as $comp => $label): ?>
    <input class="comp-cb sr-only" style="display: none;" type="checkbox" name="civisetup[components][<?php echo $comp; ?>]" id="civisetup[components][<?php echo $comp; ?>]" <?php echo in_array($comp, $model->components) ? 'checked' : '' ?>>
    <label class="comp-box" for="civisetup[components][<?php echo $comp; ?>]">
      <span class="comp-label"><?php echo $label; ?></span>
      <span class="comp-desc"><?php echo $_tpl_block['component_labels'][$comp] ?></span>
    </label>
  <?php endforeach; ?>
</div>

<p class="tip">
  <strong><?php echo ts('Tip'); ?></strong>:
  <?php echo ts('Not sure? That\'s OK. After installing, you can enable and disable components at any time.'); ?>
</p>
