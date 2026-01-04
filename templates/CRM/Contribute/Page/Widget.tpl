{literal}
<style>
    .crm-contribute-widget {
        font-size:12px;
        font-family:Helvetica, Arial, sans;
        padding:6px;
        -moz-border-radius:       4px;
        -webkit-border-radius:   4px;
        -khtml-border-radius:   4px;
        border-radius:      4px;
        border:1px solid #96C0E7;
        width:200px;
    }
    .crm-contribute-widget h5 {
        font-size:14px;
        padding:3px;
        margin: 0px;
        text-align:center;
        -moz-border-radius:   4px;
        -webkit-border-radius:   4px;
        -khtml-border-radius:   4px;
        border-radius:      4px;
    }
    .crm-contribute-widget .crm-amounts {
        height:1em;
        margin:.8em 0px;
        font-size:13px;
    }
    .crm-contribute-widget .crm-amount-low {
        float:left;
    }
    .crm-contribute-widget .crm-amount-high {
        float:right;
    }
    .crm-contribute-widget .crm-percentage {
        margin:0px 30%;
        text-align:center;
    }
    .crm-contribute-widget .crm-amount-bar {
        background-color:#FFF;
        width:100%;
        display:block;
        border:1px solid #CECECE;
        -moz-border-radius:   4px;
        -webkit-border-radius:   4px;
        -khtml-border-radius:   4px;
        border-radius:      4px;
        margin-bottom:.8em;
        text-align:left;
    }
    .crm-contribute-widget .crm-amount-fill {
        background-color:#2786C2;
        height:1em;
        display:block;
        -moz-border-radius:   4px 0px 0px 4px;
        -webkit-border-radius:   4px 0px 0px 4px;
        -khtml-border-radius:   4px 0px 0px 4px;
        border-radius:      4px 0px 0px 4px;
        text-align:left;
    }
    .crm-contribute-widget .crm-amount-raised-wrapper {
        margin-bottom:.8em;
    }
    .crm-contribute-widget .crm-amount-raised {
        font-weight:bold;
    }
    .crm-contribute-widget .crm-logo {
        text-align:center;
    }
    .crm-contribute-widget .crm-comments,
    .crm-contribute-widget .crm-donors,
    .crm-contribute-widget .crm-campaign {
        font-size:11px;
        margin-bottom:.8em;
    }
    .crm-contribute-widget .crm-contribute-button {
        display:block;
        background-color:#CECECE;
        -moz-border-radius:       4px;
        -webkit-border-radius:   4px;
        -khtml-border-radius:   4px;
        border-radius:      4px;
        text-align:center;
        margin:0px 10% .8em 10%;
        text-decoration:none;
        color:#556C82;
        padding:2px;
        font-size:13px;
    }
    .crm-contribute-widget .crm-home-url {
        text-decoration:none;
        border:0px;
    }
