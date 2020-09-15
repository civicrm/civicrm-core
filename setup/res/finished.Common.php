<?php \Civi\Setup::assertRunning(); ?>
<?php
$registerSiteURL = "https://civicrm.org/register-site";
echo "<li>" . ts("Have you registered this site at CiviCRM.org? If not, please help strengthen the CiviCRM ecosystem by taking a few minutes to <a %1>fill out the site registration form</a>. The information collected will help us prioritize improvements, target our communications and build the community. If you have a technical role for this site, be sure to check Keep in Touch to receive technical updates (a low volume mailing list).", array(1 => "href='$registerSiteURL' target='_blank'")) . "</li>";
echo "<li>" . ts("We have integrated KCFinder with CKEditor and TinyMCE. This allows a user to upload images. All uploaded images are public.") . "</li>";
