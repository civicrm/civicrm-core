{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* entity selector *}
{crmRegion name="crm-contact-relationshipselector-pre"}{/crmRegion}
<div class="crm-contact-{$entityInClassFormat}-{$context}">
  <table
    class="crm-contact-{$entityInClassFormat}-selector-{$context} crm-ajax-table"
    data-ajax="{crmURL p="civicrm/ajax/contactrelationships" q="context=$context&cid=$contactId"}"
    data-order='[[0,"asc"],[1,"asc"]]'
    style="width: 100%;">
    <thead>
    <tr>
      {foreach from=$columnHeaders key=headerkey item=header}
        {if $header.sort}
          <th data-data="{$header.sort}" class="crm-contact-{$entityInClassFormat}-{$header.sort}">{$header.name}</th>
        {else}
          <th data-data="{$headerkey}" data-orderable="false" class="crm-contact-{$entityInClassFormat}-{$headerkey}">{$header.name}</th>
        {/if}

      {/foreach}
    </tr>
    </thead>
  </table>
</div>
{crmRegion name="crm-contact-relationshipselector-post"}
{/crmRegion}
