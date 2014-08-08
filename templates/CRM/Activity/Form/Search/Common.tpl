{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<tr>
  <td colspan="2">
    {$form.activity_role.html}
    </span>
  </td>
</tr>
<tr>
  {if $form.activity_type_id}
    <td><label>{ts}Activity Type(s){/ts}</label>
      <div id="Activity" class="listing-box">
        {foreach from=$form.activity_type_id item="activity_type_val"}
          <div class="{cycle values='odd-row,even-row'}">
            {$activity_type_val.html}
          </div>
        {/foreach}
      </div>
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}
  {if $form.activity_survey_id || $buildEngagementLevel}
    <td>
      {if $form.activity_survey_id}
        <label>{$form.activity_survey_id.label}</label>
        <br/>
        {$form.activity_survey_id.html}
      {/if}
      {if $buildEngagementLevel}
        <br
        / >
        <br/>
        <label>{$form.activity_engagement_level.label}</label>
        <br/>
        {$form.activity_engagement_level.html}
      {/if}
    </td>
  {/if}

  <td>
    <table>
      <tr><td>
        {if $form.parent_id}
      	  <label>{ts}Has a Followup Activity?{/ts}</label>
      	  <br/>
      	  {$form.parent_id.html}
        {/if}
      </td></tr>
      <tr><td>
    	{if $form.followup_parent_id}
      	  <label>{ts}Is a Followup Activity?{/ts}</label>
      	  <br/>
      	  {$form.followup_parent_id.html}
        {/if}
      </td></tr>
    </table>
  </td>
  {if $form.activity_tags}
    <td><label>{ts}Activity Tag(s){/ts}</label>
      <div id="Tags" class="listing-box">
        {foreach from=$form.activity_tags item="tag_val"}
          <div class="{cycle values='odd-row,even-row'}">
            {$tag_val.html}
          </div>
        {/foreach}
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}
</tr>

<tr>
  <td><label>{ts}Activity Dates{/ts}</label></td>
</tr>
<tr>
  {include file="CRM/Core/DateRange.tpl" fieldName="activity_date" from='_low' to='_high'}
</tr>
<tr>
  <td>
    {$form.activity_subject.label}<br/>
    {$form.activity_subject.html|crmAddClass:big}
  </td>
  <td colspan="2">
    {$form.activity_status.label}<br/>
    {$form.activity_status.html}
  </td>
</tr>
<tr>
  {* td intentionally left blank to align the 'is test' widget on the right *}
  <td></td>
  <td colspan="2">
    {$form.activity_test.label} {help id="is-test" file="CRM/Contact/Form/Search/Advanced"}
    &nbsp; {$form.activity_test.html}
  </td>
</tr>
{if $buildSurveyResult }
	<tr>
	  <td id="activityResult">
	    <label>{$form.activity_result.label}</label><br />
	    {$form.activity_result.html}
	  </td>
		<td colspan="2">{include file="CRM/common/Tagset.tpl" tagsetType='activity'}</td>
	</tr>
{else}
	<tr>
		<td colspan="3">{include file="CRM/common/Tagset.tpl" tagsetType='activity'}</td>
	</tr>
{/if}

{* campaign in activity search *}
{include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
campaignContext="componentSearch" campaignTrClass='' campaignTdClass=''}

{if $activityGroupTree}
  <tr id="activityCustom">
    <td id="activityCustomData" colspan="2">
      {include file="CRM/Custom/Form/Search.tpl" groupTree=$activityGroupTree showHideLinks=false}
    </td>
  </tr>
{/if}

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    //Searchable activity custom fields which extend ALL activity types are always displayed in the form
    //hence hide remaining activity custom data
    $('#activityCustom').children( ).each( function( ) {
      $('#'+$(this).attr('id')+' div').each( function( ) {
        if ($(this).children( ).attr('id')) {
          var activityCustomdataGroup = $(this).attr('id');  //div id
          var fieldsetId = $(this).children( ).attr('id');  // fieldset id
          var splitFieldsetId = fieldsetId.split("");
          var splitFieldsetLength = splitFieldsetId.length;  //length of fieldset
          var show = 0;
          //setdefault activity custom data group if corresponding activity type is checked
          $('#Activity div').each(function( ) {
            var checkboxId = $(this).children().attr('id');  //activity type element name
            if (document.getElementById( checkboxId ).checked ) {
              var element = checkboxId.split('[');
              var splitElement = element[1].split(']');  // get activity type id
              for (var i=0; i<splitFieldsetLength; i++) {
                var singleFieldset = splitFieldsetId[i];
                if (parseInt( singleFieldset)) {
                  if (singleFieldset == splitElement[0]) {
                    show++;
                  }
                }
              }
            }
          });
          if (show < 1) {
            $('#'+activityCustomdataGroup).hide( );
          }
        }
      });
    });
  });

  function showCustomData(chkbox) {
  if (document.getElementById(chkbox).checked) {
    //inject Searchable activity custom fields according to activity type selected
    var element = chkbox.split("[");
    var splitElement = element[1].split("]");
    cj('#activityCustom').children().each(function( ) {
      cj('#'+cj(this).attr( 'id' )+' div').each(function( ) {
        if (cj(this).children().attr('id')) {
          if (cj('#'+cj(this).attr('id')+(' fieldset')).attr('id')) {
            var fieldsetId = cj('#' + cj(this).attr('id')+(' fieldset')).attr('id').split("");
            var activityTypeId = jQuery.inArray(splitElement[0], fieldsetId);
            if (fieldsetId[activityTypeId] == splitElement[0]) {
              cj(this).show();
            }
          }
        }
      });
    });
  }
  else {
    //hide activity custom fields if the corresponding activity type is unchecked
    var setcount = 0;
    var element = chkbox.split( "[" );
    var splitElement = element[1].split( "]" );
    cj('#activityCustom').children().each( function( ) {
      cj('#'+cj(this).attr( 'id' )+' div').each(function() {
        if (cj(this).children().attr('id')) {
          if (cj('#'+cj(this).attr('id')+(' fieldset')).attr('id')) {
            var fieldsetId = cj( '#'+cj(this).attr('id')+(' fieldset')).attr('id').split("");
            var activityTypeId = jQuery.inArray( splitElement[0],fieldsetId);
            if (fieldsetId[activityTypeId] ==  splitElement[0]) {
              cj('#'+cj(this).attr('id')).each( function() {
                if (cj(this).children().attr('id')) {
                  //if activity custom data extends more than one activity types then
                  //hide that only when all the extended activity types are unchecked
                  cj('#'+cj(this).attr('id')+(' fieldset')).each( function( ) {
                    var splitFieldsetId = cj( this ).attr('id').split("");
                    var splitFieldsetLength = splitFieldsetId.length;
                    for( var i=0;i<splitFieldsetLength;i++ ) {
                      var setActivityTypeId = splitFieldsetId[i];
                      if (parseInt(setActivityTypeId)) {
                        var activityTypeId = 'activity_type_id['+setActivityTypeId+']';
                        if (document.getElementById(activityTypeId).checked) {
                          return false;
                        }
                        else {
                          setcount++;
                        }
                      }
                    }
                    if (setcount > 0) {
                      cj('#'+cj(this).parent().attr('id')).hide();
                    }
                  });
                }
              });
            }
          }
        }
      });
    });
  }
}
{/literal}
</script>
