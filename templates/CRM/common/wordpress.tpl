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
{if $config->debug}
{include file="CRM/common/debug.tpl"}
{/if}

<div id="crm-container" class="crm-container{if $urlIsPublic} crm-public{/if}" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">

{* we should uncomment below code only when we are experimenting with new css for specific pages and comment css inclusion in civicrm.module*}
{*if $config->customCSSURL}
    <link rel="stylesheet" href="{$config->customCSSURL}" type="text/css" />
{else}
    {assign var="revamp" value=0}
    {foreach from=$config->revampPages item=page}
        {if $page eq $tplFile}
            {assign var="revamp" value=1}
        {/if}
    {/foreach}

    {if $revamp eq 0}
        <link rel="stylesheet" href="{$config->resourceBase}css/civicrm.css" type="text/css" />
    {else}
        <link rel="stylesheet" href="{$config->resourceBase}css/civicrm-new.css" type="text/css" />
    {/if}
    <link rel="stylesheet" href="{$config->resourceBase}css/extras.css" type="text/css" />
{/if*}


{crmNavigationMenu is_default=1}

{if $breadcrumb}
    <div class="breadcrumb">
      {foreach from=$breadcrumb item=crumb key=key}
        {if $key != 0}
           &raquo;
        {/if}
      {$crumb}
      {/foreach}
    </div>
{/if}

{* include wysiwyg related files*}
{include file="CRM/common/wysiwyg.tpl"}

{if isset($browserPrint) and $browserPrint}
{* Javascript window.print link. Used for public pages where we can't do printer-friendly view. *}
<div id="printer-friendly">
<a href="#" onclick="window.print(); return false;" title="{ts}Print this page.{/ts}">
  <div class="ui-icon ui-icon-print"></div>
</a>
</div>
{else}
{* Printer friendly link/icon. *}
<div id="printer-friendly">
<a href="{$printerFriendly}" title="{ts}Printer-friendly view of this page.{/ts}">
  <div class="ui-icon ui-icon-print"></div>
</a>
</div>
{/if}

{if $pageTitle}
  <div class="crm-title">
    <h1 class="title">{if $isDeleted}<del>{/if}{$pageTitle}{if $isDeleted}</del>{/if}</h1>
  </div>
{/if}

{crmRegion name='page-header'}
{/crmRegion}
{*{include file="CRM/common/langSwitch.tpl"}*}

<div class="clear"></div>

{if isset($localTasks) and $localTasks}
    {include file="CRM/common/localNav.tpl"}
{/if}

{include file="CRM/common/status.tpl"}


{crmRegion name='page-body'}
<!-- .tpl file invoked: {$tplFile}. Call via form.tpl if we have a form in the page. -->
{if isset($isForm) and $isForm}
    {include file="CRM/Form/$formTpl.tpl"}
{else}
    {include file=$tplFile}
{/if}
{/crmRegion}


{crmRegion name='page-footer'}
{if ! $urlIsPublic}
{include file="CRM/common/footer.tpl"}
{/if}
{/crmRegion}

</div> {* end crm-container div *}

