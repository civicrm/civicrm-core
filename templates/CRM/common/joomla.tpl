{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $config->debug}
{include file="CRM/common/debug.tpl"}
{/if}

<div id="crm-container" class="crm-container{if $urlIsPublic} crm-public{/if}" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">

<table border="0" cellpadding="0" cellspacing="0" id="crm-content">
  <tr>
{if $sidebarLeft}
    <td id="sidebar-left" valign="top">
        <div id="civi-sidebar-logo" style="margin: 0 0 .25em .25em"><img src="{$config->resourceBase}i/logo_words_small.png" title="{ts}CiviCRM{/ts}"/></div><div class="spacer"></div>
       {$sidebarLeft}
    </td>
{/if}
    <td id="content-right" valign="top">
    {if $breadcrumb}
    <div class="breadcrumb">
      {foreach from=$breadcrumb item=crumb key=key}
        {if $key != 0}
           &raquo;
        {/if}
        <a href="{$crumb.url}">{$crumb.title}</a>
      {/foreach}
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

    {if $localTasks}
        {include file="CRM/common/localNav.tpl"}
    {/if}

    <div id="crm-main-content-wrapper">
      {include file="CRM/common/status.tpl"}
      {crmRegion name='page-body'}
        {if isset($isForm) and $isForm and isset($formTpl)}
          {include file="CRM/Form/$formTpl.tpl"}
        {else}
          {include file=$tplFile}
        {/if}
      {/crmRegion}
    </div>

    {crmRegion name='page-footer'}
    {if $urlIsPublic}
      {include file="CRM/common/publicFooter.tpl"}
    {else}
      {include file="CRM/common/footer.tpl"}
    {/if}
    {/crmRegion}

    </td>

  </tr>
</table>
</div> {* end crm-container div *}
