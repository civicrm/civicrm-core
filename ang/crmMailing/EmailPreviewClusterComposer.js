var http = require ('http');
var XMLHttpRequest = require("xmlhttprequest").XMLHttpRequest;
var fs = require ('fs');

var configFile = fs.readFileSync('composer-config.json');
var  config= JSON.parse(configFile);

var prevemURL = config.prevemURL;
var statusURL = prevemURL + 'PreviewBatches/status?batchId=';
var postURL = prevemURL + 'PreviewBatches';
var customerId = config.customerId;
var batchId	= customerId + '123'				//unique everytime. Needs to be saved in order for the composer to be able to look the batch up.

var messageURL= config.emailURL					//The URL where the messageObject and renderers are picked from. CiviCRM Mailing page creates/populates this URL.
var Interval;

http.get(messageURL, function(res) {
	var body1 = '';
	res.on('data', function(chunk) {
	    body1 += chunk;
	});

	res.on('end', function() {
	    var Email = JSON.parse(body1);
	    var messageObject = {};
	    messageObject['subject'] = Email.subject;
	    messageObject['text'] = Email.text;
	    messageObject['html'] = Email.html;
	    var renderers = Email.renderers;
	    postNewBatch (statusURL, batchId, postURL, messageObject, renderers);
	});
});

function postNewBatch(statusURL, batchId, postURL, messageObject, renderers) {
	var xmlhttp = new XMLHttpRequest();   // new HttpRequest instance 
	xmlhttp.open("POST", postURL);
	xmlhttp.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
	// xmlhttp.onreadystatechange = function() {//Call a function when the state changes.
	//     if(xmlhttp.readyState == 4) {
	//         console.log(xmlhttp.responseText);
	//     }
	// }
	var postData = JSON.stringify([{	"batchId": batchId,
  										"consumerId": customerId,
										"message": messageObject,
										"renderers": renderers
								}])
	//console.log(postData);
	xmlhttp.send(postData);
	Interval = setInterval( function(){
		checkStatus(statusURL, batchId);
	}, 5000);

}

function checkStatus(statusURL, batchId) {
		http.get(statusURL+batchId, function(res) {
		var body = '';
	    res.on('data', function(chunk) {
	        body += chunk;
	    });

	    res.on('end', function() {
	        var status = JSON.parse(body);
	     	console.log(status.response);
	     	if (status.response.finished == 1) {
	     		clearInterval(Interval);
	     	}
	    });
	});
}