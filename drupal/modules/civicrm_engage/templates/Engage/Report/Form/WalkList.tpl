{if !$printOnly }
{include file="CRM/Report/Form.tpl"}
{else}

{if $groupRows}
    {assign var=pageNum value=1}
    {foreach from=$groupRows item=groupData key=group}
      {if $pageNum eq 1}
      <div>
  {else}
        <div {$newgroupdiv}>
  {/if}
         <table class="head">

        <tr><td style="text-align: left;">Organization Name: {$groupData.org}</td>
            <td style="text-align: center;"></td>
            <td style="text-align: right;">{if {$groupData.odd}Odd/Even: {$groupData.odd}{/if}</td>
        </tr>
        <tr>
          <td style="text-align: left;">{if $groupData.street_name}Street Name: {$groupData.street_name}{/if}</td>
          <td style="text-align: center;">Walk List</td>
          <td style="text-align: right;">&nbsp;</td>
        </tr>
        <tr>
          <td style="text-align: left;">{if $groupData.city_zip}City-Zip: {$groupData.city_zip}{/if}</td>
          <td style="text-align: center;">{$groupInfo.date}{$groupInfo.descr}
                  {if $statistics }
               {foreach from=$statistics.filters item=row}
                      <br /> {$row.title} &nbsp:{$row.value}
                  {/foreach}
              {/if}
    </td>
          <td style="text-align: right;">&nbsp;</td>
        </tr>

  </table>
  {if $pdfRows.$group}
      <table class="body">
      <tr>
        {foreach from=$pdfHeaders item=header}
            <th {$header.class}  class='reports-header-right' >{$header.title}</th>
        {/foreach}
            </tr>
      {foreach from=$pdfRows.$group item=row}
          <tr>
          {foreach from=$pdfHeaders item=title key=k}
        {if $k eq 'rcode'}
                        <td>Q1&nbsp;&nbsp;&nbsp;&nbsp;Y&nbsp;&nbsp;&nbsp;&nbsp;N&nbsp;&nbsp;&nbsp;&nbsp;U&nbsp;&nbsp;&nbsp;&nbsp;D<br>
                            Q2&nbsp;&nbsp;&nbsp;&nbsp;Y&nbsp;&nbsp;&nbsp;&nbsp;N&nbsp;&nbsp;&nbsp;&nbsp;U&nbsp;&nbsp;&nbsp;&nbsp;D<br>
                            Q3&nbsp;&nbsp;&nbsp;&nbsp;Y&nbsp;&nbsp;&nbsp;&nbsp;N&nbsp;&nbsp;&nbsp;&nbsp;U&nbsp;&nbsp;&nbsp;&nbsp;D<br>
                            Q4&nbsp;&nbsp;&nbsp;&nbsp;Y&nbsp;&nbsp;&nbsp;&nbsp;N&nbsp;&nbsp;&nbsp;&nbsp;U&nbsp;&nbsp;&nbsp;&nbsp;D
                        </td>
                    {elseif $k eq 'status'}
                        <td>NH&nbsp;MV&nbsp;D&nbsp;WN</td>
        {else}
            <td>{$row.$k}</td>
                    {/if}
          {/foreach}
    </tr>
      {/foreach}
      </tr>
      </table>
  {/if}

        <p>Response Codes: Y= Yes; N= No; U= Undecided; D= Declined to State</p>
        <p>Status Codes: NH= Not Home; MV= Moved; D= Deceased; WA= Wrong Address</p>
        <p>VH (Voting History): A=Always; O=Occasional; N=New</p>
  <br/>

     {/foreach}
{/if}
{/if}
