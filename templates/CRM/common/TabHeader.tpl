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
  {if !empty($tabHeader|smarty:nodefaults) and count($tabHeader)}
    <div id="mainTabContainer">
    <ul>
       {foreach from=$tabHeader key=tabName item=tabValue}
          <li id="tab_{$tabName}" class="crm-tab-button ui-corner-all{if !$tabValue.valid} disabled{/if}{if !empty($tabValue.class|smarty:nodefaults)} {$tabValue.class}{/if}" {if !empty($tabValue.extra|smarty:nodefaults)}{$tabValue.extra}{/if}>
          {if $tabValue.active}
             <a href="{if !empty($tabValue.template|smarty:nodefaults)}#panel_{$tabName}{else}{$tabValue.link}{/if}" title="{$tabValue.title|escape}{if !$tabValue.valid} ({ts}disabled{/ts}){/if}">
               {if !empty($tabValue.icon|smarty:nodefaults)}<i class="{$tabValue.icon}"></i>{/if}
               <span>{$tabValue.title}</span>
               {if isset($tabValue.count|smarty:nodefaults)}<em>{$tabValue.count}</em>{/if}
             </a>
          {else}
             <span {if !$tabValue.valid} title="{ts}disabled{/ts}"{/if}>{$tabValue.title}</span>
          {/if}
          </li>
       {/foreach}
    </ul>
      {foreach from=$tabHeader key=tabName item=tabValue}
        {if !empty($tabValue.template|smarty:nodefaults)}
          <div id="panel_{$tabName}">
            {include file=$tabValue.template}
          </div>
        {/if}
      {/foreach}
    </div>
  {/if}
  <div class="clear"></div>
</div> {* crm-content-block ends here *}
