{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<tr>
  <td colspan="2">
    {$form.activity_role.html}
    </span>
  </td>
</tr>
<tr>
  {if !empty($form.activity_type_id)}
    <td><label>{$form.activity_type_id.label}</label>
       <br />
       {$form.activity_type_id.html}
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}
  {if !empty($form.activity_survey_id) || $buildEngagementLevel}
    <td>
      {if !empty($form.activity_survey_id)}
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
        {if !empty($form.parent_id)}
          <label>{ts}Has a Followup Activity{/ts}</label>
          <br/>
          {$form.parent_id.html}
        {/if}
      </td></tr>
      <tr><td>
      {if !empty($form.followup_parent_id)}
          <label>{ts}Is a Followup Activity{/ts}</label>
          <br/>
          {$form.followup_parent_id.html}
        {/if}
      </td></tr>
    </table>
  </td>
</tr>

{if !empty($form.activity_tags)}
  <tr>
    <td><label>{$form.activity_tags.label}</label>
      <br/>
      {$form.activity_tags.html}
    </td>
  </tr>
{/if}

<tr>
  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="activity_date_time" to='' from='' colspan="2" hideRelativeLabel=0 class =''}
  <td>&nbsp;</td>
</tr>
<tr>
  <td>
    {$form.activity_text.label}<br/>
    {$form.activity_text.html|crmAddClass:big}<br/>
    {$form.activity_option.html}<br/>
  </td>
  <td colspan="2">
    {$form.activity_status_id.label}<br/>
    {$form.activity_status_id.html}
  </td>
</tr>
<tr>
  <td>
    {$form.priority_id.label}<br />
    {$form.priority_id.html}
  </td>
  <td colspan="2">
    {$form.activity_test.label} {help id="is_test" file="CRM/Contact/Form/Search/Advanced" title=$form.activity_test.textLabel}
    &nbsp; {$form.activity_test.html}
  </td>
</tr>
<tr>
<td>{$form.activity_location.label}<br />
  {$form.activity_location.html}</td>
<td></td>
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
{include file="CRM/Campaign/Form/addCampaignToSearch.tpl"
campaignTrClass='' campaignTdClass=''}

{if !empty($activityGroupTree)}
  <tr id="activityCustom">
    <td id="activityCustomData" colspan="4">
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
