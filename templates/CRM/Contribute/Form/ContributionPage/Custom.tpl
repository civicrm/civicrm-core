{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="contribute-form-contributionpage-custom-main"}
<div class="crm-block crm-form-block crm-contribution-contributionpage-custom-form-block">
<div class="help">
    <p>{ts}You may want to collect information from contributors beyond what is required to make a contribution. For example, you may want to inquire about volunteer availability and skills. Add any number of fields to your contribution form by selecting CiviCRM Profiles (collections of fields) to include at the beginning of the page, and/or at the bottom.{/ts} {help id="contrib-profile"}</p>
    <p>{ts}To create a new profile, go to <a href="{crmURL p="civicrm/admin/uf/group" q="reset=1"}" target="_blank">Administer Profiles</a>.{/ts}</p>
</div>
    <table class="form-layout-compressed">
    <tr class="crm-contribution-contributionpage-custom-form-block-custom_pre_id">
       <td class="label">{$form.custom_pre_id.label} {help id="contrib-profile-top"}</td>
       <td class="html-adjust">{$form.custom_pre_id.html}
         <a href="#" class="crm-button crm-popup">{icon icon="fa-list-alt"}{/icon} {ts}Fields{/ts}</a>
       </td>
    </tr>
    <tr class="crm-contribution-contributionpage-custom-form-block-custom_post_id">
       <td class="label">{$form.custom_post_id.label} {help id="contrib-profile-bottom"}</td>
       <td class="html-adjust">{$form.custom_post_id.html}
         <a href="#" class="crm-button crm-popup">{icon icon="fa-list-alt"}{/icon} {ts}Fields{/ts}</a>
       </td>
    </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{/crmRegion}
{crmRegion name="contribute-form-contributionpage-custom-post"}
{/crmRegion}
