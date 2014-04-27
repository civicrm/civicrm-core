{* add campaigns to various components CRM-7362 *}

{if $campaignContext eq 'componentSearch'}

{* add campaign in component search *}
<tr class="{$campaignTrClass}">
    {assign var=elementName value=$campaignInfo.elementName}

    <td class="{$campaignTdClass}">{$form.$elementName.label}<br />
    <div class="crm-select-container">{$form.$elementName.html}</div>
    </td>
</tr>

{else}

{if $campaignInfo.showAddCampaign}

    <tr class="{$campaignTrClass}">
        <td class="label">{$form.campaign_id.label} {help id="id-campaign_id" file="CRM/Campaign/Form/addCampaignToComponent.hlp"}</td>
        <td class="view-value">
      {* lets take a call, either show campaign select drop-down or show add campaign link *}
            {if $campaignInfo.hasCampaigns}
            {$form.campaign_id.html|crmAddClass:huge}
            {* show for add and edit actions *}
          {if ( $action eq 1 or $action eq 2 )
              and !$campaignInfo.alreadyIncludedPastCampaigns and $campaignInfo.includePastCampaignURL}
                <br />
                <a id='include-past-campaigns' href='#' onClick='includePastCampaigns( "campaign_id" ); return false;'>
                   &raquo;
                   {ts}Show past campaign(s) in this select list.{/ts}
                </a>
            {/if}
            {else}
            <div class="status">
            {ts}There are currently no active Campaigns.{/ts}
            {if $campaignInfo.addCampaignURL}
              {capture assign="link"}href="{$campaignInfo.addCampaignURL}" class="action-item"{/capture}
              {ts 1=$link}If you want to associate this record with a campaign, you can <a %1>create a campaign here</a>.{/ts}
            {/if} {help id="id-campaign_id" file="CRM/Campaign/Form/addCampaignToComponent.hlp"}
            </div>
          {/if}
        </td>
    </tr>


{literal}
<script type="text/javascript">
function includePastCampaigns()
{
    //hide past campaign link.
    cj( "#include-past-campaigns" ).hide( );

    var campaignUrl = {/literal}'{$campaignInfo.includePastCampaignURL}'{literal};
    cj.post( campaignUrl,
             null,
             function( data ) {
          if ( data.status != 'success' ) return;

          //first reset all select options.
         cj( "#campaign_id" ).val( '' );
             cj( "#campaign_id" ).html( '' );
         cj('input[name="included_past_campaigns"]').val( 1 );

         var campaigns = data.campaigns;

         //build the new options.
         for ( campaign in campaigns ) {
              title = campaigns[campaign].title;
              value = campaigns[campaign].value;
              className = campaigns[campaign].class;
              if ( !title ) continue;
              cj('#campaign_id').append( cj('<option></option>').val(value).html(title).addClass(className) );
          }
           },
           'json');
}
</script>
{/literal}


{/if}{* add campaign to component if closed.  *}

{/if}{* add campaign to component search if closed. *}

