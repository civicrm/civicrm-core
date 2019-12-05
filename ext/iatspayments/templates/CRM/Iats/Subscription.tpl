{*
 Extra fields for updating the subscription, buried in a fake table so I
 can move the rows where they belong
*}
<table id="contributionrecur-extra">
<tr class="contribution-status-id"><td class="label">{$form.contribution_status_id.label}</td>
        <td class="content">{$form.contribution_status_id.html}</td></tr>
<tr class="payment-processor-id"><td class="label">{$form.payment_processor_id.label}</td>
        <td class="content">{$form.payment_processor_id.html}</td></tr>
<tr class="start-date"><td class="label">{$form.start_date.label}</td>
        <td class="content">{$form.start_date.html}</td></tr>
<tr class="next-sched-contribution-date"><td class="label">{$form.next_sched_contribution_date.label}</td>
        <td class="content">{$form.next_sched_contribution_date.html}</td></tr>
<tr class="is-email-receipt"><td class="label">{$form.is_email_receipt.label}</td>
        <td class="content">{$form.is_email_receipt.html}</td></tr>
</table>
<table id="contributionrecur-info">
<tr><td class="label">Contact Name</td>
	<td class="content"><strong>{$form.contact.label}</strong></td></tr>
<tr><td class="label">Payment Processor</td>
	<td class="content"><strong>{$form.payment_processor.label}</strong></td></tr>
<tr><td class="label">Financial Type</td>
	<td class="content"><strong>{$form.financial_type.label}</strong></td></tr>
<tr><td class="label">Payment Instrument</td>
	<td class="content"><strong>{$form.payment_instrument.label}</strong></td></tr>
<tr><td class="label">Failure Count</td>
	<td class="content"><strong>{$form.failure_count.label}</strong></td></tr>
</table>
