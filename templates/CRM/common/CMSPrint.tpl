{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

{if $pageTitle}
  <div class="crm-title">
    <h1 class="title">{if $isDeleted}<del>{/if}{$pageTitle}{if $isDeleted}</del>{/if}</h1>
  </div>
{/if}

{crmRegion name='page-header'}
{/crmRegion}
<div class="clear"></div>

{if isset($localTasks) and $localTasks}
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

</div> {* end crm-container div *}
