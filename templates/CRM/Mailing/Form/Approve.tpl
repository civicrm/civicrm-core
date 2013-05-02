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

<div class="crm-block crm-form-block crm-mailing-approve-form-block">

<table class="form-layout">
  <tbody>
    <tr class="crm-mailing-approve-form-block-approval_status">
        <td class="label">{$form.approval_status_id.label}</td>
        <td>{$form.approval_status_id.html}</td>
    </tr>
    <tr class="crm-mailing-approve-form-block-approval_note">
        <td class="label">{$form.approval_note.label}</td>
        <td>{$form.approval_note.html}</td>
    </tr>
  </tbody>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>

<div class="crm-accordion-wrapper crm-plain_text_email-accordion collapsed">
    <div class="crm-accordion-header">
        {ts}Preview Mailing{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <table class="form-layout">
          <tr class="crm-mailing-test-form-block-subject"><td class="label">{ts}Subject:{/ts}</td><td>{$preview.subject}</td></tr>
    {if $preview.attachment}
          <tr class="crm-mailing-test-form-block-attachment"><td class="label">{ts}Attachment(s):{/ts}</td><td>{$preview.attachment}</td></tr>
    {/if}
          {if $preview.viewURL}
    <tr><td class="label">{if $preview.type eq 'html'}{ts}Mailing HTML:{/ts}{else}{ts}Mailing Text:{/ts}{/if}</td><td><iframe height="300" src="{$preview.viewURL}" width="80%"><a href="{$preview.viewURL}" onclick="window.open(this.href); return false;">{ts}Mailing Text:{/ts}</a></iframe></td></tr>
          {/if}
        </table>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

</div>

{literal}
<script type="text/javascript">
cj(function() {
   cj().crmAccordions();
});
</script>
{/literal}

