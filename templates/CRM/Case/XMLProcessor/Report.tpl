{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<?xml version="1.0" encoding="UTF-8"?>
<Case>
  <Client>{$case.clientName|escape}</Client>
  <CaseType>{$case.caseType}</CaseType>
  <CaseSubject>{$case.subject|escape}</CaseSubject>
  <CaseStatus>{$case.status}</CaseStatus>
  <CaseOpen>{$case.start_date}</CaseOpen>
  <CaseClose>{$case.end_date}</CaseClose>
  <ActivitySet>
    <Label>{$activitySet.label}</Label>
    <IncludeActivities>{$includeActivities}</IncludeActivities>
    <Redact>{$isRedact}</Redact>
{foreach from=$activities item=activity}
    <Activity>
       <EditURL>{$activity.editURL}</EditURL>
       <Fields>
{foreach from=$activity.fields item=field}
          <Field>
            <Name>{$field.name|escape}</Name>
            <Label>{$field.label|escape}</Label>
{if $field.category}
            <Category>{$field.category|escape}</Category>
{/if}
            <Value>{$field.value|escape}</Value>
            <Type>{$field.type}</Type>
          </Field>
{/foreach}
{if $activity.customGroups}
         <CustomGroups>
{foreach from=$activity.customGroups key=customGroupName item=customGroup}
            <CustomGroup>
               <GroupName>{$customGroupName|escape}</GroupName>
{foreach from=$customGroup item=field}
                  <Field>
                    <Name>{*TODO*}</Name>
                    <Label>{$field.label|escape}</Label>
                    <Value>{$field.value|escape}</Value>
                    <Type>{$field.type}</Type>
                  </Field>
{/foreach}
            </CustomGroup>
{/foreach}
         </CustomGroups>
{/if}
       </Fields>
    </Activity>
{/foreach}
  </ActivitySet>
</Case>
