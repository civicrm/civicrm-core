{* add campaigns to various components CRM-7362 *}

{if $campaignContext eq 'componentSearch'}

{* add campaign in component search *}
<tr class="{$campaignTrClass}">
    {assign var=elementName value=$campaignInfo.elementName}

    <td class="{$campaignTdClass}">{$form.$elementName.label}<br />
    <div class="crm-select-container">{$form.$elementName.html}</div>
       {literal}
       <script type="text/javascript">
       cj( "select[name=" + "'" + {/literal}'{$elementName}[]'{literal} + "'" + "]" ).crmasmSelect({
           addItemTarget: 'bottom',
           animate: true,
           highlight: true,
           sortable: true,
           respectParents: true,
     selectClass:'crmasmSelectCampaigns'
       });

       //lets disable the current and past campaign options.
       cj(function(){
              cj( 'select[id^="'+ 'crmasmSelectCampaigns' +'"] option' ).each( function( ) {
                   value = cj(this).val();
                   if ( value == 'current_campaign' || value == 'past_campaign' ) {
                        cj(this).css( 'color', 'black' );
                        cj(this).attr( 'disabled', true );
                  cj(this).attr( 'selected', false);
                        cj(this).addClass( 'asmOptionDisabled' );
                   }
              });
       });
       </script>
       {/literal}
    </td>
</tr>

{else}

{if $campaignInfo.showAddCampaign}

    <tr class="{$campaignTrClass}">
        <td class="label">{$form.campaign_id.label}</td>
        <td class="view-value">
      {* lets take a call, either show campaign select drop-down or show add campaign link *}
            {if $campaignInfo.hasCampaigns}
            {$form.campaign_id.html|crmAddClass:huge} {help id="id-campaign_id" file="CRM/Campaign/Form/addCampaignToComponent.hlp"}
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
                {ts 1=$campaignInfo.addCampaignURL}If you want to associate this record with a campaign, you can <a href="%1">create a campaign here</a>.{/ts}
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

