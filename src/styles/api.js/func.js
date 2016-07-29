/**
 * @file: func.js
 * @desc: generic javascript functions
 *
**/

/**
 *
 * @function: _extends
 * @desc: prototype for inherited class
 *
**/
Function.prototype._extends = function (_parentClass)
{
	this.prototype = new _parentClass;
	this.prototype.constructor = this;
	this.prototype._parentClass = _parentClass.prototype;
	return this;
}

/**
 * @function: HttpRequest
 * @desc: create a new HttpRequest object
 *
**/
function HttpRequest()
{
	// get an instance of the http request header: gecko
	if ( window.XMLHttpRequest )
	{
		return new XMLHttpRequest();
	}
	// IE
	else if ( window.ActiveXObject )
	{
		try	{
			httpRequest = new ActiveXObject('Msxml2.XMLHTTP');
		}
		catch (e) {
			return new ActiveXObject('Microsoft.XMLHTTP');
		}
	}
	return httpRequest;
}

/**
 * @function: deleteChildren
 * @desc: remove all children from the node
 *
**/
function deleteChildren(node)
{
	if ( !node )
	{
		return;
	}
	for ( var i = node.childNodes.length - 1; i >= 0; i-- )
	{
		if ( node.childNodes[i].hasChildNodes() )
		{
			deleteChildren(node.childNodes[i]);
		}
		node.removeChild(node.childNodes[i]);
	}
}


/**
 * @function: getPageHeight
 * @desc: return the page Height minus the footer
**/
function getPageHeight()
{
	var pageHeight = 768; /* in case we don't find a correct pageHeight */
	var marginHeight = 31; /* margin for scrollbars */

	// All but IE
	if ( window.innerHeight && (typeof(window.innerHeight) == 'number') )
	{
		pageHeight = parseInt(window.innerHeight);
	}
	// IE >= 6, strict
	else if ( document.documentElement && document.documentElement.clientHeight )
	{
		pageHeight = parseInt(document.documentElement.clientHeight);
	}
	// other IE
	else if ( document.body && document.body.clientHeight )
	{
		pageHeight = parseInt(document.body.clientHeight);
	}
	var footerHeight = parseInt(document.getElementById('footer').offsetHeight);
	var gimeHeightBox = document.getElementById('gimeheight');
	if ( gimeHeightBox )
	{
		footerHeight = parseInt(gimeHeightBox.offsetHeight);
	}
	pageHeight = pageHeight - footerHeight - marginHeight;
	return pageHeight;
}


/**
 * @function: getAbsOffsetTop
 * @desc: return the absolute y on the page
 * @input: (string) id: element id
**/
function getAbsOffsetTop(id)
{
	if ( typeof(id) === 'object' )
	{
		var element = id;
	}
	else
	{
		var element = document.getElementById(id);
	}
	var offsetTop = 0;
	while( element != null )
	{
		offsetTop += element.offsetTop;
		element = element.offsetParent;
	}
	return offsetTop;
}
function getAbsOffsetLeft(id)
{
	if ( typeof(id) === 'object' )
	{
		var element = id;
	}
	else
	{
		var element = document.getElementById(id);
	}
	var offsetLeft = 0;
	while( element != null )
	{
		offsetLeft += element.offsetLeft;
		element = element.offsetParent;
	}
	return offsetLeft;
}


/**
 * @function: Resize
 * @desc: adjust the id size to the visible area
 * @input: (string) id: element id
**/
function resize(id)
{
	var pageHeight = getPageHeight();
	var boxY = getAbsOffsetTop(id);
	var box = document.getElementById(id);
	if ( !box )
	{
		return false;
	}
	var boxHeight = parseInt(box.offsetHeight);
	if ( (boxY + boxHeight) > pageHeight )
	{
		boxHeight = pageHeight - boxY;
		if ( boxHeight < 45 )
		{
			boxHeight = 45;
		}
		box.style.height = boxHeight.toString() + 'px';
	}
	return true;
}
function resetSize(id)
{
	var box = document.getElementById(id);
	if ( box )
	{
		box.style.height = '';
		return true;
	}
	return false;
}
function ResizeBoxes()
{
}
	ResizeBoxes.prototype.set = function ()
	{
		this.ids = new Array();
	}
	ResizeBoxes.prototype.register = function (id)
	{
		this.ids.push(id);
	}
	ResizeBoxes.prototype.resize = function (id)
	{
		if ( id )
		{
			resize(id);
		}
		else if ( this.ids.length )
		{
			for ( var i = 0; i < this.ids.length; i++ )
			{
				resetSize(this.ids[i]);
				resize(this.ids[i]);
			}
		}
	}


/**
 * @function: getScroll
 * @desc: retrieve the vertical scroll of an element
 * @input: (string) id: element id
 * @output: (int) scrollTop of an id in pixel
**/
function getScroll(id)
{
	if ( !document.getElementById(id) )
	{
		return false;
	}
	return document.getElementById(id) ? parseInt(document.getElementById(id).scrollTop) : 0;
}


/**
 * @function: setScroll
 * @desc: retrieve the vertical scroll of an element
 * @input: (string) id: element id
 * @input: (int) scrollTop value
**/
function setScroll(id, value)
{
	if ( !document.getElementById(id) )
	{
		return false;
	}
	document.getElementById(id).scrollTop = value;
}

/**
 * @function: checktimeshift
 * @desc: check the user settings time shift versus browser time
 * @input: (int) usertimezone: timezone set for the user
 * @input: (string) message: confirmation message
 * @input: (string) uri: base url for index
**/
function checktimeshift(timezone, message, uri, uri_index)
{
	var date = new Date();
	var timeshift = Math.round(date.getTimezoneOffset() / 15) * -15;
	var utimeshift = Math.round(timezone / (60 * 15)) * 15;
	if ( timeshift != utimeshift )
	{
		message = message.replace('%1$s', toHM(timeshift));
		message = message.replace('%2$s', toHM(utimeshift));
		if ( confirm(message) )
		{
			var httpRequest = new HttpRequest();
			if ( httpRequest )
			{
				var now = new Date();
				var uri = uri + '&ts=' + (timeshift * 60) + '&nocache=' + now.getTime();
				httpRequest.open('GET', uri, false);
				httpRequest.send(null);
				document.location.href = uri_index;
				return false;
			}
		}
		return true;
	}
}

/**
 * @func: toHM
 * @desc: turn a timeshift to its h:m format
 * @input: (int) timeshift
 * @output: (string) formated timeshift (-)hh(:mm)
**/
function toHM(timeshift)
{
	var strTime = '';
	var hTime = Math.floor(timeshift / 60);
	var mTime = Math.floor(timeshift % 60);

	strTime = 'UTC' + ((hTime < 0) || (mTime < 0) ? '-' : '+') + Math.abs(hTime);
	if ( mTime != 0 )
	{
		strTime = strTime + ':' + Math.abs(mTime);
	}
	return strTime;
}


/**
 * @function: _dump
 * @desc: output a result to the debug id (innerHTML)
 * @input: (string) text to display
**/
function _echo(txt, reset)
{
	if ( document.getElementById('debug') )
	{
		document.getElementById('debug').innerHTML = reset ? txt : document.getElementById('debug').innerHTML + '<br />' + txt;
	}
}
function _dump(txt)
{
	_echo(txt, true);
}

function _debug(txt)
{
	_echo(txt, false);
}
