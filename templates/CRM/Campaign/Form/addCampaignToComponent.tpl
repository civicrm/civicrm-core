{* add campaigns to various components CRM-7362 *}

{if isset($campaignContext) and $campaignContext eq 'componentSearch'}

  {* add campaign in component search *}
  <tr class="{$campaignTrClass}">
    {assign var=elementName value=$campaignInfo.elementName}
    <td class="{$campaignTdClass}">
      {if isset($form.$elementName)}
        {$form.$elementName.label} {$form.$elementName.html}
      {/if}
    </td>
  </tr>

{elseif $campaignInfo.showAddCampaign}

  <tr class="{$campaignTrClass}">
    <td class="label">{$form.campaign_id.label} {help id="id-campaign_id" file="CRM/Campaign/Form/addCampaignToComponent.hlp"}</td>
    <td class="view-value">{$form.campaign_id.html}</td>
  </tr>

{/if}{* add campaign to component search if closed. *}

