{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* tpl for building Individual related fields *}
<script type="text/javascript">
{literal}
CRM.$(function($) {
  {/literal}
    var cid = "{$contactId}",
      viewIndividual = "{crmURL p='civicrm/contact/view' q='reset=1&cid=' h=0}",
      checkSimilar = {$checkSimilar},
      lastnameMsg;
  {literal}
  if ($('#contact_sub_type *').length == 0) {//if they aren't any subtype we don't offer the option
    $('#contact_sub_type').parent().hide();
  }
  if (cid.length || !checkSimilar) {
   return;//no dupe check if this is a modif or if checkSimilar is disabled (contact_ajax_check_similar in civicrm_setting table)
  }
  $('#last_name').change(function() {
    // Close msg if it exists
    lastnameMsg && lastnameMsg.close && lastnameMsg.close();
    if (this.value == '') return;
    CRM.api3('contact', 'get', {
      sort_name: $('#last_name').val(),
      contact_type: 'Individual',
      'return': 'display_name,sort_name,email'
    }).done(function(data) {
      var title = data.count == 1 ? {/literal}"{ts escape='js'}Similar Contact Found{/ts}" : "{ts escape='js'}Similar Contacts Found{/ts}"{literal},
        msg = "<em>{/literal}{ts escape='js'}If the person you were trying to add is listed below, click their name to view or edit their record{/ts}{literal}:</em>";
      if (data.is_error == 1 || data.count == 0) {
        return;
      }
      msg += '<ul class="matching-contacts-actions">';
      $.each(data.values, function(i, contact) {
        contact.email = contact.email || '';
        msg += '<li><a href="'+viewIndividual+contact.id+'">'+ contact.display_name +'</a> '+contact.email+'</li>';
      });
      msg += '</ul>';
      lastnameMsg = CRM.alert(msg, title);
      $('.matching-contacts-actions a').click(function() {
        // No confirmation dialog on click
        $('[data-warn-changes=true]').attr('data-warn-changes', 'false');
      });
    });
  });
});
</script>
{/literal}

<table class="form-layout-compressed">
  <tr>
    {if $form.prefix_id}
    <td>
      {$form.prefix_id.label}<br/>
      {$form.prefix_id.html}
    </td>
    {/if}
    {if $form.formal_title}
    <td>
      {$form.formal_title.label}<br/>
      {$form.formal_title.html}
    </td>
    {/if}
    {if $form.first_name}
    <td>
      {$form.first_name.label}<br />
      {$form.first_name.html}
    </td>
    {/if}
    {if $form.middle_name}
    <td>
      {$form.middle_name.label}<br />
      {$form.middle_name.html}
    </td>
    {/if}
    {if $form.last_name}
    <td>
      {$form.last_name.label}<br />
      {$form.last_name.html}
    </td>
    {/if}
    {if $form.suffix_id}
    <td>
      {$form.suffix_id.label}<br/>
      {$form.suffix_id.html}
    </td>
    {/if}
  </tr>

  <tr>
    <td colspan="2">
      {$form.employer_id.label}&nbsp;{help id="id-current-employer" file="CRM/Contact/Form/Contact.hlp"}<br />
      {$form.employer_id.html}
    </td>
    <td>
      {$form.job_title.label}<br />
      {$form.job_title.html}
    </td>
    <td>
      {$form.nick_name.label}<br />
      {$form.nick_name.html}
    </td>
    <td>
      {$form.contact_sub_type.label}<br />
      {$form.contact_sub_type.html}
    </td>
  </tr>
</table>
