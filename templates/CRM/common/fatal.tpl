{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* error.tpl: Display page for fatal errors. Provides complete HTML doc.*}
{if $config->userFramework != 'Joomla' and $config->userFramework != 'WordPress'}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>
  <title>{$pageTitle}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <base href="{$config->resourceBase}" />
  <style type="text/css" media="screen">@import url({$config->resourceBase}css/civicrm.css);</style>
  <style type="text/css" media="screen">@import url({$config->resourceBase}css/extras.css);</style>
</head>
<body>
<div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
{else}
<div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
  <style type="text/css" media="screen">@import url({$config->resourceBase}css/civicrm.css);</style>
  <style type="text/css" media="screen">@import url({$config->resourceBase}css/extras.css);</style>
{/if}
<div class="messages status no-popup">  <div class="icon red-icon alert-icon"></div>
 <span class="status-fatal">{ts}Sorry but we are not able to provide this at the moment.{/ts}</span>
    <div class="crm-section crm-error-message">{$message}</div>
    {if $error.message && $message != $error.message}
        <hr style="solid 1px" />
        <div class="crm-section crm-error-message">{$error.message}</div>
    {/if}
    {if ($code OR $mysql_code OR $errorDetails) AND $config->debug}
        <div class="crm-accordion-wrapper collapsed crm-fatal-error-details-block" onclick="toggle(this);";>
         <div class="crm-accordion-header">
          {ts}Error Details{/ts}
         </div><!-- /.crm-accordion-header -->
         <div class="crm-accordion-body">
            {if $code}
                <div class="crm-section">{ts}Error Code:{/ts} {$code}</div>
            {/if}
            {if $mysql_code}
                <div class="crm-section">{ts}Database Error Code:{/ts} {$mysql_code}</div>
            {/if}
            {if $errorDetails}
                <div class="crm-section">{ts}Additional Details:{/ts} {$errorDetails}</div>
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
    var className = element.className;
    if ( className  == 'crm-accordion-wrapper collapsed crm-fatal-error-details-block') {
        element.className = 'crm-accordion-wrapper  crm-fatal-error-details-block';
    } else {
        element.className = 'crm-accordion-wrapper collapsed crm-fatal-error-details-block';
    }
}
</script>
{/literal}
{if $config->userFramework != 'Joomla' and $config->userFramework != 'WordPress'}
</body>
</html>
{/if}
