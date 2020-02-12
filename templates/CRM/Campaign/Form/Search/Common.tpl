{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Search form and results for voters *}
<div class="crm-block crm-form-block crm-search-form-block">

{assign var='searchForm' value='searchForm'}
{if $searchVoterFor}
  {assign var='searchForm' value="search_form_$searchVoterFor"}
{/if}
  <div id="{$searchForm}" class="crm-accordion-wrapper crm-contribution_search_form-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header {if !$votingTab} crm-master-accordion-header{/if}">
    {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->

    <div class="crm-accordion-body">
    {strip}
      <table class="form-layout">
        <tr>
          <td class="font-size12pt">
            {$form.campaign_survey_id.label}
          </td>
          <td>
            {$form.campaign_survey_id.html}
          </td>

          {if $showInterviewer}
            <td class="font-size12pt">
              {$form.survey_interviewer_id.label}
            </td>
            <td class="font-size12pt ">
              {$form.survey_interviewer_id.html}
            </td>
          {/if}

        </tr>
        <tr>
          <td class="font-size12pt">
            {$form.sort_name.label}
          </td>
          <td colspan="3">
            {$form.sort_name.html|crmAddClass:'twenty'}
          </td>
        </tr>
        <tr>
          <td>
            <label>{ts}Contact Type(s){/ts}</label>
          </td>
          <td>
            {$form.contact_type.html}
          </td>
          <td>
            <label>{ts}Group(s){/ts}</label>
          </td>
          <td >
            {$form.group.html}
          </td>
        </tr>

        <tr>
          <td class="font-size12pt">
            {$form.street_address.label}
          </td>
          <td>
            {$form.street_address.html}
          </td>
          <td class="font-size12pt">
            {$form.street_name.label}
          </td>
          <td>
            {$form.street_name.html}
          </td>
        </tr>
        <tr>
          <td class="font-size12pt">
            {$form.street_unit.label}
          </td>
          <td>
            {$form.street_unit.html}
          </td>
          <td class="font-size12pt">
            {$form.city.label}
          </td>
          <td>
            {$form.city.html}
          </td>
        </tr>
        <tr>
          <td class="font-size12pt">
            {$form.street_number.label}
          </td>
          <td>
            {$form.street_number.html}
          </td>

          <td class="font-size12pt">
            {$form.postal_code.label}
          </td>
          <td>
            {$form.postal_code.html}
          </td>
        </tr>
        {if $customSearchFields.ward || $customSearchFields.precinct}
          <tr>
            {if $customSearchFields.ward}
              {assign var='ward' value=$customSearchFields.ward}
              <td class="font-size12pt">
                {$form.$ward.label}
              </td>
              <td>
                {$form.$ward.html}
              </td>
            {/if}

            {if $customSearchFields.precinct}
              {assign var='precinct' value=$customSearchFields.precinct}
              <td class="font-size12pt">
                {$form.$precinct.label}
              </td>
              <td>
                {$form.$precinct.html}
              </td>
            {/if}
          </tr>
        {/if}
        <tr>
          <td colspan="2">
            {if $context eq 'search'}
              {$form.buttons.html}
              {else}
              <a class="searchVoter button" style="float:left;" href="#" title={ts}Search{/ts} onClick="searchVoters( '{$qfKey}' );return false;">{ts}Search{/ts}</a>
            {/if}
          </td>
        </tr>
      </table>
    {/strip}

    </div>
  </div>
</div>

{literal}
<script type="text/javascript">

  CRM.$(function($) {
    {/literal}
    {if !$isFormSubmitted}
      buildCampaignGroups( );
    {/if}
    {literal}
  });

function buildCampaignGroups( surveyId ) {
  if ( !surveyId ) surveyId = cj("#campaign_survey_id").val( );

  var operation = {/literal}'{$searchVoterFor}'{literal};
  if ( !surveyId || operation != 'reserve' ) return;

  var grpUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Campaign_Page_AJAX&fnName=campaignGroups'}"
  {literal};

  cj.post( grpUrl,
    {survey_id:surveyId},
    function( data ) {
      if ( data.status != 'success' ) return;

      //first remove all groups for given survey.
      cj("#group").find('option').remove();

      var groups = data.groups;

      //build the new group options.
      var optCount = 0;
      for ( group in groups ) {
        var title = groups[group].title;
        var value = groups[group].value;
        if ( !value ) continue;

        //add options to main group select.
        cj( "#group" ).append( cj('<option></option>').val( value ).html(title));

        optCount++;
      }
      cj("#group").trigger('change');
    },
    'json');
}

</script>
{/literal}
