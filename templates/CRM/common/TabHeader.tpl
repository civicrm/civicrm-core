{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* enclose all tabs and its content in a block *}
<div class="crm-block crm-content-block">
  {if $tabHeader}
    <div id="mainTabContainer">
    <ul>
       {foreach from=$tabHeader key=tabName item=tabValue}
          <li id="tab_{$tabName}" class="crm-tab-button ui-corner-all{if !$tabValue.valid} disabled{/if} {$tabValue.class}" {$tabValue.extra}>
          {if $tabValue.active}
             <a href="{if $tabValue.template}#panel_{$tabName}{else}{$tabValue.link|smarty:nodefaults}{/if}" title="{$tabValue.title|escape}{if !$tabValue.valid} ({ts}disabled{/ts}){/if}">
               {if $tabValue.icon}<i class="{$tabValue.icon}"></i>{/if}
               <span>{$tabValue.title}</span>
               {if is_numeric($tabValue.count)}<em>{$tabValue.count}</em>{/if}
             </a>
          {else}
             <span {if !$tabValue.valid} title="{ts}disabled{/ts}"{/if}>{$tabValue.title}</span>
          {/if}
          </li>
       {/foreach}
    </ul>
      {foreach from=$tabHeader key=tabName item=tabValue}
        {if $tabValue.template}
          <div id="panel_{$tabName}">
            {include file=$tabValue.template}
          </div>
        {/if}
      {/foreach}
    </div>
  {/if}
  <div class="clear"></div>
</div> {* crm-content-block ends here *}
