{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* error.tpl: Display page for fatal errors. Provides complete HTML doc.*}
{if $config->userFramework != 'WordPress'}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>
  <title>{$pageTitle|escape}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <base href="{$config->resourceBase}" />
  <style type="text/css" media="screen">
    @import url({$config->resourceBase}css/civicrm.css);
    @import url({$config->resourceBase}css/crm-i.css);
    @import url({$config->resourceBase}bower_components/font-awesome/css/font-awesome.min.css);
  </style>
</head>
<body>
<div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
{else}
<div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
  <style type="text/css" media="screen">
    @import url({$config->resourceBase}css/civicrm.css);
    @import url({$config->resourceBase}css/crm-i.css);
    @import url({$config->resourceBase}bower_components/font-awesome/css/font-awesome.min.css);
  </style>
{/if}
<div class="messages status no-popup">  <i class="crm-i fa-exclamation-triangle crm-i-red" aria-hidden="true"></i>
 <span class="status-fatal">{ts}Sorry, due to an error, we are unable to fulfill your request at the moment. You may want to contact your administrator or service provider with more details about what action you were performing when this occurred.{/ts}</span>
    <div class="crm-section crm-error-message">{$message|escape}</div>
    {if $error.message && $message != $error.message}
        <hr style="solid 1px" />
        <div class="crm-section crm-error-message">{$error.message|escape}</div>
    {/if}
    {if ($code OR $mysql_code OR $errorDetails) AND $config->debug}
        <div class="crm-accordion-wrapper collapsed crm-fatal-error-details-block">
         <div class="crm-accordion-header" onclick="toggle(this);";>
          {ts}Error Details{/ts}
         </div><!-- /.crm-accordion-header -->
         <div class="crm-accordion-body">
            {if $code}
                <div class="crm-section">{ts}Error Code:{/ts} {$code|purify}</div>
            {/if}
            {if $mysql_code}
                <div class="crm-section">{ts}Database Error Code:{/ts} {$mysql_code|purify}</div>
            {/if}
            {if $errorDetails}
                <div class="crm-section">{ts}Additional Details:{/ts} {$errorDetails|purify}</div>
            {/if}
         </div><!-- /.crm-accordion-body -->
        </div><!-- /.crm-accordion-wrapper -->
    {/if}
    <p><a href="{$config->userFrameworkBaseURL}" title="{ts}Main Menu{/ts}">{ts}Return to home page.{/ts}</a></p>
</div>
</div> {* end crm-container div *}
{literal}
<script language="JavaScript">
function toggle( element ) {
    var parent = element.parentNode;
    var className = parent.className;
    if ( className  == 'crm-accordion-wrapper collapsed crm-fatal-error-details-block') {
        parent.className = 'crm-accordion-wrapper  crm-fatal-error-details-block';
    } else {
        parent.className = 'crm-accordion-wrapper collapsed crm-fatal-error-details-block';
    }
}
</script>
{/literal}
{if $config->userFramework != 'WordPress'}
</body>
</html>
{/if}
