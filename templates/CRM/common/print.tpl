{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* Print.tpl: wrapper for Print views. Provides complete HTML doc. Includes print media stylesheet.*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">

<head>
  <title>{if $pageTitle}{$pageTitle|strip_tags}{else}{ts}Printer-Friendly View{/ts} | {ts}CiviCRM{/ts}{/if}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
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
<!-- .tpl file invoked: {$tplFile}. Call via form.tpl if we have a form in the page. -->
  {if $isForm}
    {include file="CRM/Form/$formTpl.tpl"}
  {else}
    {include file=$tplFile}
  {/if}
{/crmRegion}


{crmRegion name='page-footer' allowCmsOverride=0}{/crmRegion}
</div> {* end crm-container div *}
</body>
</html>
