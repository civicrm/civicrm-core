<?php \Civi\Setup::assertRunning(); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $short_lang_code; ?>" lang="<?php echo $short_lang_code; ?>" dir="<?php echo $text_direction; ?>">
<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
  <title><?php echo ts('CiviCRM Installer'); ?></title>
  <script type="text/javascript" src="<?php echo $ctrl->getUrl('jquery.js'); ?>"></script>
  <script type="text/javascript">
    window.csj$ = jQuery.noConflict();
  </script>
  <link rel="stylesheet" type="text/css" href=<?php echo $installURLPath . "template.css"?> />
  <link rel="stylesheet" type="text/css" href=<?php echo $ctrl->getUrl('font-awesome.css') ?> />
<?php
if ($text_direction == 'rtl') {
  echo "  <link rel='stylesheet' type='text/css' href='{$installURLPath}template-rtl.css' />\n";
}
?>
</head>
<body>

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
</body>
</html>
