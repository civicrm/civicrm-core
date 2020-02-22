<?php \Civi\Setup::assertRunning(); ?>
<?php
throw new \Exception("A draft copy of this file is available but has not been tested. Please edit " . __FILE__);

// FIXME: Compute URL's with backdrop functions (e.g. 'url(...)')
// FIXME: Just echo instead of doing $output silliness.
// FIXME: Use finished.Common.php instead of $commonOutputMessage.

$registerSiteURL = "https://civicrm.org/register-site";
$commonOutputMessage = "<li>" . ts("Have you registered this site at CiviCRM.org? If not, please help strengthen the CiviCRM ecosystem by taking a few minutes to <a %1>fill out the site registration form</a>. The information collected will help us prioritize improvements, target our communications and build the community. If you have a technical role for this site, be sure to check Keep in Touch to receive technical updates (a low volume mailing list).", array(1 => "href='$registerSiteURL' target='_blank'")) . "</li>"
. "<li>" . ts("We have integrated KCFinder with CKEditor and TinyMCE. This allows a user to upload images. All uploaded images are public.") . "</li>";

$output = NULL;
$output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
$output .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
$output .= '<head>';
$output .= '<title>' . ts('CiviCRM Installed') . '</title>';
$output .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
$output .= '<link rel="stylesheet" type="text/css" href="template.css" />';
$output .= '</head>';
$output .= '<body>';
$output .= '<div style="padding: 1em;"><p class="good">' . ts('CiviCRM has been successfully installed') . '</p>';
$output .= '<ul>';

$backdropURL = civicrm_cms_base();
$backdropPermissionsURL = "{$backdropURL}index.php?q=admin/config/people/permissions";
$backdropURL .= "index.php?q=civicrm/admin/configtask&reset=1";

$output .= "<li>" . ts("Backdrop user permissions have been automatically set - giving anonymous and authenticated users access to public CiviCRM forms and features. We recommend that you <a %1>review these permissions</a> to ensure that they are appropriate for your requirements (<a %2>learn more...</a>)", array(
  1 => "target='_blank' href='{$backdropPermissionsURL}'",
  2 => "target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'",
  )) . "</li>";
$output .= "<li>" . ts("Use the <a %1>Configuration Checklist</a> to review and configure settings for your new site", array(1 => "target='_blank' href='$backdropURL'")) . "</li>";
$output .= $commonOutputMessage;
$output .= '</ul>';
$output .= '</div>';
$output .= '</body>';
$output .= '</html>';
echo $output;
