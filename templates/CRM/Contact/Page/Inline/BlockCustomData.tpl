{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template adding block-specific custom data *}
{foreach from=$customGroups item=customGroup key=customGroupID} {* start of customGroup foreach *}
  {if (!empty($customGroup.fields))}
    <details id="{$entity}_custom_{$customGroupID}_{$identifier}" class="crm-{$entity}-custom-{$customGroupID}-{$identifier}-accordion crm-accordion-light" {if $customGroup.collapse_display}{else}open{/if}>
      <summary class="collapsible-title">
        {$customGroup.title}
      </summary>
      <div class="crm-summary-block">
        {foreach from=$customGroup.fields item=customField}
          {* We only expect one value in this loop because as of writing only single fields custom groups are supported
          for the entities that use this -address, email~ *}
          {foreach from=$customField item=instance}
            <div class="crm-summary-row">
            {include file="CRM/Contact/Page/Inline/CustomDataFieldInstance.tpl" instance=$instance}
            </div>
          {/foreach}
        {/foreach}
      </div>
    </details>
  {/if}
{/foreach} {* end of outer custom group foreach *}
<!-- end custom data -->
