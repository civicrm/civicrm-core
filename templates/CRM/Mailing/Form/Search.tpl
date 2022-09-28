{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-search-form-block">
  <table class="form-layout">
    <tr>
       <td>{$form.mailing_name.label} {help id="id-mailing_name"}<br />
        {$form.mailing_name.html|crmAddClass:big}
      </td>
    </tr>
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="mailing" to='' from='' colspan='2' class='' hideRelativeLabel=0}
    </tr>
    <tr>
      <td colspan="1">{$form.sort_name.label} {help id="id-create_sort_name"}<br />
        {$form.sort_name.html|crmAddClass:big}
        <br/><br/>
        <div class="crm-search-form-block-is_archive">
          {$form.is_archived.label} {help id="is_archived"}<br/>
          {$form.is_archived.html}
        </div>
      </td>
      {if !empty($form.mailing_status)}
         <td width="100%"><label>{if $sms eq 1}{ts}SMS Status{/ts}{else}{ts}Mailing Status{/ts}{/if}</label><br />
           <div class="listing-box" style="height: auto">
             {foreach from=$form.mailing_status item="mailing_status_val"}
               <div class="{cycle values="odd-row,even-row"}">
                 {$mailing_status_val.html}
               </div>
            {/foreach}
            <div class="{cycle values="odd-row,even-row"}">
              {$form.status_unscheduled.html}
            </div>
          </div><br />
        </td>
      {/if}
    </tr>

    {* language *}
    {if array_key_exists('language', $form)}
      <tr>
        <td>{$form.language.label} {help id="id-language"}<br />
          {$form.language.html|crmAddClass:big}
        </td>
      </tr>
    {/if}

    {* campaign in mailing search *}
    {include file="CRM/Campaign/Form/addCampaignToSearch.tpl"
      campaignTrClass='' campaignTdClass=''}

    <tr>
      <td>{$form.buttons.html}</td><td colspan="2"></td>
    </tr>
  </table>
</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var archiveOption = $("input[name^='is_archived']:radio");
    $('#status_unscheduled').change(function() {
      if ($(this).prop('checked') ) {
        archiveOption.prop({checked: false, disabled: true}).change();
      } else {
        archiveOption.prop('disabled', false);
      }
    }).trigger('change');
    archiveOption.change(function() {
      if ($("input[name^='is_archived']:radio:checked").length) {
        $('#status_unscheduled').prop({checked: false, disabled: true}).change();
      } else {
        $('#status_unscheduled').prop('disabled', false);
      }
    }).trigger('change');
  });
</script>
{/literal}
