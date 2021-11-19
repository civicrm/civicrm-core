{if $campaignElementName}
  {* add campaign in component search *}
  <tr class="{$campaignTrClass}">
    <td class="{$campaignTdClass}">
      {$form.$campaignElementName.label} {$form.$campaignElementName.html}
    </td>
  </tr>
{/if}
