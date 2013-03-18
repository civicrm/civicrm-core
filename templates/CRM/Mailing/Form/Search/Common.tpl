<tr>
  <td colspan="2">
  {$form.mailing_id.label}
    <br />
  {$form.mailing_id.html}
  {literal}
    <script type="text/javascript">
      cj("select#mailing_id").crmasmSelect({
        addItemTarget: 'bottom',
        animate: false,
        highlight: true,
        sortable: true
      });
    </script>
  {/literal}
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
    <span class="crm-clear-link">
      (<a href="#" onclick="unselectRadio('mailing_delivery_status','{$form.formName}'); return false;">{ts}clear{/ts}</a>)
    </span>
  </td>
  <td>
  {$form.mailing_open_status.label}
    <br />
  {$form.mailing_open_status.html}
    <span class="crm-clear-link">
      (<a href="#" onclick="unselectRadio('mailing_open_status','{$form.formName}'); return false;">{ts}clear{/ts}</a>)
    </span>
  </td>
</tr>
<tr>
  <td>
  {$form.mailing_click_status.label}
    <br />
  {$form.mailing_click_status.html}
    <span class="crm-clear-link">
      (<a href="#" onclick="unselectRadio('mailing_click_status','{$form.formName}'); return false;">{ts}clear{/ts}</a>)
    </span>
  </td>
  <td>
  {$form.mailing_reply_status.label}
    <br />
  {$form.mailing_reply_status.html}
    <span class="crm-clear-link">
      (<a href="#" onclick="unselectRadio('mailing_reply_status','{$form.formName}')">{ts}clear{/ts}</a>)
    </span>
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
