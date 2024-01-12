{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-content-block crm-activity-view-block">
      {if $activityTypeDescription}
        <div class="help">{$activityTypeDescription|purify}</div>
      {/if}
      <table class="crm-info-panel">
        <tr>
            <td class="label">{ts}Added by{/ts}</td><td class="view-value">{$values.source_contact}</td>
        </tr>
       {if array_key_exists('target_contact_value', $values)}
           <tr>
                <td class="label">{ts}With Contact{/ts}</td><td class="view-value">{$values.target_contact_value}</td>
           </tr>
       {/if}
       {if (array_key_exists('mailingId', $values) && $values.mailingId)}
           <tr>
                <td class="label">{ts}With Contact{/ts}</td><td class="view-value"><a href="{$values.mailingId}" title="{ts}View Mailing Report{/ts}"><i class="crm-i fa-chevron-right" aria-hidden="true"></i>{ts}Mailing Report{/ts}</a></td>
           </tr>
       {/if}
        <tr>
            <td class="label">{ts}Subject{/ts}</td><td class="view-value">{$values.subject}</td>
        </tr>

  {if array_key_exists('campaign', $values)}
        <tr>
            <td class="label">{ts}Campaign{/ts}</td><td class="view-value">{$values.campaign}</td>
        </tr>
        {/if}

  {if array_key_exists('engagement_level', $values) AND
      call_user_func( array( 'CRM_Campaign_BAO_Campaign', 'isComponentEnabled' ) )}
      <td class="label">{ts}Engagement Level{/ts}</td><td class="view-value">{$values.engagement_level}</td>
  {/if}

        <tr>
            <td class="label">{ts}Date and Time{/ts}</td><td class="view-value">{$values.activity_date_time|crmDate}</td>
        </tr>
        {if (array_key_exists('mailingId', $values) && $values.mailingId)}
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
                                    <strong><a class="crm-popup" href='{$textViewURL}'><i class="crm-i fa-chevron-right" aria-hidden="true"></i> {ts}View complete message{/ts}</a></strong>
                              </td>
                          </tr>
                      {/if}

                      {if $mailingReport.mailing.body_html}
                          <tr>
                              <td class="label nowrap">{ts}HTML Message{/ts}</td>
                              <td>
                                  {$mailingReport.mailing.body_html|mb_truncate:30|escape|nl2br}
                                  <br/>
                                    <strong><a class="crm-popup" href='{$htmlViewURL}'><i class="crm-i fa-chevron-right" aria-hidden="true"></i> {ts}View complete message{/ts}</a></strong>
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
                 <td class="label">{ts}Details{/ts}</td><td class="view-value report">
                   {if array_key_exists('details', $values)}{$values.details|crmStripAlternatives|purify|nl2brIfNotHTML}{/if}
                 </td>
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
