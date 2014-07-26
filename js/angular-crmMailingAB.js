/**
 * Created by aditya on 6/12/14.
 */


(function(angular, $, _) {

    var partialUrl = function(relPath) {
        //console.log(CRM.resourceUrls['civicrm']);
        return CRM.resourceUrls['civicrm'] + '/partials/abtesting/' + relPath;
        //return '/drupal-7.28/sites/all/modules/civicrm/partials/abtesting/' + relPath;


    };

    var crmMailingAB = angular.module('crmMailingAB', ['ngRoute', 'ui.utils']);

    crmMailingAB.run(function($rootScope, $templateCache) {
        $rootScope.$on('$viewContentLoaded', function() {
            $templateCache.removeAll();
        });
    });

    crmMailingAB.config(['$routeProvider',
        function($routeProvider) {
            $routeProvider.when('/mailing', {
                template: '<h1>sdfs</h1>',
                controller: 'mailingListCtrl',
                resolve: {
                    mailingList: function($route, crmApi) {
                        return crmApi('Mailing', 'get', {});
                    }
                }
            });

            $routeProvider.when('/mailing/abtesting', {
                templateUrl: partialUrl('main.html'),
                controller: 'TabsDemoCtrl',
                resolve: {

                    mailingList: function($route, crmApi) {
                        return crmApi('Mailing', 'get', {});
                    }
                }
            });
        }
    ]);
//-----------------------------------------
    // Add a new record by name.
    // Ex: <crmAddName crm-options="['Alpha','Beta','Gamma']" crm-var="newItem" crm-on-add="callMyCreateFunction(newItem)" />

    crmMailingAB.controller('TabsDemoCtrl', function($scope, crmApi) {

        $scope.groups= CRM.crmMailing.groupNames;
        $scope.mailList = CRM.crmMailing.civiMails;

        $scope.tab_val=0;
        $scope.max_tab=0;
        $scope.campaign_clicked= function(){
            if($scope.max_tab >= 0 ){
                $scope.tab_val  =0;
            }
        };
        $scope.compose_clicked=function(){
            if($scope.max_tab >=1){
                $scope.tab_val =1;
            }
        };
        $scope.rec_clicked=function(){
            if($scope.max_tab >=2){
                $scope.tab_val =2;
            }
        };
        $scope.preview_clicked=function(){
            if($scope.max_tab>=3){
                $scope.tab_val=3;
            }
        };
        $scope.templates =
            [   { name: 'Subject Lines', url: partialUrl('subject_lines.html')},
                { name: 'From Name', url: partialUrl('from_name.html')},
                {name:'Two different Emails',url: partialUrl('two_emails.html')} ];
        $scope.template = $scope.templates[0];

        $scope.slide_value = 0;

        $scope.setifyes= function(val){
            if(val ==1) {
                $scope.ifyes = true;
            }
            else
            $scope.ifyes=false;
        };

        $scope.send_date ="01/01/2000";

        $scope.dt="";

        $scope.mailA={};

        $scope.mailB={};
        $scope.save=function(dat){

            var result= crmApi('Mailing', 'create',dat, true);
            console.log("Ac "+result);
        };

        $scope.init=function(par){

            $scope.whatnext=par.toString()
        };

        $scope.setdate= function(par){
            console.log("called")
            console.log("av "+par)
            $scope.send_date =par;
            $scope.dt=par;
            $scope.apply();
        };

        $scope.scheddate={};
        $scope.scheddate.date = "6";
        $scope.scheddate.time = "";
        $scope.incGroup =[];
        $scope.excGroup=[];


    });

    crmMailingAB.directive('nexttab', function() {
        return {
            // Restrict it to be an attribute in this case
            restrict: 'A',

            priority: 500,
            // responsible for registering DOM listeners as well as updating the DOM
            link: function(scope, element, attrs) {

                $(element).parent().parent().parent().parent().tabs(scope.$eval(attrs.nexttab));
                var myarr = new Array(1,2,3)
                $(element).parent().parent().parent().parent().tabs({disabled:myarr});

                $(element).on("click",function() {
                    scope.tab_val=scope.tab_val +1;

                    scope.max_tab= Math.max(scope.tab_val,scope.max_tab);
                    var myArray1 = new Array(  );
                    for ( var i = scope.max_tab+1; i < 4; i++ ) {
                        myArray1.push(i);
                    }
                    $(element).parent().parent().parent().parent().parent().tabs( "option", "disabled", myArray1 );
                    $(element).parent().parent().parent().parent().parent().tabs("option", "active", scope.tab_val);
                    scope.$apply();
                    console.log("Adir");
                });
            }
        };
    });

    crmMailingAB.directive('prevtab', function() {
        return {
            // Restrict it to be an attribute in this case
            restrict: 'A',
            priority: 500,
            // responsible for registering DOM listeners as well as updating the DOM
            link: function(scope, element, attrs) {



                $(element).on("click",function() {
                    var temp= scope.tab_val -1 ;
                    scope.tab_val=scope.tab_val -1;

                    console.log(temp);
                    if(temp==3){

                    }
                    else {
                        $(element).parent().parent().parent().parent().parent().tabs("option", "active", temp);
                    }

                    scope.$apply();

                });
            }
        };
    });

    crmMailingAB.directive('groupselect',function(){
       return {
           restrict : 'AE',
           link: function(scope,element, attrs){
               function format(item) {
                   if(!item.id) {
                       // return `text` for optgroup
                       return item.text;
                   }
                   // return item template
                   var a = item.id.split(" ");
                   if(a[1]=="group" && a[2]=="include")
                       return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
                   if(a[1]=="group" && a[2]=="exclude")
                       return "<img src='../../sites/all/modules/civicrm/i/Error.gif' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
                   if(a[1]=="mail" && a[2]=="include")
                       return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/EnvelopeIn.gif' height=12 width=12/>" + item.text;
                   if(a[1]=="mail" && a[2]=="exclude")
                       return "<img src='../../sites/all/modules/civicrm/i/Error.gif' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/EnvelopeIn.gif' height=12 width=12/>" + item.text;
               }

               $(element).select2({
                   width:"400px",
                   placeholder: "Select the groups you wish to include",
                   formatResult: format,
                   formatSelection: format,
                   escapeMarkup: function(m) { return m; }
               });

               $(element).on('select2-selecting', function(e) {
                   var a = e.val.split(" ");
                   var l = a.length;
                   if(a[2]=="include")
                   {   var str="";
                       for(i=3; i< l; i++){
                       str+=a[i];
                       str+=" ";
                       }
                       scope.incGroup.push(str);scope.$apply();}

                   else
                   {   var str="";
                       for(i=3; i< l; i++){
                           str+=a[i];
                           str+=" ";
                       }

                       scope.excGroup.push(str);scope.$apply();}

               });
               $(element).on("select2-removed", function(e) {
                   if(e.val.split(" ")[2]=="exclude") {
                       var index = scope.excGroup.indexOf(e.val.split(" ")[3]);
                       scope.excGroup.splice(index, 1);
                       scope.$apply();
                   }
                   else{
                       var index = scope.incGroup.indexOf(e.val.split(" ")[3]);
                       scope.incGroup.splice(index, 1);scope.$apply();
                   }
               });
           }
       };

    });

    crmMailingAB.directive('sliderbar',function(){
       return{
           restrict: 'AE',
           link: function(scope,element, attrs){
               $(element).slider({min:1});
               $(element).slider({
                   slide: function( event, ui ) {
                       scope.slide_value = ui.value;
                       scope.$apply();
                   }
               });
           }
       };
    });

    crmMailingAB.directive('tpmax',function(){
        return {
            restrict: 'E',
            link: function(scope,element,attr){
                scope.$watch('automated', function(val) {
                    if(val=="Yes") {
                        $(element).dialog({
                            title: 'Automated A/B Testing',
                            width: 800,
                            height: 600,
                            closed: false,
                            cache: false,
                            modal: true
                        });
                    }
                });

                $(element).find("#closebutton").on("click",function(){
                    $(element).dialog("close");
                });
            }
        };
    });

    crmMailingAB.directive('numbar',function(){
        return{
            restrict: 'AE',
            link: function(scope,element, attrs){
                $(element).spinner({max:attrs.numbar,min:0});
            }
        };
    });

    crmMailingAB.directive('datepick',function(){
        return {


          restrict: 'AE',
          link: function(scope,element,attrs){
                $(element).datepicker({
                    dateFormat: "yy-mm-dd",
                    onSelect: function(date) {
                        $(".ui-datepicker a").removeAttr("href");

                        scope.scheddate.date=date.toString();
                        scope.$apply();
                        console.log(scope.scheddate.date);

                    }


                });
            }
        };
    });

    crmMailingAB.directive('submitform',function(){
        return {
          restrict:'A',
            priority: 1000,
          link: function(scope,element,attrs){
              $(element).on("click",function() {

                  console.log("clicked");
                  scope.save({
                      "sequential": 1,
                       "name": "Aditya Nambiar",
                      "subject": scope.mailA.subj,
                      "created_id": "2",
                      "from_email": scope.mailA.fromEmail,
                      "body_text": scope.mailA.body

                  });
                  console.log("Truth "+ scope.whatnext)

                  if(scope.whatnext=="3"){
                      console.log("sdf");
                      scope.mailB.subj=scope.mailA.subj;
                      scope.mailB.body=scope.mailA.body;

                  }
                  else if(scope.whatnext=="2"){
                      scope.mailB.fromEmail=scope.mailA.fromEmail;
                      scope.mailB.body=scope.mailA.body;

                  }
                  

                  scope.save({
                      "sequential": 1,
                      "name": "Aditya Nambiar",
                      "subject": scope.mailB.subj,
                      "created_id": "2",
                      "from_email": scope.mailB.fromEmail,
                      "body_text": scope.mailB.body

                  });

              });
          }
        };

    });

    crmMailingAB.directive('nextbutton',function(){
        return {
            restrict: 'AE',
            replace:'true',
            template:'<div class="crm-submit-buttons" id="campaignbutton">'+
                '<div class = "crm-button crm-button-type-upload crm-button_qf_Contact_upload_view"   >' +
                '<input type="submit" value="Next"  id="campaignbutton _qf_Contact_upload_view-top" class="btn btn-primary" nexttab={{tab_val}}>'+
                '</div></div>'

        };
    });

    crmMailingAB.directive('cancelbutton',function(){
        return {
            restrict: 'AE',
            replace:'true',
            template:'<div class="crm-submit-buttons" id="campaignbutton">'+
                '<div class = "crm-button crm-button-type-upload crm-button_qf_Contact_upload_view"   >' +
                '<input type="submit" value="Cancel"  id="campaignbutton _qf_Contact_upload_view-top" class="btn btn-primary" >'+
                '</div></div>'

        };
    });

    crmMailingAB.directive('prevbutton',function(){
        return {
            restrict: 'AE',
            replace:'true',
            template:'<div class="crm-submit-buttons" >'+
                '<div class = "crm-button crm-button-type-upload crm-button_qf_Contact_upload_view"   >' +
                '<input type="submit" value="Previous"  id="campaignbutton _qf_Contact_upload_view-top" class="btn btn-primary" prevtab={{tab_val}}>'+
                '</div></div>'

        };
    });




})(angular, CRM.$, CRM._);

