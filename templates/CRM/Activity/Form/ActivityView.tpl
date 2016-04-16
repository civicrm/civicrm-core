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
<div class="crm-block crm-content-block crm-activity-view-block">
      {if $activityTypeDescription}
        <div class="help">{$activityTypeDescription}</div>
      {/if}
      <table class="crm-info-panel">
        <tr>
            <td class="label">{ts}Added By{/ts}</td><td class="view-value">{$values.source_contact}</td>
        </tr>
       {if $values.target_contact_value}
           <tr>
                <td class="label">{ts}With Contact{/ts}</td><td class="view-value">{$values.target_contact_value}</td>
           </tr>
       {/if}
       {if $values.mailingId}
           <tr>
                <td class="label">{ts}With Contact{/ts}</td><td class="view-value"><a href="{$values.mailingId}" title="{ts}View Mailing Report{/ts}">&raquo;{ts}Mailing Report{/ts}</a></td>
           </tr>
       {/if}
        <tr>
            <td class="label">{ts}Subject{/ts}</td><td class="view-value">{$values.subject}</td>
        </tr>

  {if $values.campaign}
        <tr>
            <td class="label">{ts}Campaign{/ts}</td><td class="view-value">{$values.campaign}</td>
        </tr>
        {/if}

  {if $values.engagement_level AND
      call_user_func( array( 'CRM_Campaign_BAO_Campaign', 'isCampaignEnable' ) )}
      <td class="label">{ts}Engagement Level{/ts}</td><td class="view-value">{$values.engagement_level}</td>
  {/if}

        <tr>
            <td class="label">{ts}Date and Time{/ts}</td><td class="view-value">{$values.activity_date_time|crmDate }</td>
        </tr>
        {if $values.mailingId}
            <tr>
                <td class="label nowrap">
                   # of opens

                </td>
                <td  class="view-value">{$openreport|@count}
                {if $openreport|@count > 0 and $openreport|@count < 50}<br />Open times:
                  {foreach from=$openreport item=opens}
                    {$opens.date} <br />
                  {/foreach}
                {/if}
             </tr>
             <tr>
               <td class="label">
               # of click-throughs
               </td>
               <td class="view-value"> {$clickreport|@count}
                {if $clickreport|@count > 0 and $clickreport|@count < 50}<br />Click times:
                  {foreach from=$clickreport item=clicks}
                    {$clicks.date}: <a href ='{$clicks.url}'>{$clicks.url|truncate:40:' .... ':true:true}</a> <br />
                  {/foreach}
                {/if}

                </td>
            </tr>
            <tr>
                <td class="label">{ts}Details{/ts}</td>
                <td class="view-value report">

                    <fieldset>
                    <legend>{ts}Content / Components{/ts}</legend>
                    {strip}
                    <table class="form-layout-compressed">
                      {if $mailingReport.mailing.body_text}
                          <tr>
                              <td class="label nowrap">{ts}Text Message{/ts}</td>
                              <td>
                                  {$mailingReport.mailing.body_text|mb_truncate:30|escape|nl2br}
                                  <br />
                                  {if $values.mailingId}
                                    <strong><a class="crm-popup" href='{$textViewURL}'>&raquo; {ts}View complete message{/ts}</a></strong>
                                  {/if}
                              </td>
                          </tr>
                      {/if}

                      {if $mailingReport.mailing.body_html}
                          <tr>
                              <td class="label nowrap">{ts}HTML Message{/ts}</td>
                              <td>
                                  {$mailingReport.mailing.body_html|mb_truncate:30|escape|nl2br}
                                  <br/>
                                  {if $values.mailingId}
                                    <strong><a class="crm-popup" href='{$htmlViewURL}'>&raquo; {ts}View complete message{/ts}</a></strong>
                                  {/if}
                              </td>
                          </tr>
                      {/if}

                      {if $mailingReport.mailing.attachment}
                          <tr>
                              <td class="label nowrap">{ts}Attachments{/ts}</td>
                              <td>
                                  {$mailingReport.mailing.attachment}
                              </td>
                              </tr>
                      {/if}

                    </table>
                    {/strip}
                    </fieldset>
                </td>
            </tr>
        {else}
             <tr>
                 <td class="label">{ts}Details{/ts}</td><td class="view-value report">{$values.details|crmStripAlternatives|nl2br}</td>
             </tr>
        {/if}
{if $values.attachment}
        <tr>
            <td class="label">{ts}Attachment(s){/ts}</td><td class="view-value report">{$values.attachment}</td>
        </tr>
{/if}
     </table>
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

