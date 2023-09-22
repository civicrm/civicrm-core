{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $notConfigured} {* Case types not present. Component is not configured for use. *}
  {include file="CRM/Case/Page/ConfigureError.tpl"}
{else}
  <tr>
    <td class="crm-case-common-form-block-case_id">
      {$form.case_id.label}<br />
      {$form.case_id.html}
    </td>
    <td class="crm-case-common-form-block-case_subject" colspan="2">
      {$form.case_subject.label}<br />
      {$form.case_subject.html}
    </td>
  </tr>

  <tr>
    {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="case_start_date" to='' from='' colspan='' class ='' hideRelativeLabel=0}
  </tr>
  <tr>
    {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="case_end_date" to='' from='' colspan='' class ='' hideRelativeLabel=0}
  </tr>

  <tr id='case_search_form'>
    <td class="crm-case-common-form-block-case_type">
      {$form.case_type_id.label}<br />
      {$form.case_type_id.html}
    </td>

    <td class="crm-case-common-form-block-case_status_id">
      {$form.case_status_id.label}<br />
      {$form.case_status_id.html}
      {if $accessAllCases}
        <br />
        {$form.case_owner.html}
      {/if}
      {if !empty($form.case_deleted)}
        <br />
        {$form.case_deleted.html}
        {$form.case_deleted.label}
      {/if}
    </td>
    <td class="crm-case-common-form-block-case_tags">
      {if !empty($form.case_tags.html)}
        {$form.case_tags.label}<br />
        {$form.case_tags.html}
      {/if}
    </td>
  </tr>

  <tr>
    <td colspan="3">{include file="CRM/common/Tagset.tpl" tagsetType='case'}</td>
  </tr>

  {if !empty($caseGroupTree)}
    <tr>
      <td colspan="3">
        {include file="CRM/Custom/Form/Search.tpl" groupTree=$caseGroupTree showHideLinks=false}
      </td>
    </tr>
  {/if}

{/if}
