{if $contributionsCount > 0}
  <details class="crm-accordion-bold" open>
    <summary>{ts}Related Contributions{/ts}</summary>
    <div class="crm-accordion-body">
      <table class="crm-contact-contributions">
        <thead>
        <tr>
          <th class='crm-contact-total_amount'>{ts}Amount{/ts}</th>
          <th class='crm-contact-financial_type_id'>{ts}Type{/ts}</th>
          <th class='crm-contact-contribution_source'>{ts}Source{/ts}</th>
          <th class='crm-contact-receive_date'>{ts}Contribution Date{/ts}</th>
          <th class='crm-contact-thankyou_date'>{ts}Thank-you Sent{/ts}</th>
          <th class='crm-contact-contribution_status'>{ts}Status{/ts}</th>
          <th>&nbsp;</th>
        </tr>
        </thead>
      </table>
      <script type="text/javascript">
        var tableData = {$relatedContributions};
        {literal}
        cj('table.crm-contact-contributions').DataTable({
          data : tableData,
          columns: [
            { data: 'amount_control' },
            { data: 'financial_type' },
            { data: 'contribution_source' },
            { data: 'formatted_receive_date' },
            { data: 'formatted_thankyou_date' },
            { data: 'contribution_status_label' },
            { data: 'action' }
          ]
        });
        {/literal}
      </script>
    </div>
  </details>
{else}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts}No contributions have been recorded for this recurring contribution.{/ts}
  </div>
{/if}
