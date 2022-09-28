{* add campaigns to various components CRM-7362 *}

{if $campaignInfo.showAddCampaign}

  <tr class="{$campaignTrClass}">
    <td class="label">{$form.campaign_id.label} {help id="id-campaign_id" file="CRM/Campaign/Form/addCampaignToComponent.hlp"}</td>
    <td class="view-value">{$form.campaign_id.html}</td>
  </tr>

{/if}

