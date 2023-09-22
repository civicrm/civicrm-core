<?php \Civi\Setup::assertRunning(); ?>
<?php
$mainClasses = array(
  'civicrm-setup-body',
  count($reqs->getErrors()) ? 'has-errors' : '',
  count($reqs->getWarnings()) ? 'has-warnings' : '',
  (count($reqs->getErrors()) + count($reqs->getWarnings()) > 0) ? 'has-problems' : '',
  (count($reqs->getErrors()) + count($reqs->getWarnings()) === 0) ? 'has-no-problems' : '',
);
?>

<div class="<?php echo implode(' ', $mainClasses); ?>">
<div id="All">

<form name="civicrm_form" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">

  <?php echo $ctrl->renderBlocks($_tpl_params); ?>

</form>
</div>
</div>
