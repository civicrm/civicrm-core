<tr>
  <td>
  {$form.mailing_id.label}
    <br />
  {$form.mailing_id.html}
  </td>
<td>
  {$form.mailing_job_status.label}
    <br />
  {$form.mailing_job_status.html}
</td>
</tr>
<tr><td><label>{ts}Mailing Date{/ts}</label></td></tr>
<tr>
{include file="CRM/Core/DateRange.tpl" fieldName="mailing_date" from='_low' to='_high'}
</tr>
<tr>
  <td>
  {$form.mailing_delivery_status.label}
    <br />
  {$form.mailing_delivery_status.html}
  <br />
  {$form.mailing_bounce_types.label}
  {$form.mailing_bounce_types.html}
  </td>
  <td>
  {$form.mailing_open_status.label}
    <br />
  {$form.mailing_open_status.html}
  </td>
</tr>
<tr>
  <td>
  {$form.mailing_click_status.label}
    <br />
  {$form.mailing_click_status.html}
  </td>
  <td>
  {$form.mailing_reply_status.label}
    <br />
  {$form.mailing_reply_status.html}
  </td>
</tr>
<tr>
  <td>
    <table>
      <tr>
      {$form.mailing_unsubscribe.html}&nbsp;
      {$form.mailing_unsubscribe.label}
      </tr>
    </table>
  </td>
  <td>
    <table>
      <tr>
        <td>
        {$form.mailing_optout.html}&nbsp;
        {$form.mailing_optout.label}
        </td>
        <td>
        {$form.mailing_forward.html}&nbsp;
        {$form.mailing_forward.label}
        </td>
      </tr>
    </table>
  </td>
</tr>
<tr>
  <td>{* campaign in Advance search *}
      {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignContext="componentSearch"
       campaignTrClass='crmCampaign' campaignTdClass='crmCampaignContainer'}
  </td>
</tr>
