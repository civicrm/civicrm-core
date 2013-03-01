{if $smarty.get.snippet neq 4}
{literal}
<script type="text/javascript">
cj( function() {
    modifyActivityForm( );
    cj('#activity_type_id').change( function() {
        modifyActivityForm( );
    });
});

function modifyActivityForm( ) {
    var selectedActivityType = cj('#activity_type_id').val();
    if ( !selectedActivityType ) {
        // retrieve from assigned value
        {/literal}
        {if $atype}
        selectedActivityType = '{$atype}';
        {/if}
        {literal}
    }
    
    buildProfileForm( selectedActivityType );
    switch ( selectedActivityType ) {
        // NAI Open 
        case '51':
            alterFormElements( true );
            // remove unwanted statuses
            cj("#status_id option[value='1']").remove();
            cj("#status_id option[value='2']").remove();
            cj("#status_id option[value='3']").remove();
            cj("#status_id option[value='4']").remove();
            cj("#status_id option[value='5']").remove();
            cj("#status_id option[value='6']").remove();
            break;
        // NAI Prepare Application
        case '47':
            alterFormElements( true );
            break;
        // NAI Refer Out
        case '48':
            alterFormElements( true );
            break;
        // NAI Follow Up
        case '49':
            alterFormElements( true );
            break;
        // NAI Close
        case '50':
            alterFormElements( true );
            break;
        default:
            alterFormElements( false );
    }
}

function alterFormElements( hide ) {
    if ( hide ) {
        cj('.crm-activity-form-block-location').hide(); 
        cj('.crm-activity-form-block-duration').hide(); 
        cj('.crm-activity-form-block-priority_id').hide(); 
        cj('.crm-activity-form-block-details td.label').text('Notes');
    } else {
        cj('.crm-activity-form-block-location').show(); 
        cj('.crm-activity-form-block-duration').show(); 
        cj('.crm-activity-form-block-priority_id').show(); 
        cj('.crm-activity-form-block-details td.label').text('Details');
    }
}

function buildProfileForm( activityType ) {
    if ( !activityType ) {
        cj( '#crm-activity-profile' ).html( '' );
        return;
    }

    var dataUrl = {/literal}"{crmURL h=0 q='includeProfile=1&snippet=4&acttype='}"{literal} + activityType; 
    {/literal}
    {if $qfKey}
        dataUrl = dataUrl + '&qfKey=' + '{$qfKey}'
    {/if}
    
    {if $entityID}
        dataUrl = dataUrl + '&activity_id=' + '{$entityID}'
    {/if}
    {literal}
	
    var response = cj.ajax({
                    url: dataUrl,
                    async: false
                    }).responseText;

    cj( '#crm-activity-profile' ).html( response );
}
</script>
{/literal}
{/if}
