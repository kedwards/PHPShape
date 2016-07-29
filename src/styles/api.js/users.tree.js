/**
 * @file: users.tree.js
 * @desc: reload users tree depending the pagination and/or the name filter
 *
**/

/**
 * @class: UsersTreeAction
 * @desc: handle header actions for users tree
**/

function UsersTreeAction()
{
	return this;
}

	UsersTreeAction.prototype.set = function (instance, searchfield, togglefield, toggleImgOpen, toggleImgClose, tpageId, ppageId, curpageId, prevpageId, nextpageId, pPrevBlockId, pNextBlockId, item_id, item_type, uri, startId, insideId, containerId)
	{
		this.timer = false;
		this.TIMER_CATCH = 1000; /* 1000 milli-seconds */
		this.TIMER_SHORT = 500; /* short timer */
		this.KEY_ENTER = 13; /* Enter key code */
		this.filter = '';

		/* parms */
		this.instance = instance;

		this.searchfield = searchfield;
		this.togglefield = togglefield;
		this.toggleImgOpen = toggleImgOpen;
		this.toggleImgClose = toggleImgClose;

		this.tpageId = tpageId;
		this.ppageId = ppageId;
		this.pageId = curpageId;
		this.prevPageId = prevpageId;
		this.nextPageId = nextpageId;

		this.pPrevBlockId = pPrevBlockId;
		this.pNextBlockId = pNextBlockId;

		this.item_id = item_id;
		this.item_type = item_type;

		this.uri = uri;
		this.startId = startId;
		this.insideId = insideId;
		this.containerId = containerId;

		/* start catching filter changes */
		this.setTimer(this.TIMER_CATCH);
	}

	UsersTreeAction.prototype.setTimer = function(duration)
	{
		this.timer = window.setTimeout(this.instance + '.catchTimer()', duration);
	}

	UsersTreeAction.prototype.catchTimer = function ()
	{
		if ( document.getElementById(this.searchfield) && (this.filter != document.getElementById(this.searchfield).value) )
		{
			this.reload();
		}
		this.setTimer(this.TIMER_CATCH);
	}

	UsersTreeAction.prototype.onKey = function (e)
	{
		if ( this.timer )
		{
			window.clearTimeout(this.timer);
		}
		this.setTimer(this.TIMER_SHORT);

		// check return
		var keyPressed = false;
		if ( window.event )
		{
			keyPressed = window.event.keyCode;
		}
		else if ( e )
		{
			if ( e.keyCode )
			{
				keyPressed = e.keyCode;
			}
			else if ( e.which )
			{
				keyPressed = e.which;
			}
		}
		if ( keyPressed == this.KEY_ENTER )
		{
			this.reload();
			if ( this.filter == '' )
			{
				this.toggle();
			}
		}
		return !(keyPressed == this.KEY_ENTER);
	}

	UsersTreeAction.prototype.toggle = function()
	{
		if ( document.getElementById(this.searchfield + 'container').style.display == 'none' )
		{
			document.getElementById(this.searchfield + 'container').style.display = '';
			if ( this.togglefield )
			{
				document.getElementById(this.togglefield).src = this.toggleImgClose;
			}
			if ( document.getElementById(this.searchfield).style.display != 'none' )
			{
				document.getElementById(this.searchfield).focus();
			}
		}
		else
		{
			document.getElementById(this.searchfield + 'container').style.display = 'none';
			if ( this.togglefield )
			{
				document.getElementById(this.togglefield).src = this.toggleImgOpen;
			}
		}
	}

	UsersTreeAction.prototype.reload = function()
	{
		this.filter = document.getElementById(this.searchfield) ? document.getElementById(this.searchfield).value : '';
		var uri = this.uri + (this.filter ? '&' + this.searchfield + '=' + encodeURIComponent(this.filter) : '');
		var result = this.request(uri);
		this.display(result);
	}

	/**
	 * @method: pagination
	**/
	UsersTreeAction.prototype.pageFirst = function()
	{
		if ( (this.getIntValue(this.ppageId) <= 0) || (this.getIntValue(this.pageId) == 1) )
		{
			return true;
		}
		return this.gotoPage(0);
	}

	UsersTreeAction.prototype.pagePrevious = function()
	{
		var current = this.getIntValue(this.pageId) > 1 ? (this.getIntValue(this.pageId) - 1) * this.getIntValue(this.ppageId) : -1;
		if ( (this.getIntValue(this.ppageId) > 0) && (this.getIntValue(this.prevPageId) < current) )
		{
			return this.gotoPage(this.getIntValue(this.prevPageId));
		}
		return true;
	}

	UsersTreeAction.prototype.pageNext = function()
	{
		var current = this.getIntValue(this.pageId) > 1 ? (this.getIntValue(this.pageId) - 1) * this.getIntValue(this.ppageId) : -1;
		if ( (this.getIntValue(this.ppageId) > 0) && (this.getIntValue(this.nextPageId) > current) )
		{
			return this.gotoPage(this.getIntValue(this.nextPageId));
		}
		return true;
	}

	UsersTreeAction.prototype.pageLast = function()
	{
		var current = this.getIntValue(this.pageId) > 1 ? (this.getIntValue(this.pageId) - 1) * this.getIntValue(this.ppageId) : -1;
		var lastPage = this.getIntValue(this.tpageId) > 1 ? (this.getIntValue(this.tpageId) - 1) * this.getIntValue(this.ppageId) + 1 : -1;
		if ( (this.getIntValue(this.ppageId) > 0) && (lastPage > current) )
		{
			return this.gotoPage(lastPage);
		}
		return true;
	}

	UsersTreeAction.prototype.gotoPage = function(start)
	{
		var uri = this.uri + (this.filter ? '&' + this.searchfield + '=' + encodeURIComponent(this.filter) : '') + (start > 0 ? '&' + this.startId + '=' + start : '');
		var result = this.request(uri);
		this.display(result);
		return false;
	}

	UsersTreeAction.prototype.display = function (result)
	{
		if ( !result )
		{
			return false;
		}
		var idResize = this.item_type ? 'treecontentcombined' : 'treeright';
		var idReload = this.insideId;
		var scroll = getScroll(idResize);
		deleteChildren(document.getElementById(idReload));
		resetSize(idResize);
		document.getElementById(idReload).innerHTML = result;
		resize(idResize);
		setScroll(idResize, scroll);
		if ( document.getElementById(this.searchfield) )
		{
			if ( document.getElementById(this.searchfield).style.display != 'none' )
			{
				document.getElementById(this.searchfield).focus();
			}
		}
		this.showPagination();
		return true;
	}

	UsersTreeAction.prototype.showPagination = function ()
	{
		var pPrevShow = (this.getIntValue(this.ppageId) > 0) && (this.getIntValue(this.pageId) > 1);
		var pNextShow = (this.getIntValue(this.ppageId) > 0) && (this.getIntValue(this.pageId) < this.getIntValue(this.tpageId));

		document.getElementById(this.pPrevBlockId).style.display = pPrevShow ? '' : 'none';
		document.getElementById(this.pNextBlockId).style.display = pNextShow ? '' : 'none';
	}

	UsersTreeAction.prototype.getIntValue = function (id)
	{
		return document.getElementById(id) ? parseInt(document.getElementById(id).value) : 0;
	}

	/**
	 * @method: request
	 * @desc: ajax call for html
	 * @input: (string) uri: uri to call
	 * @output: (mixed) false if error, html otherwise
	**/
	UsersTreeAction.prototype.request = function (uri)
	{
		if ( this.item_id )
		{
			uri = uri + '&' + this.item_type + '=' + document.getElementById(this.item_id).value;
		}
		_dump(uri);
		var httpRequest = new HttpRequest();
		if ( httpRequest )
		{
			var now = new Date();
			var uri = uri + '&nocache=' + now.getTime();
			httpRequest.open('GET', uri, false);
			httpRequest.send(null);
			if ( httpRequest.responseText )
			{
				return httpRequest.responseText;
			}
		}
		return false;
	}
