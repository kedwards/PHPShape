/**
 * @file: tree.openclose.js
 * @desc: open close tree javascript
 *
**/

/**
 * @class: OpenCloseTree
 * @desc: Open & Close handler for trees
**/
function OpenCloseTree()
{
	return this;
}
	/**
	 * @method: set
	 * @desc: initialisation
	 * @input: (string) uri: url to call: $1: action, $2: id
	 * @output: (void)
	**/
	OpenCloseTree.prototype.set = function (uri, resizableArea)
	{
		// parms
		this.uri = uri; // url to call
		this.resizableArea = resizableArea;
		this.idContent = false;
	}

	OpenCloseTree.prototype.setIdContent = function (idContent)
	{
		this.idContent = idContent;
	}

	/**
	 * @method: onClick
	 * @desc: handle the onClick event
	 * @input: (object) event: mouse event
	 * @output: (boolean) false if handled, true if not concerned
	**/
	OpenCloseTree.prototype.onClick = function (event)
	{
		if ( !event.target || !event.target.id || !event.target.className )
		{
			return true;
		}

		// check if clickable and get id from target id
		var idRegExp = new RegExp('_([0-9]+)$');
		var clickableRegExp = new RegExp('actionable|linkable');

		var matches = false;
		if ( !event.target.className.match(clickableRegExp) || !(matches = event.target.id.match(idRegExp)) )
		{
			return true;
		}
		var id = matches[1];

		var linkableRegExp = new RegExp('linkable');
		var actionableRegExp = new RegExp('actionable');

		var linkable = this.idContent === false ? false : event.target.className.match(linkableRegExp);
		var actionable = event.target.className.match(actionableRegExp);
		if ( !linkable && !actionable )
		{
			return true;
		}

		// actions
		if ( linkable )
		{
			this.displayContent(id);
		}
		if ( actionable )
		{
			var action = this.getAction(id);
			if ( action === false )
			{
				return true;
			}
			if ( action != 'leaf' )
			{
				return this.openCloseBranch(id, action);
			}
		}
		return false;
	}

	/**
	 * @method: request
	 * @desc: ajax call for html
	 * @input: (string) url: url to call
	 * @output: (mixed) false if error, html otherwise
	**/
	OpenCloseTree.prototype.request = function (uri)
	{
		_dump(uri);
		var httpRequest = new HttpRequest();
		if ( httpRequest )
		{
			var now = new Date();
			uri = uri + '&nocache=' + now.getTime();
			httpRequest.open('GET', uri, false);
			httpRequest.send(null);
			if ( httpRequest.responseText && (httpRequest.responseText.substr(0, 6) != '*Error') )
			{
				return httpRequest.responseText;
			}
			var content = httpRequest.responseText.substr(6, httpRequest.responseText.length - 6);
		}
		return false;
	}

	/**
	 * @method: openCloseBranch
	 * @desc: open or close the branch
	 * @input: (int) id: tree id
	 * @input: (string) action: open/close
	 * @output: (boolean) true if error, false if done
	**/
	OpenCloseTree.prototype.openCloseBranch = function (id, action)
	{
		var item = false;
		if ( (item = document.getElementById('tree_' + id)) )
		{
			var now = new Date();
			var urlRegExp = new RegExp('(.+)/([0-9]+)$');
			var parms = action + '/' + id;
			var uri = parms.replace(urlRegExp, this.uri);
			var result = this.request(uri);
			if ( result )
			{
				var idResize = this.resizableArea;
				var scroll = getScroll(idResize);
				deleteChildren(item);
				resetSize(idResize);
				item.innerHTML = result;
				resize(idResize);
				setScroll(idResize, scroll);
				return false;
			}
		}
		return true;
	}

	/**
	 * @method: displayContent
	 * @desc: display the content
	 * @input: (string) id
	 * @output: (boolean) false
	**/
	OpenCloseTree.prototype.displayContent = function (id)
	{
		var urlRegExp = new RegExp('(.+)/([0-9]+)$');
		var parms = 'content/' + id;
		var uri = parms.replace(urlRegExp, this.uri);
		var result = this.request(uri);
		if ( result )
		{
			var idResize = this.resizableArea;
			deleteChildren(document.getElementById(idResize));
			resetSize(idResize);
			document.getElementById(idResize).innerHTML = result;
			resize(idResize);
		}
		this.idContent = id;
		return false;
	}

	/**
	 * @method: getAction
	 * @desc: return the action from the class of the id
	 * @input: (string) id: targeted id
	 * @output: (mixed): false if not found, open/close/leaf otherwise
	**/
	OpenCloseTree.prototype.getAction = function (id)
	{
		var actionRegExp = new RegExp('tree_(opened|closed|leaf)');
		var matches = false;
		var node = document.getElementById('node_tree_' + id);
		if ( !node || !node.className || !(matches = node.className.match(actionRegExp)) )
		{
			return false;
		}
		return action = matches[1] == 'opened' ? 'close' : (matches[1] == 'closed' ? 'open' : 'leaf');
	}
