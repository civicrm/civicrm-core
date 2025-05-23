<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

  {capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
  {capture assign=labelStyle}style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
  {capture assign=valueStyle}style="padding: 4px; border-bottom: 1px solid #999;"{/capture}

    <!-- BEGIN HEADER -->
      {* To modify content in this section, you can edit the Custom Token named "Message Header". See also: https://docs.civicrm.org/user/en/latest/email/message-templates/#modifying-system-workflow-message-templates *}
      {site.message_header}
    <!-- END HEADER -->

    <!-- BEGIN CONTENT -->

    <table id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">
      <tr>
        <td>
          <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
            <tr>
              <th {$headerStyle}>
                {ts}Activity Summary{/ts} - {activity.activity_type_id:label}
              </th>
            </tr>
            {if !empty($isCaseActivity)}
              <tr>
                <td {$labelStyle}>
                  {ts}Your Case Role(s){/ts}
                </td>
                <td {$valueStyle}>
                  {$contact.role|default:''}
                </td>
              </tr>
              {if !empty($manageCaseURL)}
                <tr>
                  <td colspan="2" {$valueStyle}>
                    <a href="{$manageCaseURL}" title="{ts}Manage Case{/ts}">{ts}Manage Case{/ts}</a>
                  </td>
                </tr>
              {/if}
            {/if}
            {if !empty($editActURL)}
              <tr>
                <td colspan="2" {$valueStyle}>
                  <a href="{$editActURL}" title="{ts}Edit activity{/ts}">{ts}Edit activity{/ts}</a>
                </td>
              </tr>
            {/if}
            {if !empty($viewActURL)}
              <tr>
                <td colspan="2" {$valueStyle}>
                  <a href="{$viewActURL}" title="{ts}View activity{/ts}">{ts}View activity{/ts}</a>
                </td>
              </tr>
            {/if}
            {foreach from=$activity.fields item=field}
              <tr>
                <td {$labelStyle}>
                  {$field.label}
                </td>
                <td {$valueStyle}>
                  {if $field.type eq 'Date'}
                    {$field.value|crmDate:$config->dateformatDatetime}
                  {else}
                    {$field.value}
                  {/if}
                </td>
              </tr>
            {/foreach}

            {if !empty($activity.customGroups)}
              {foreach from=$activity.customGroups key=customGroupName item=customGroup}
                <tr>
                  <th {$headerStyle}>
                    {$customGroupName}
                  </th>
                </tr>
                {foreach from=$customGroup item=field}
                  <tr>
                    <td {$labelStyle}>
                      {$field.label}
                    </td>
                    <td {$valueStyle}>
                      {if $field.type eq 'Date'}
                        {$field.value|crmDate:$config->dateformatDatetime}
                      {else}
                        {$field.value}
                      {/if}
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
