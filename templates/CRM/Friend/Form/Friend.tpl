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
<div id="form" class="crm-form-block crm-friend-manage-block">
<div id="help">
  {if $context EQ 'Contribute'}
    {assign var=enduser value="contributor"}
    {assign var=pageType value="Online Contribution page"}
    {ts}Tell a Friend gives your contributors an easy way to spread the word about this fundraising campaign. The contribution thank-you page will include a link to a form where they can enter their friends' email addresses, along with a personalized message. CiviCRM will record these solicitation activities, and will add the friends to your database.{/ts}
  {elseif $context EQ 'Event'}
    {assign var=enduser value="participant"}
    {assign var=pageType value="Event Information page"}
    {ts}Tell a Friend gives registering participants an easy way to spread the word about this event. The registration thank-you page will include a link to a form where they can enter their friends' email addresses, along with a personalized message. CiviCRM will record these solicitation activities, and will add the friends to your database.{/ts}
  {elseif $context EQ 'Pledge'}
    {assign var=enduser value="pledge"}
    {assign var=pageType value="Pledge Information page"}
    {ts}Tell a Friend gives registering pledge signers an easy way to spread the word about this pledge. The registration thank-you page will include a link to a form where they can enter their friends' email addresses, along with a personalized message. CiviCRM will record these solicitation activities, and will add the friends to your database.{/ts}
  {/if}
  {ts}If sharing through social media is enabled, links allowing people to share with their social network will also be included on the Tell a Friend form (e.g. Facebook "Like", Google+, and Twitter). You can turn social media sharing on and off from the Settings tab.{/ts}
</div>
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
        <tr>
           <td></td>
        </tr>
        <tr class="crm-friend-manage-form-block-tf_is_active">
           <td class="label"">{$form.tf_is_active.html}</td>
           <td>{$form.tf_is_active.label}</td>
        </tr>
    </table>
    <div id="friendFields">
    <table class="form-layout">
        <tr class="crm-friend-manage-form-block-tf_title">
     <td class="label">{$form.tf_title.label}
         {if $action == 2}
              {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_tell_friend' field='title' id=$friendId}
         {/if}
           </td>
     <td>{$form.tf_title.html}
     </td>
        </tr>
        <tr class="crm-friend-manage-form-block-intro">
     <td class="label">{$form.intro.label}
         {if $action == 2}
              {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_tell_friend' field='intro' id=$friendId}
         {/if}
     </td>
     <td>{$form.intro.html}<br />
               <span class="description">{ts 1=$enduser}This message is displayed to the %1 at the top of the Tell a Friend form. You may include HTML tags to add formatting or links.{/ts}
         </span>
     </td>
        </tr>
        <tr class="crm-friend-manage-form-block-suggested_message">
     <td class="label">{$form.suggested_message.label}
         {if $action == 2}
              {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_tell_friend' field='suggested_message' id=$friendId}
         {/if}
     </td>
     <td>{$form.suggested_message.html}<br />
               <span class="description">{ts 1=$enduser}Provides the %1 with suggested text for their personalized message to their friends.{/ts}
         </span>
     </td>
        </tr>
        <tr class="crm-friend-manage-form-block-general_link">
     <td class="label">{$form.general_link.label}</td>
     <td>{$form.general_link.html}<br />
               <span class="description">{ts 1=$pageType}A link to this %1 is automatically included in the email sent to friends. If you ALSO want to include a link providing general information about your organization, enter that link here (e.g <em>http://www.example.org/</em>){/ts}
         </span>
     </td>
        </tr>
        <tr class="crm-friend-manage-form-block-thankyou_title">
           <td class="label">{$form.tf_thankyou_title.label}
         {if $action == 2}
              {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_tell_friend' field='thankyou_title' id=$friendId}{/if}</td>
     <td>{$form.tf_thankyou_title.html}</td>
        </tr>
        <tr class="crm-friend-manage-form-block-thankyou_text">
     <td class="label">{$form.tf_thankyou_text.label}
         {if $action == 2}
              {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_tell_friend' field='thankyou_text' id=$friendId}
         {/if}
     </td>
     <td>{$form.tf_thankyou_text.html}<br />
               <span class="description">{ts 1=$enduser}Your message thanking the %1 for helping to spread the word. You may include HTML tags to add formatting or links.{/ts}
         </span>
     </td>
        </tr>
    </table>
    </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">
  var is_act = document.getElementsByName('tf_is_active');
    if ( ! is_act[0].checked) {
           cj('#friendFields').hide();
  }
       function friendBlock(chkbox) {
           if (chkbox.checked) {
        cj('#friendFields').show();
        return;
           } else {
        cj('#friendFields').hide();
            return;
     }
       }
</script>
{/literal}
