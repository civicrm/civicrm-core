{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* Search form and results for voters *}
<div class="crm-block crm-form-block crm-search-form-block">

{assign var='searchForm' value='searchForm'}
{if $searchVoterFor}
  {assign var='searchForm' value="search_form_$searchVoterFor"}
{/if}

  <div id="{$searchForm}" class="crm-accordion-wrapper crm-contribution_search_form-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
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
              {$form.survey_interviewer_name.label}
            </td>
            <td class="font-size12pt ">
              {$form.survey_interviewer_name.html}
            </td>
          {/if}

        </tr>
        <tr>
          <td class="font-size12pt">
            {$form.sort_name.label}
          </td>
          <td>
            {$form.sort_name.html|crmAddClass:'twenty'}
          </td>
          <td><label>{ts}Contact Type(s){/ts}</label><br />
            {$form.contact_type.html}
            {literal}
              <script type="text/javascript">
                cj("select#contact_type").crmasmSelect({
                  addItemTarget: 'bottom',
                  animate: false,
                  highlight: true,
                  sortable: true,
                  respectParents: true
                });
              </script>
            {/literal}
          </td>
          <td><label>{ts}Group(s){/ts}</label>
            {$form.group.html}
            {literal}
              <script type="text/javascript">
                cj("select#group").crmasmSelect({
                  addItemTarget: 'bottom',
                  animate: false,
                  highlight: true,
                  sortable: true,
                  respectParents: true,
                  selectClass:'campaignGroupsSelect'
                });
              </script>
            {/literal}
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

  cj(function() {
    cj().crmAccordions();

    {/literal}
    {if !$isFormSubmitted}
      buildCampaignGroups( );
    {/if}
    {literal}
  });

//load interviewer autocomplete.
var interviewerDataUrl = "{/literal}{$dataUrl}{literal}";
var hintText = "{/literal}{ts escape='js'}Type in a partial or complete name of an existing contact.{/ts}{literal}";
cj( "#survey_interviewer_name" ).autocomplete( interviewerDataUrl,
  { width : 256,
    selectFirst : false,
    hintText: hintText,
    matchContains: true,
    minChars: 1
  }
).result( function( event, data, formatted ) {
    cj( "#survey_interviewer_id" ).val( data[1] );
  }).bind( 'click', function( ) {
    cj( "#survey_interviewer_id" ).val('');
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

      var selectName         = 'campaignGroupsSelect';
      var groupSelect        = cj("select[id^=" + selectName + "]");
      var groupSelectCountId  = cj( groupSelect ).attr( 'id' ).replace( selectName, '' );

      //first remove all groups for given survey.
      cj( "#group" ).find('option').remove( );
      cj( groupSelect ).find('option').remove( );
      cj( '#crmasmContainer' + groupSelectCountId ).find( 'span' ).remove( );
      cj( '#crmasmList' + groupSelectCountId ).find( 'li' ).remove( );

      var groups = data.groups;

      //build the new group options.
      var optCount = 0;
      for ( group in groups ) {
        title = groups[group].title;
        value = groups[group].value;
        if ( !title ) continue;
        var crmOptCount = 'asm' + groupSelectCountId + 'option' + optCount;

        //add options to main group select.
        cj( "#group" ).append( cj('<option></option>').val( value ).html( title ).attr( 'id', crmOptCount ) );

        //add option to crm multi select ul.
        cj( groupSelect ).append( cj('<option></option>').val(value).html(title).attr( 'rel', crmOptCount ) );

        optCount++;
      }
    },
    'json');
}

</script>
{/literal}