</style>
<style>
    .crm-contribute-widget {
        background-color: {/literal}{$form.color_main.value}{literal}; /* background color */
        border-color:{/literal}{$form.color_bg.value}{literal}; /* border color */
    }
    .crm-contribute-widget h5 {
        color: {/literal}{$form.color_title.value}{literal};
        background-color: {/literal}{$form.color_main_bg.value}{literal};
    } /* title */
    .crm-contribute-widget .crm-amount-raised { color:#000; }
    .crm-contribute-widget .crm-amount-fill {
      background-color:{/literal}{$form.color_bar.value}{literal};
    }
    .crm-contribute-widget a.crm-contribute-button { /* button color */
        background-color:{/literal}{$form.color_button.value}{literal};
    }
    .crm-contribute-widget .crm-contribute-button-inner { /* button text color */
        padding:2px;
        display:block;
        color:{/literal}{$form.color_about_link.value}{literal};
    }
    .crm-contribute-widget .crm-comments,
    .crm-contribute-widget .crm-donors,
    .crm-contribute-widget .crm-campaign {
        color:{/literal}{$form.color_main_text.value}{literal} /* other color*/
    }
    .crm-contribute-widget .crm-home-url {
        color:{/literal}{$form.color_homepage_link.value}{literal} /* home page link color*/
    }
</style>
{/literal}
<div id="crm_cpid_{$cpageId}" class="crm-contribute-widget">
    <h5 id="crm_cpid_{$cpageId}_title"></h5>
    <div class="crm-amounts">
        <div id="crm_cpid_{$cpageId}_amt_hi" class="crm-amount-high"></div>
        <div id="crm_cpid_{$cpageId}_amt_low" class="crm-amount-low"></div>
        <div id="crm_cpid_{$cpageId}_percentage" class="crm-percentage"></div>
    </div>
    <div class="crm-amount-bar">
        <div class="crm-amount-fill" id="crm_cpid_{$cpageId}_amt_fill"></div>
    </div>
    <div class="crm-amount-raised-wrapper">
        <span id="crm_cpid_{$cpageId}_amt_raised" class="crm-amount-raised"> -- placeholder -- </span>
    </div>
    {if !empty($form.url_logo.value)}
        <div class="crm-logo"><img src="{$form.url_logo.value}" alt="{ts escape='htmlattribute'}Logo{/ts}"></div>
    {/if}
    <div id="crm_cpid_{$cpageId}_donors" class="crm-donors"></div>
    <div id="crm_cpid_{$cpageId}_comments" class="crm-comments"></div>
    <div id="crm_cpid_{$cpageId}_campaign" class="crm-campaign"></div>
    <div class="crm-contribute-button-wrapper" id="crm_cpid_{$cpageId}_button">
        <a href='{crmURL p="civicrm/contribute/transact" q="reset=1&id=$cpageId" h=0 a=1 fe=1}' class="crm-contribute-button"><span class="crm-contribute-button-inner" id="crm_cpid_{$cpageId}_btn_txt"> -- placeholder -- </span></a>
    </div>
</div>
{literal}
<script type="text/javascript">
// Cleanup functions for the document ready method
if ( document.addEventListener ) {
    DOMContentLoaded = function() {
        document.removeEventListener( "DOMContentLoaded", DOMContentLoaded, false );
        onReady();
    };
} else if ( document.attachEvent ) {
    DOMContentLoaded = function() {
        // Make sure body exists, at least, in case IE gets a little overzealous
        if ( document.readyState === "complete" ) {
            document.detachEvent( "onreadystatechange", DOMContentLoaded );
            onReady();
        }
    };
}
if ( document.readyState === "complete" ) {
    // Handle it asynchronously to allow scripts the opportunity to delay ready
    setTimeout( onReady, 1 );
}
// Mozilla, Opera and webkit support this event
if ( document.addEventListener ) {
    // Use the handy event callback
    document.addEventListener( "DOMContentLoaded", DOMContentLoaded, false );
    // A fallback to window.onload, that will always work
    window.addEventListener( "load", onReady, false );
    // If IE event model is used
} else if ( document.attachEvent ) {
    // ensure firing before onload,
    // maybe late but safe also for iframes
    document.attachEvent("onreadystatechange", DOMContentLoaded);
    // A fallback to window.onload, that will always work
    window.attachEvent( "onload", onReady );
}
function onReady( ) {
    var cpid    = {/literal}{$cpageId}{literal};
    var jsonvar = eval('jsondata' + cpid);
    var crmCurrency = jsonvar.currencySymbol;
    document.getElementById('crm_cpid_'+cpid+'_title').innerHTML        = jsonvar.title;
    if ( jsonvar.money_target > 0 ) {
        document.getElementById('crm_cpid_'+cpid+'_amt_hi').innerHTML   = jsonvar.money_target_display;
        document.getElementById('crm_cpid_'+cpid+'_amt_low').innerHTML  = crmCurrency+jsonvar.money_low;
    }
    document.getElementById('crm_cpid_'+cpid+'_amt_raised').innerHTML   = jsonvar.money_raised;
    document.getElementById('crm_cpid_'+cpid+'_comments').innerHTML     = jsonvar.about;
    document.getElementById('crm_cpid_'+cpid+'_donors').innerHTML       = jsonvar.num_donors;
    document.getElementById('crm_cpid_'+cpid+'_btn_txt').innerHTML      = jsonvar.button_title;
    document.getElementById('crm_cpid_'+cpid+'_campaign').innerHTML     = jsonvar.campaign_start;
    if ( jsonvar.money_raised_percentage ) {
        var moneyRaised = jsonvar.money_raised_percentage;
        var percentWidth = moneyRaised.split('%');
        if ( percentWidth[0] > 100 ) {
            moneyRaised = '100%';
        }
        document.getElementById('crm_cpid_'+cpid+'_amt_fill').style.width = moneyRaised;
        document.getElementById('crm_cpid_'+cpid+'_percentage').innerHTML = jsonvar.money_raised_percentage;
    }
    if ( !jsonvar.is_active ) {
        document.getElementById('crm_cpid_'+cpid+'_button').innerHTML   = jsonvar.home_url;
        document.getElementById('crm_cpid_'+cpid+'_button').style.color = 'red';
    }
}
</script>
{/literal}
<script type="text/javascript" src="{$widgetExternUrl}"></script>
