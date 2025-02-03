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
  <title>{ts}Error{/ts}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <base href="{$config->resourceBase}" />
  <style type="text/css" media="screen">
    @import url({$config->resourceBase}css/civicrm.css);
    @import url({$config->resourceBase}css/crm-i.css);
    @import url({$config->resourceBase}bower_components/font-awesome/css/all.min.css);
  </style>
</head>
<body>
<div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
{else}
<div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
  <style type="text/css" media="screen">
    @import url({$config->resourceBase}css/civicrm.css);
    @import url({$config->resourceBase}css/crm-i.css);
    @import url({$config->resourceBase}bower_components/font-awesome/css/all.min.css);
  </style>
{/if}
<div class="messages status no-popup">  <i class="crm-i fa-exclamation-triangle crm-i-red" aria-hidden="true"></i>
    <div class="crm-section crm-error-message">{$message|escape}</div>
    {if !empty($error.message) && $message != $error.message}
        <hr style="solid 1px" />
        <div class="crm-section crm-error-message">{$error.message|escape}</div>
    {/if}
    {if (!empty($code) || !empty($mysql_code) || !empty($errorDetails)) AND $config->debug}
        <details class="crm-accordion-bold crm-fatal-error-details-block">
         <summary>
          {ts}Error Details{/ts}
         </summary>
         <div class="crm-accordion-body">
            {if !empty($code)}
                <div class="crm-section">{ts}Error Code:{/ts} {$code|purify}</div>
            {/if}
            {if !empty($mysql_code)}
                <div class="crm-section">{ts}Database Error Code:{/ts} {$mysql_code|purify}</div>
            {/if}
            {if !empty($errorDetails)}
                <div class="crm-section">{ts}Additional Details:{/ts} {$errorDetails|purify}</div>
            {/if}
         </div>
        </details>
    {/if}
    <p><a href="{$config->userFrameworkBaseURL}" title="{ts escape='htmlattribute'}Main Menu{/ts}">{ts}Return to home page.{/ts}</a></p>
</div>
</div> {* end crm-container div *}
{if $config->userFramework != 'WordPress'}
</body>
</html>
{/if}
