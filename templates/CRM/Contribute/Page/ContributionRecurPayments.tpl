{if $contributionsCount > 0}
  <div class="crm-accordion-wrapper">
    <div class="crm-accordion-header">{ts}Related Contributions{/ts}</div>
    <div class="crm-accordion-body">
      <table class="crm-contact-contributions">
        <thead>
        <tr>
          <th class='crm-contact-total_amount'>{ts}Amount{/ts}</th>
          <th class='crm-contact-financial_type_id'>{ts}Type{/ts}</th>
          <th class='crm-contact-contribution_source'>{ts}Source{/ts}</th>
          <th class='crm-contact-receive_date'>{ts}Recieved{/ts}</th>
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
  </div>
{else}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {ts}No contributions have been recorded for this recurring contribution.{/ts}
  </div>
{/if}
