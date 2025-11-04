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

<div id="crm-container" class="crm-container{if !empty($urlIsPublic)} crm-public{/if}" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
  <div id ="crm-content">
    {if $breadcrumb}
    <div class="breadcrumb">
      {foreach from=$breadcrumb item=crumb key=key}
        {if $key != 0}
           <i class="crm-i fa-angle-double-right" role="img" aria-hidden="true"></i>
        {/if}
        <a href="{$crumb.url}">{$crumb.title}</a>
      {/foreach}
    </div>
    {/if}

    {if $pageTitle}
      <div class="crm-title crm-page-title-wrapper">
        <h1 class="title">{if $isDeleted}<del>{/if}{$pageTitle}{if $isDeleted}</del>{/if}</h1>
      </div>
    {/if}

    {crmRegion name='page-header'}
    {/crmRegion}

    {*{include file="CRM/common/langSwitch.tpl"}*}

    <div class="clear"></div>

    <div id="crm-main-content-wrapper">
      {include file="CRM/common/status.tpl"}
      {crmRegion name='page-body'}
        {if $isForm and $formTpl}
          {include file="CRM/Form/$formTpl.tpl"}
        {else}
          {include file=$tplFile}
        {/if}
      {/crmRegion}
    </div>

    {crmRegion name='page-footer'}
    {if !empty($urlIsPublic)}
      {include file="CRM/common/publicFooter.tpl"}
    {else}
      {include file="CRM/common/footer.tpl"}
    {/if}
    {/crmRegion}
  </div>
</div> {* end crm-container div *}
