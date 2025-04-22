<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
  <title></title>
</head>
<body>

{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
{capture assign=labelStyle}style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
{capture assign=valueStyle}style="padding: 4px; border-bottom: 1px solid #999;"{/capture}
{capture assign=tdfirstStyle}style="width: 180px; padding-bottom: 15px;"{/capture}
{capture assign=tdStyle}style="width: 100px;"{/capture}
{capture assign=participantTotalStyle}style="margin: 0.5em 0 0.5em;padding: 0.5em;background-color: #999999;font-weight: bold;color: #FAFAFA;border-radius: 2px;"{/capture}

  <!-- BEGIN HEADER -->
    {* To modify content in this section, you can edit the Custom Token named "Message Header". See also: https://docs.civicrm.org/user/en/latest/email/message-templates/#modifying-system-workflow-message-templates *}
    {site.message_header}
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <table id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">
  <tr>
   <td>
    {assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}

    {if $userText}
     <p>{$userText}</p>
    {/if}

    {if !empty($isOnWaitlist)}
      <p>{ts}You have been added to the WAIT LIST for this event.{/ts}</p>
      <p>{ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}</p>
    {elseif !empty($isRequireApproval)}
      <p>{ts}Your registration has been submitted.{/ts}</p>
      <p>{ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}</p>
    {elseif {contribution.is_pay_later|boolean} && {contribution.balance_amount|boolean}}
     <p>{event.pay_later_receipt}</p> {* FIXME: this might be text rather than HTML *}
    {/if}

   </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     <tr>
      <th {$headerStyle}>
       {ts}Event Information and Location{/ts}
      </th>
     </tr>
     <tr>
      <td colspan="2" {$valueStyle}>
       {event.title}<br />
       {event.start_date|crmDate}{if {event.end_date|boolean}}-{if '{event.end_date|crmDate:"%Y%m%d"}' === '{event.start_date|crmDate:"%Y%m%d"}'}{event.end_date|crmDate:"Time"}{else}{event.end_date}{/if}{/if}
      </td>
     </tr>

     {if {event.is_show_location|boolean}}
      <tr>
       <td colspan="2" {$valueStyle}>
         {event.location}
       </td>
      </tr>
     {/if}

     {if {event.loc_block_id.phone_id.phone|boolean} || {event.loc_block_id.email_id.email|boolean}}
       <tr>
         <td colspan="2" {$labelStyle}>
           {ts}Event Contacts:{/ts}
         </td>
       </tr>

       {if {event.loc_block_id.phone_id.phone|boolean}}
         <tr>
           <td {$labelStyle}>
             {if {event.loc_block_id.phone_id.phone_type_id|boolean}}
               {event.loc_block_id.phone_id.phone_type_id:label}
             {else}
               {ts}Phone{/ts}
             {/if}
           </td>
         <td {$valueStyle}>
          {event.loc_block_id.phone_id.phone} {if {event.loc_block_id.phone_id.phone_ext|boolean}}&nbsp;{ts}ext.{/ts} {event.loc_block_id.phone_id.phone_ext}{/if}
         </td>
        </tr>
       {/if}
       {if {event.loc_block_id.phone_2_id.phone|boolean}}
         <tr>
           <td {$labelStyle}>
             {if {event.loc_block_id.phone_2_id.phone_type_id|boolean}}
               {event.loc_block_id.phone_2_id.phone_type_id:label}
             {else}
               {ts}Phone{/ts}
             {/if}
           </td>
           <td {$valueStyle}>
             {event.loc_block_id.phone_2_id.phone} {if {event.loc_block_id.phone_2_id.phone_ext|boolean}}&nbsp;{ts}ext.{/ts} {event.loc_block_id.phone_2_id.phone_ext}{/if}
           </td>
         </tr>
       {/if}

       {if {event.loc_block_id.email_id.email|boolean}}
        <tr>
         <td {$labelStyle}>
          {ts}Email{/ts}
         </td>
         <td {$valueStyle}>
           {event.loc_block_id.email_id.email}
         </td>
        </tr>
       {/if}

       {if {event.loc_block_id.email_2_id.email|boolean}}
         <tr>
           <td {$labelStyle}>
             {ts}Email{/ts}
           </td>
           <td {$valueStyle}>
               {event.loc_block_id.email_2_id.email}
           </td>
         </tr>
       {/if}
     {/if}

     {if {event.is_public|boolean} and {event.is_show_calendar_links|boolean}}
      <tr>
       <td colspan="2" {$valueStyle}>
        {capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id={event.id}" h=0 a=1 fe=1}{/capture}
        <a href="{$icalFeed}">{ts}Download iCalendar entry for this event.{/ts}</a>
       </td>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {capture assign=gCalendar}{crmURL p='civicrm/event/ical' q="gCalendar=1&reset=1&id={event.id}" h=0 a=1 fe=1}{/capture}
         <a href="{$gCalendar}">{ts}Add event to Google Calendar{/ts}</a>
       </td>
      </tr>
     {/if}

     {if {contact.email_primary.email|boolean}}
      <tr>
       <th {$headerStyle}>
        {ts}Registered Email{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
         {contact.email_primary.email}
       </td>
      </tr>
     {/if}


     {if {event.is_monetary|boolean}}
      <tr>
        <th {$headerStyle}>
            {event.fee_label}
        </th>
      </tr>
        {if $isShowLineItems}
          {foreach from=$participants key=index item=currentParticipant}
            {if $isPrimary || {participant.id} === $currentParticipant.id}
              {if $isPrimary && ($participants|@count > 1)} {* Header for multi participant registration cases. *}
                <tr>
                  <td colspan="2" {$labelStyle}>
                    {$currentParticipant.contact.display_name}
                  </td>
                </tr>
              {/if}
              <tr>
                <td colspan="2" {$valueStyle}>
                  <table>
                    <tr>
                      <th>{ts}Item{/ts}</th>
                      <th>{ts}Qty{/ts}</th>
                      <th>{ts}Each{/ts}</th>
                      {if $isShowTax && {contribution.tax_amount|boolean}}
                        <th>{ts}Subtotal{/ts}</th>
                        <th>{ts}Tax Rate{/ts}</th>
                        <th>{ts}Tax Amount{/ts}</th>
                      {/if}
                      <th>{ts}Total{/ts}</th>
                        {if $isShowParticipantCount}
                          <th>{ts}Total Participants{/ts}</th>
                        {/if}
                    </tr>
                    {foreach from=$currentParticipant.line_items item=line}
                      <tr>
                        <td {$tdfirstStyle}>{$line.title}</td>
                        <td {$tdStyle} align="middle">{$line.qty}</td>
                        <td {$tdStyle}>{$line.unit_price|crmMoney:$currency}</td>
                          {if $isShowTax && {contribution.tax_amount|boolean}}
                            <td>{$line.line_total|crmMoney:$currency}</td>
                            {if $line.tax_rate || $line.tax_amount != ""}
                              <td>{$line.tax_rate|string_format:"%.2f"}%</td>
                              <td>{$line.tax_amount|crmMoney:$currency}</td>
                            {else}
                              <td></td>
                              <td></td>
                            {/if}
                          {/if}
                        <td {$tdStyle}>
                            {$line.line_total_inclusive|crmMoney:$currency}
                        </td>
                        {if $isShowParticipantCount}
                          <td {$tdStyle}>{$line.participant_count}</td>
                        {/if}
                      </tr>
                    {/foreach}
                    {if $isShowTax && $isPrimary && ($participants|@count > 1)}
                      <tr {$participantTotalStyle}>
                        <td colspan=3>{ts 1=$currentParticipant.contact.display_name}Total for %1{/ts}</td>
                        <td colspan=2>{$currentParticipant.totals.total_amount_exclusive|crmMoney}</td>
                        <td colspan=1>{$currentParticipant.totals.tax_amount|crmMoney}</td>
                        <td colspan=2>{$currentParticipant.totals.total_amount_inclusive|crmMoney}</td>
                      </tr>
                    {/if}
                  </table>
                </td>
              </tr>
            {/if}
          {/foreach}
        {/if}
        {if !$isShowLineItems}
          {foreach from=$participants key=index item=currentParticipant}
            {if $isPrimary || {participant.id} === $currentParticipant.id}
              {foreach from=$currentParticipant.line_items key=index item=currentLineItem}
                <tr>
                  <td {$valueStyle}>
                    {$currentLineItem.label}{if $isPrimary && ($participants|@count > 1)} - {$currentParticipant.contact.display_name}{/if}
                  </td>
                  <td {$valueStyle}>
                    {$currentLineItem.line_total|crmMoney:$currency}
                  </td>
                </tr>
              {/foreach}
            {/if}
          {/foreach}
       {/if}
        {if $isShowTax && {contribution.tax_amount|boolean}}
          <tr>
            <td {$labelStyle}>
                {ts}Amount Before Tax:{/ts}
            </td>
            <td {$valueStyle}>
                {if $isPrimary}{contribution.tax_exclusive_amount}{else}{$participant.totals.total_amount_exclusive|crmMoney}{/if}
            </td>
          </tr>
          {if !$isPrimary}
            {* Use the participant specific tax rate breakdown *}
            {assign var=taxRateBreakdown value=$participant.tax_rate_breakdown}
          {/if}
          {foreach from=$taxRateBreakdown item=taxDetail key=taxRate}
            <tr>
              <td {$labelStyle}>{if $taxRate == 0}{ts}No{/ts} {$taxTerm}{else}{$taxTerm} {$taxDetail.percentage}%{/if}</td>
              <td {$valueStyle}>{$taxDetail.amount|crmMoney:'{contribution.currency}'}</td>
            </tr>
          {/foreach}
        {/if}
        {if $isShowTax && {contribution.tax_amount|boolean}}
          <tr>
            <td {$labelStyle}>
              {ts}Total Tax Amount{/ts}
            </td>
            <td {$valueStyle}>
              {if $isPrimary}{contribution.tax_amount}{else}{$participant.totals.tax_amount|crmMoney}{/if}
            </td>
          </tr>
        {/if}
        {if $isPrimary}
          <tr>
            <td {$labelStyle}>
              {ts}Total Amount{/ts}
            </td>
            <td {$valueStyle}>
              {contribution.total_amount}
            </td>
          </tr>
          {if {contribution.balance_amount|boolean} && {contribution.paid_amount|boolean}}
            <tr>
              <td {$labelStyle}>{ts}Total Paid{/ts}</td>
              <td {$valueStyle}>
                {contribution.paid_amount|crmMoney}
              </td>
            </tr>
            <tr>
              <td {$labelStyle}>{ts}Balance{/ts}</td>
              <td {$valueStyle}>{contribution.balance_amount}</td>
            </tr>
          {/if}
          {if $isShowParticipantCount}
            <tr>
              <td {$labelStyle}>{ts}Total Participants{/ts}</td>
              <td {$valueStyle}>{$line.participant_count}</td>
            </tr>
          {/if}
          {if {contribution.is_pay_later|boolean} && {contribution.balance_amount|boolean} && {event.pay_later_receipt|boolean}}
            <tr>
              <td colspan="2" {$labelStyle}>
                {event.pay_later_receipt}
              </td>
            </tr>
          {/if}

          {if {participant.register_date|boolean}}
            <tr>
              <td {$labelStyle}>
                {ts}Registration Date{/ts}
              </td>
              <td {$valueStyle}>
                {participant.register_date}
              </td>
            </tr>
          {/if}

          {if {contribution.receive_date|boolean}}
            <tr>
              <td {$labelStyle}>
                  {ts}Transaction Date{/ts}
              </td>
              <td {$valueStyle}>
                {contribution.receive_date}
              </td>
            </tr>
          {/if}

          {if {contribution.trxn_id|boolean}}
            <tr>
              <td {$labelStyle}>
                  {ts}Transaction #{/ts}
              </td>
              <td {$valueStyle}>
                {contribution.trxn_id}
              </td>
            </tr>
          {/if}

          {if {contribution.payment_instrument_id|boolean} && {contribution.paid_amount|boolean}}
            <tr>
              <td {$labelStyle}>
                  {ts}Paid By{/ts}
              </td>
              <td {$valueStyle}>
                  {contribution.payment_instrument_id:label}
              </td>
            </tr>
          {/if}

          {if {contribution.check_number|boolean}}
            <tr>
              <td {$labelStyle}>
                  {ts}Check Number{/ts}
              </td>
              <td {$valueStyle}>
                {contribution.check_number}
              </td>
            </tr>
          {/if}

          {if {contribution.address_id.display|boolean}}
            <tr>
              <th {$headerStyle}>
                {ts}Billing Address{/ts}
              </th>
            </tr>
            <tr>
              <td colspan="2" {$valueStyle}>
                {contribution.address_id.name}<br/>
                {contribution.address_id.display}
              </td>
            </tr>
          {/if}

          {if !empty($credit_card_type)}
            <tr>
              <th {$headerStyle}>
                {ts}Credit Card Information{/ts}
              </th>
            </tr>
            <tr>
              <td colspan="2" {$valueStyle}>
                {$credit_card_type}<br/>
                {$credit_card_number}<br/>
              </td>
            </tr>
          {/if}
        {/if}
     {/if} {* End of conditional section for Paid events *}

     {if !empty($customGroup)}
      {foreach from=$customGroup item=value key=customName}
       <tr>
        <th {$headerStyle}>
         {$customName}
        </th>
       </tr>
       {foreach from=$value item=v key=n}
        <tr>
         <td {$labelStyle}>
          {$n}
         </td>
         <td {$valueStyle}>
          {$v}
         </td>
        </tr>
       {/foreach}
      {/foreach}
     {/if}

    </table>
   </td>
  </tr>

 </table>

</body>
</html>
