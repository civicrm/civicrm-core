{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Print.tpl: wrapper for Print views. Provides complete HTML doc. Includes print media stylesheet.*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">

<head>
  <title>{if $pageTitle}{$pageTitle|strip_tags}{else}{ts}Printer-Friendly View{/ts} | {ts}CiviCRM{/ts}{/if}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
  {crmRegion name='html-header' allowCmsOverride=0}{/crmRegion}
  <style type="text/css" media="print">@import url({$config->resourceBase}css/print.css);</style>
</head>

<body>
{if $config->debug}
  {include file="CRM/common/debug.tpl"}
{/if}
<div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
{crmRegion name='page-header' allowCmsOverride=0}{/crmRegion}
{* Check for Status message for the page (stored in session->getStatus). Status is cleared on retrieval. *}
{include file="CRM/common/status.tpl"}

{crmRegion name='page-body' allowCmsOverride=0}
  {if $isForm and $formTpl}
    {include file="CRM/Form/$formTpl.tpl"}
  {else}
    {include file=$tplFile}
  {/if}
{/crmRegion}


{crmRegion name='page-footer' allowCmsOverride=0}
  <script type="text/javascript">
    window.print();
  </script>
{/crmRegion}
</div> {* end crm-container div *}
</body>
</html>
