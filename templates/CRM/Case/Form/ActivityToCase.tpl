{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* CiviCase - assign activity to case form *}
{if !empty($buildCaseActivityForm)}
  <div class="crm-block crm-form-block crm-case-activitytocase-form-block">
    <table class="form-layout">
      <tr class="crm-case-activitytocase-form-block-file_on_case_unclosed_case_id">
        <td class="label">{$form.file_on_case_unclosed_case_id.label}</td>
        <td>{$form.file_on_case_unclosed_case_id.html}</td>
      </tr>
      <tr class="crm-case-activitytocase-form-block-file_on_case_target_contact_id">
        <td class="label">{$form.file_on_case_target_contact_id.label}</td>
        <td>{$form.file_on_case_target_contact_id.html}</td>
      </tr>
      <tr class="crm-case-activitytocase-form-block-file_on_case_activity_subject">
        <td class="label">{$form.file_on_case_activity_subject.label}</td>
        <td>{$form.file_on_case_activity_subject.html}<br />
          <span class="description">{ts}You can modify the activity subject before filing.{/ts}</span>
        </td>
      </tr>
    </table>
  </div>
{* main form end *}

{else}
{* Markup and js to go on the main page for loading the above form in a popup *}
{literal}
<script type="text/javascript">
(function($) {
  window.fileOnCase = function(action, activityID, currentCaseId, a) {
    if ( action == "move" ) {
      var dialogTitle = "{/literal}{ts escape='js'}Move to Case{/ts}{literal}";
    } else if ( action == "copy" ) {
      var dialogTitle = "{/literal}{ts escape='js'}Copy to Case{/ts}{literal}";
    } else if ( action == "file" ) {
      var dialogTitle = "{/literal}{ts escape='js'}File on Case{/ts}{literal}";
    }

    var dataUrl = {/literal}"{crmURL p='civicrm/case/addToCase' q='reset=1' h=0}"{literal};
    dataUrl += '&activityId=' + activityID + '&caseId=' + currentCaseId + '&cid=' + {/literal}"{$contactID}"{literal};

    function save() {
      if (!$("#file_on_case_unclosed_case_id").val()) {
        $("#file_on_case_unclosed_case_id").crmError('{/literal}{ts escape="js"}Please select a case from the list{/ts}{literal}.');
        return false;
      }

      var $context = $('div.crm-confirm-dialog'),
        selectedCaseId = $('input[name=file_on_case_unclosed_case_id]', $context).val(),
        caseTitle = $('input[name=file_on_case_unclosed_case_id]', $context).select2('data').label,
        contactId = $('input[name=file_on_case_unclosed_case_id]', $context).select2('data').extra.contact_id,
        subject = $("#file_on_case_activity_subject").val(),
        targetContactId = $("#file_on_case_target_contact_id").val();

      var postUrl = {/literal}"{crmURL p='civicrm/ajax/activity/convert' h=0 }"{literal};
      $.post( postUrl, { activityID: activityID, caseID: selectedCaseId, contactID: contactId, newSubject: subject, targetContactIds: targetContactId, mode: action, key: {/literal}"{crmKey name='civicrm/ajax/activity/convert'}"{literal} },
        function( values ) {
          if ( values.error_msg ) {
            $().crmError(values.error_msg, "{/literal}{ts escape='js'}Unable to file on case.{/ts}{literal}");
          } else {
            var destUrl = {/literal}"{crmURL p='civicrm/contact/view/case' q='reset=1&action=view&id=' h=0 }"{literal};
            var context = '';
            {/literal}{if !empty($fulltext)}{literal}
            context = '&context={/literal}{$fulltext}{literal}';
            {/literal}{/if}{literal}
            var caseUrl = destUrl + selectedCaseId + '&cid=' + contactId + context;

            var statusMsg = {/literal}'{ts escape='js' 1='%1'}Activity has been filed to %1 case.{/ts}'{literal};
            CRM.alert(ts(statusMsg, {1: '<a href="' + caseUrl + '">' + caseTitle + '</a>'}), '{/literal}{ts escape="js"}Saved{/ts}{literal}', 'success');
            CRM.refreshParent(a);
          }
        }
      );
    }

    CRM.confirm({
      title: dialogTitle,
      width: '600',
      resizable: true,
      options: {yes: "{/literal}{ts escape='js'}Save{/ts}{literal}", no: "{/literal}{ts escape='js'}Cancel{/ts}{literal}"},
      url: dataUrl
    }).on('crmConfirm:yes', save);

  }
})(CRM.$);
</script>
{/literal}
{/if}
