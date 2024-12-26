{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Custom Data view mode*}
{assign var="customGroupCount" value = 1}
{foreach from=$viewCustomData item=customValues key=customGroupId}
  {assign var="cgcount" value=1}
  {assign var="count" value=$customGroupCount%2}
  {if ($count eq $side) or $skipTitle}
    {foreach from=$customValues item=cd_edit key=cvID}
      <details class="customFieldGroup crm-accordion-light ui-corner-all {$cd_edit.name} crm-custom-set-block-{$customGroupId}" {if empty($cd_edit.collapse_display)} open{/if}>
        <summary>
          {$cd_edit.title}
        </summary>
        {if $cvID eq 0}
          {assign var='cvID' value='-1'}
        {/if}
        <div class="crm-summary-block" id="custom-set-block-{$customGroupId}-{$cvID}">
          {include file="CRM/Contact/Page/View/CustomDataFieldView.tpl" customGroupId=$customGroupId customRecId=$cvID cgcount=$cgcount}
        </div>
      </details>
      {assign var="cgcount" value=$cgcount+1}
    {/foreach}
  {/if}
  {assign var="customGroupCount" value = $customGroupCount+1}
{/foreach}

