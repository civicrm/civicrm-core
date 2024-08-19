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
{foreach from=$customFields item=customGroup key=cgId} {* start of outer foreach *}
  {foreach from=$customGroup item=customValue key=cvId}
    <details id="{$entity}_custom_{$cgId}_{$identifier}" class="crm-{$entity}-custom-{$cgId}-{$identifier}-accordion crm-accordion-light" {if $customValue.collapse_display}{else}open{/if}>
      <summary class="collapsible-title">
        {$customValue.title}
      </summary>
      <div class="crm-summary-block">
        {foreach from=$customValue.fields item=customField key=cfId}
          <div class="crm-summary-row">
            <div class="crm-label">
              {$customField.field_title}
            </div>
            <div class="crm-content">
              {$customField.field_value}
            </div>
          </div>
        {/foreach}
      </div>
    </details>
  {/foreach}
{/foreach} {* end of outer custom group foreach *}
<!-- end custom data -->
