/*
    Stuff to help us work with JSON and AJAX
*/
Function.prototype.bind = function(object) {
  var __method = this;
  return function() {
    __method.apply(object, arguments);
  }
}

function JAJAX() {
	this.Request = false;
	if (window.XMLHttpRequest) {
        try {
            this.Request = new XMLHttpRequest();
        } catch(e) {
            this.Request = false;
        }

    } else if(window.ActiveXObject) {
        try {
            this.Request = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(e) {
            try {
                this.Request = new ActiveXObject("Microsoft.XMLHTTP");
            } catch(e) {
                this.Request = false;
            }
		}
    }
}

JAJAX.prototype.Busy = function() {
    if (this.Request.readyState == 4) {
        if (document.getElementById('loading'))
            document.getElementById('loading').style.visibility = 'hidden';
        try {
            if (this.Request.status) {
                if (this.Request.status == 200) {
					var myObject;
                    try {
						myObject = JSON.parse(this.Request.responseText);
					} catch (e) {
						myObject = this.Request.responseText;
					}
                    this.onDone(myObject);
                } else {
                    alert("There was a problem retrieving the JSON data:\n" + this.Request.statusText);
                }
            }
        } catch(e) {
		};
    }
}

JAJAX.prototype.Open = function(url, parameters, method, callback) {
    if (this.Request) {
        if (document.getElementById('loading'))
            document.getElementById('loading').style.visibility = 'visible';

        this.onDone = callback;
        this.Request.onreadystatechange = this.Busy.bind(this);
        outUrl = url;
        parameters += '&jsonMode=1&random=' + (Math.random()*(Math.random()+1));
        if (method == "GET")
            outUrl = url + '?' + parameters;
        this.Request.open(method, outUrl, true);
        if (method == "POST") {
            this.Request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            this.Request.send(parameters);
        } else {
            this.Request.setRequestHeader('Content-Type', 'text/plain');
            this.Request.send(null);
        }
    }
}

function jsonRequest(url, parameters, method, callback) {
    var j = new JAJAX();
    j.Open(url, parameters, method, callback);
}