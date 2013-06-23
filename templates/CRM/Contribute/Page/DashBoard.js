// http://civicrm.org/licensing
cj(document).ready( function( ) {
    getChart( );
    cj('#chart_view').click(function( ) {
        if ( cj('#chart_view').hasClass('ui-state-default') ) {
            cj('#chart_view').removeClass('ui-state-default').addClass('ui-state-active ui-tabs-selected');
            cj('#table_view').removeClass('ui-state-active ui-tabs-selected').addClass('ui-state-default');
            getChart( );
            cj('#tableData').children().html('');
        }
    });
    cj('#table_view').click(function( ) {
        if ( cj('#table_view').hasClass('ui-state-default') ) {
            cj('#table_view').removeClass('ui-state-default').addClass('ui-state-active ui-tabs-selected');
            cj('#chart_view').removeClass('ui-state-active ui-tabs-selected').addClass('ui-state-default');
            buildTabularView();
            cj('#chartData').children().html('');
        }
    });
});

function getChart( ) {
   var year        = cj('#select_year').val( );
   var charttype   = cj('#chart_type').val( );
   var date        = new Date()
   var currentYear = date.getFullYear( );
   if ( !charttype ) charttype = 'bvg';
   if ( !year ) year           = currentYear;

   var chartUrl = CRM.url("civicrm/ajax/chart", {snippet : 4});
   chartUrl    += "&year=" + year + "&type=" + charttype;
   cj.ajax({
       url     : chartUrl,
       success  : function(html){
           cj( "#chartData" ).html( html );
       }
   });

}

function buildTabularView( ) {
    var tableUrl = CRM.url("civicrm/contribute/ajax/tableview", {showtable:1, snippet:4});
    cj.ajax({
        url      : tableUrl,
        success  : function(html){
            cj( "#tableData" ).html( html );
        }
    });
}