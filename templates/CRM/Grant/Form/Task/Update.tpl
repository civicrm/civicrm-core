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
{* Update Grants *} 
<div class="crm-block crm-form-block crm-grants-update-form-block">
    <p>{ts}Enter values for the fields you wish to update. Leave fields blank to preserve existing values.{/ts}</p>
    <table class="form-layout-compressed">
        {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
     <tr class="crm-contact-custom-search-form-row-status_id">
       <td class="label">{$form.status_id.label}</td>
       <td>{$form.status_id.html}</td>
    </tr>
    <tr class="crm-contact-custom-search-form-row-radio_ts">
       <td class="label">{$form.radio_ts.amount_granted.label}</td>
       <td>{$form.radio_ts.amount_granted.html}</td>
    </tr>
    <tr class="crm-contact-custom-search-form-row-radio_ts">
       <td class="label"></td>
       <td>{$form.amount_granted.html}</td>
    </tr>
    <tr class="crm-contact-custom-search-form-row-radio_ts">
       <td class="label">{$form.radio_ts.amount_total.label}</td>
       <td>{$form.radio_ts.amount_total.html}</td>
    </tr>

    <tr class="crm-contact-custom-search-form-row-decision_date">
       <td class="label">{$form.decision_date.label}</td>
                    <td>{include file="CRM/common/jcalendar.tpl" elementName=decision_date}<br />
                    <span class="description">{ts}Date on which the grant decision was finalized.{/ts}</span></td>
            </tr>
    </table>
    <p>{ts 1=$totalSelectedGrants}Number of selected grants: %1{/ts}</p>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div><!-- /.crm-form-block -->

<script type="text/javascript">
    {literal} 

cj("#CIVICRM_QFID_amount_total_4").click(function() {
   cj("#amount_total").show();
   cj("#amount_granted").hide();
   cj("#amount_granted").val(null);
});

cj("#CIVICRM_QFID_amount_granted_2").click(function() {
   cj("#amount_granted").show();
});
    {/literal}
</script>