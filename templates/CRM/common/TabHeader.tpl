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
<div class="crm-block crm-content-block {$containerClasses|default:''}">
  {if $tabHeader}
    <div id="mainTabContainer">
      <ul class="{$listClasses|default:''}" role="tablist">
        {foreach from=$tabHeader key=tabName item=tabValue}
          <li id="tab_{$tabName}" role="tab" class="crm-tab-button ui-corner-all {if !$tabValue.valid}disabled{/if} {if is_numeric($tabValue.count)}crm-count-{$tabValue.count}{/if} {if $tabValue.class} {$tabValue.class}{/if}" {$tabValue.extra}>
            {if $tabValue.active}
              <a href="{if $tabValue.template}#{$tabIdPrefix|default:'panel_'}{$tabName}{else}{$tabValue.url|smarty:nodefaults}{/if}" title="{$tabValue.title|escape} {if !$tabValue.valid}({ts escape='htmlattribute'}disabled{/ts}){/if}">
                <i class="{$tabValue.icon|default:'crm-i fa-puzzle-piece'}" role="img" aria-hidden="true"></i>
                <span>{$tabValue.title}</span>
                {if empty($tabValue.hideCount) && is_numeric($tabValue.count)}<em>{$tabValue.count}</em>{/if}
              </a>
            {else}
               <span {if !$tabValue.valid} title="{ts escape='htmlattribute'}disabled{/ts}"{/if}>{$tabValue.title}</span>
            {/if}
          </li>
        {/foreach}
      </ul>

      {* Item must be named $block for compatibility with InlineAfform.tpl *}
      {foreach from=$tabHeader key=tabName item=block}
        {if $block.template}
          <div id="{$tabIdPrefix|default:'panel_'}{$tabName}" role="tabpanel">
            {include file=$block.template}
          </div>
        {/if}
      {/foreach}
    </div>
  {/if}
  <div class="clear"></div>
</div>
