<?php \Civi\Setup::assertRunning(); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $shortLangCode; ?>" lang="<?php echo $shortLangCode; ?>" dir="<?php echo $textDirection; ?>">
<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
  <title><?php echo htmlentities($pageTitle); ?></title>
  <?php foreach ($pageAssets as $pageAsset) {
    switch ($pageAsset['type']) {
      case 'script-url':
        printf("<script type=\"text/javascript\" src=\"%s\"></script>", htmlentities($pageAsset['url']));
        break;

      case 'script-code':
        printf("<script type=\"text/javascript\">\n%s\n</script>", $pageAsset['code']);
        break;

      case 'style-url':
        printf("<link rel=\"stylesheet\" type=\"text/css\" href=\"%s\" />", htmlentities($pageAsset['url']));
        break;

      default:
        throw new \Exception("Unrecognized page asset: " . $pageAsset['type']);

    }
  } ?>
</head>
<body>

<?php echo $pageBody; ?>

</body>
</html>
