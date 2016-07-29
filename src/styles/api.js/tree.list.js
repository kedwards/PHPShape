/**
 * @file: tree.list.js
 * @desc: tree list javascript
 *
**/

/**
 * @class: DragDropTree
 * @desc: Drag & drop handler for trees
**/
function DragDropTree()
{
	return this;
}
	/**
	 * @method: set
	 * @desc: initialisation
	 * @input:
	 *	o (string) instance: instance name,
	 *	o (string) uri: url to call: $1: action, $2: id
	 *	o (string) idContent: selected id for the right part
	 *	o (string) floatbox: floatbox div identifier,
	 *	o (object) icons: all icons:
	 *		o (string) iOpen: icon url for openable folder
	 *		o (string) IOpenOver: icon url for openable folder with "add into" flag
	 *		o (string) iClose: icon url for opened folder
	 *		o (string) iCloseOver: icon url for opened folder with "add into" flag
	 *		o (string) iEmpty: icon url for empty folder
	 *		o (string) iEmptyOver: icon url for empty folder with "add into" flag
	 *		o (string) iContent: icon url for folder, content side
	 *		o (string) iContentOver: icon url for folder with "add into" flag, content side
	 *		o (string) iSpecial: icon url for folder not linkable
	 * @output: (void)
	**/
	DragDropTree.prototype.set = function (instance, uri, idContent, floatbox, icons)
	{
		// parms
		this.instance = instance; // instance name
		this.uri = uri; // url to call
		this.floatbox = floatbox; // floatbox div id
		this.icons = icons; // icons

		// class constants
		this.TIMER_CATCH = 1000; // 1000 mSeconds: delay between mouse pressed and item catch without move
		this.TIMER_OVER = 1500; // 1500 mSeconds: delay between overing an item and opening it
		this.MOUSEX_OFFSET = 10; // distance between floatbox origin (top-left) and the mouse cursor when overing
		this.MOUSEY_OFFSET = 20;

		// class vars
		this.idCaught = false; // caught item
		this.idCaughtType = false; // tree or content

		this.idOver = false; // overed item
		this.idOverType = false; // overed type (tree|content|breadscrumb)
		this.idOverActionable = false; // true if overed item is an image
		this.idOverLinkable = false; // true if overed item is clickable for opening the right content
		this.idOverRoot = false; // true if overed item is the content root
		this.idOverAction = false; // overed item action to do (open/close/leaf)

		this.idContent = idContent; // right content

		this.timerOnCatch = false; // catch timer using TIMER_CATCH delay
		this.timerOnOver = false; // overing timer using TIMER_OVER delay
		this.dragging = false; // when an item is dragged: having an idCaught doesn't mean it is dragged (yet)

		// mouse position
		this.mousePos = false;
	}

	DragDropTree.prototype.resizeAdd = function ()
	{
		var listBox = document.getElementById('treecontent_list');
		var addBox = document.getElementById('tree_content_add');
		if ( listBox && addBox )
		{
			var xList = parseInt(listBox.offsetLeft);
			var xAdd = parseInt(addBox.offsetLeft);
			var wList = parseInt(listBox.offsetWidth);

			/* the infamous ie6... */
			if ( xAdd < xList )
			{
				xList = 0;
			}

			var newWidth = wList - (xAdd - xList) - 4;
			if ( newWidth < 0 )
			{
				newWidth = 15;
			}
			addBox.style.width = newWidth.toString() + 'px';
		}
	}

	/**
	 * @method: onClick
	 * @desc: handle the onClick event
	 * @input: (object) event: mouse event
	 * @output: (boolean) false if handled, true if not concerned
	**/
	DragDropTree.prototype.onClick = function (event)
	{
		this.mousePos = event.pos;
		if ( this.dragging )
		{
			return this.cancelDrag();
		}

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

		// get what to do from target id
		var linkableRegExp = new RegExp('linkable');
		var actionableRegExp = new RegExp('actionable');

		var linkable = event.target.className.match(linkableRegExp);
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
			this.releaseItem();
			if ( action && (action != 'leaf') )
			{
				return this.openCloseBranch(id, action);
			}
		}
		return false;
	}

	/**
	 * @method: doOnMouseDown
	 * @desc: handle the onMouseDown event
	 * @input: (object) event: mouse event
	 * @output: (boolean) false if handled, true if not concerned
	**/
	DragDropTree.prototype.onMouseDown = function (event)
	{
		this.mousePos = event.pos;
		if ( !event.target || !event.target.id || !event.target.className )
		{
			return true;
		}

		var idRegExp = new RegExp('(tree|content|breadscrumb)_([0-9]+)$');
		var catchableRegExp = new RegExp('catchable');
		var matches = false;
		if ( !event.target.className.match(catchableRegExp) || !(matches = event.target.id.match(idRegExp)) )
		{
			this.releaseItem();
			return true;
		}

		// catch item
		var type = matches[1];
		var id = matches[2];
		if ( !this.idCaught )
		{
			this.idCaught = id;
			this.idCaughtType = matches[1];
			this.startTimerOnCatch();
		}
		return false;
	}

	/**
	 * @method: onMouseUp
	 * @desc: handle the onMouseUp event
	 * @input: (object) event: mouse event
	 * @output: (boolean) false if handled, true if not concerned
	**/
	DragDropTree.prototype.onMouseUp = function (event)
	{
		this.mousePos = event.pos;
		this.cancelTimers();
		if ( this.dragging )
		{
			return this.stopDrag();
		}
		if ( !this.idCaught )
		{
			return true;
		}
		this.releaseItem();
		return false;
	}

	/**
	 * @method: onMouseMove
	 * @desc: handle the onMouseMove event
	 * @input: (object) event: mouse event
	 * @output: (boolean) false if handled, true if not concerned
	**/
	DragDropTree.prototype.onMouseMove = function (event)
	{
		this.mousePos = event.pos;
		return !this.idCaught ? true : (this.dragging ? this.moveFloatBox() : this.startDrag());
	}

	/**
	 * @method: onMouseOver
	 * @desc: handle the onMouseOver event
	 * @input: (object) event: mouse event
	 * @output: (boolean) false if handled, true if not concerned
	**/
	DragDropTree.prototype.onMouseOver = function (event)
	{
		this.mousePos = event.pos;
		if ( !this.idCaught )
		{
			return true;
		}
		if ( !event.target || !event.target.id || !event.target.className )
		{
			return true;
		}

		this.idOverType = false;
		this.idOver = false;
		this.idOverActionable = false;
		this.idOverLinkable = false;
		this.idOverRoot = false;
		this.idOverAction = false;

		var idRegExp = new RegExp('(tree|content|breadscrumb)_(more|add|[0-9]+)$');
		var matches = false;
		if ( (matches = event.target.id.match(idRegExp)) )
		{
			var actionableRegExp = new RegExp('actionable');
			var linkableRegExp = new RegExp('linkable');
			if ( (this.idContent !== false) && ((matches[2] === 'add') || (matches[2] === 'more')) )
			{
				matches[2] = this.idContent;
			}
			this.idOver = matches[2];
			this.idOverType = matches[1];
			this.idOverActionable = event.target.className.match(actionableRegExp);
			this.idOverLinkable = event.target.className.match(linkableRegExp);
			this.idOverRoot = (this.idContent !== false) && (matches[2] == this.idContent);
		}
		if ( this.idOver === false )
		{
			return false;
		}

		if ( (this.idCaught !== false) && (this.idOver != this.idCaught) )
		{
			if ( this.idOverType == 'tree' )
			{
				this.idOverAction = this.getAction(this.idOver);
				if ( this.idOverAction && this.idOverActionable )
				{
					this.startTimerOnOver();
				}
			}
			if ( (this.idOverType == 'content') || (this.idOverType == 'breadscrumb') )
			{
				if ( this.idOverLinkable )
				{
					this.startTimerOnOver();
				}
			}
		}
		this.setCursor(true);
		return false;
	}

	/**
	 * @method: onMouseOut
	 * @desc: handle the onMouseOut event
	 * @input: (object) event: mouse event
	 * @output: (boolean) false if handled, true if not concerned
	**/
	DragDropTree.prototype.onMouseOut = function (event)
	{
		this.mousePos = event.pos;

		// cancel the timer
		this.cancelTimers();
		this.setCursor(false);

		// cancel over
		this.idOverRoot = false;
		this.idOverType = false;
		this.idOver = false;
		this.idOverActionable = false;
		this.idOverLinkable = false;
		this.idOverAction = false;
		return false;
	}

	/**
	 * @method: openCloseBranch
	 * @desc: open or close the branch
	 * @input: (int) id: tree id
	 * @input: (string) action: open/close
	 * @output: (boolean) true if error, false if done
	**/
	DragDropTree.prototype.openCloseBranch = function (id, action)
	{
		var item = false;
		if ( (item = document.getElementById('tree_' + id)) )
		{
			var urlRegExp = new RegExp('(.+)/([0-9]+)$');
			var parms = action + '/' + id;
			var result = this.request(parms.replace(urlRegExp, this.uri));
			if ( result )
			{
				var idResize = 'tree';
				var scroll = getScroll(idResize);
				deleteChildren(item);
				resetSize(idResize);
				item.innerHTML = result;
				resize(idResize);
				this.resizeAdd();
				setScroll(idResize, scroll);
				if ( this.dragging && ((action == 'open') || (this.idCaught && (this.idCaught == id))) && document.getElementById('tree_' + this.idCaught) )
				{
					document.getElementById('tree_' + this.idCaught).style.fontStyle = 'italic';
				}
				return false;
			}
		}
		return true;
	}


	/**
	 * @method: startDrag
	 * @desc: start dragging the catched item
	 * @output: (boolean) false
	**/
	DragDropTree.prototype.startDrag = function ()
	{
		this.cancelTimers();
		if ( this.idCaught )
		{
			this.dragging = true;
			this.displayFloatBox();
		}
		return false;
	}

	/**
	 * @method: cancelDrag
	 * @desc: cancel dragging the catched item
	 * @output: (boolean) false
	**/
	DragDropTree.prototype.cancelDrag = function ()
	{
		this.releaseItem();
		this.dragging = false;
		return false;
	}

	/**
	 * @method: stopDrag
	 * @desc: stop dragging the catched item
	 * @output: (boolean) false
	**/
	DragDropTree.prototype.stopDrag = function ()
	{
		var idCaught = this.idCaught;
		var idCaughtType = this.idCaughtType;
		var idOver = this.idOver;
		var idOverType = this.idOverType;
		var idOverAction = this.idOverAction;
		var idOverActionable = this.idOverActionable;
		var idOverLinkable = this.idOverLinkable;
		var idOverRoot = this.idOverRoot;
		var dragging = this.dragging;

		this.cancelDrag();
		var item = false;
		if ( dragging && (item = document.getElementById('tree_root')) && (idOver !== false) && (idCaught !== false) && (idOver != idCaught) )
		{
			var urlRegExp = new RegExp('(.+)/([0-9]+)$');
			var parms = 'move/' + idCaught;
			var toId = ((idOverType == 'content') && idOverRoot ? '&eid=' : (idOverActionable || ((idOverType == 'content') && idOverLinkable) || (idOverAction == 'close') || (idOver === '0') ? '&pid=' : '&tid=')) + idOver;
			var uri = parms.replace(urlRegExp, this.uri) + toId;
			var result = this.request(uri);
			if ( result )
			{
				var idResize = 'tree';
				var scroll = getScroll(idResize);
				deleteChildren(item);
				resetSize(idResize);
				item.innerHTML = result;
				resize(idResize);
				this.resizeAdd();
				setScroll(idResize, scroll);
				this.displayContent(this.idContent);
			}
		}
		return false;
	}

	/**
	 * @method: openCloseOnOver
	 * @desc: open or close the branch if over
	 * @output: (boolean) true
	**/
	DragDropTree.prototype.openCloseOnOver = function ()
	{
		var item = false;
		if ( (this.idOver !== false) && document.getElementById('tree_' + this.idOver) && this.idOverAction )
		{
			this.openCloseBranch(this.idOver, this.idOverAction);
			this.idOverAction = false;
			this.cancelTimers();
		}
		return true;
	}

	/**
	 * @method: releaseItem
	 * @desc: raz the id caught
	 * @output: (void)
	**/
	DragDropTree.prototype.releaseItem = function ()
	{
		this.cancelTimers();
		this.setCursor(false);
		this.hideFloatBox();
		this.idCaught = false;
		this.idCaughtType = false;
	}

	/**
	 * @method: startTimerOnCatch
	 * @desc: start the catch timer
	 * @output: (void)
	**/
	DragDropTree.prototype.startTimerOnCatch = function()
	{
		this.timerOnCatch = window.setTimeout(this.instance + '.startDrag()', this.TIMER_CATCH);
	}

	/**
	 * @method: startTimerOnOver
	 * @desc: start the over timer
	 * @output: (void)
	**/
	DragDropTree.prototype.startTimerOnOver = function()
	{
		if ( this.idOverType == 'tree' )
		{
			this.timerOnOver = window.setTimeout(this.instance + '.openCloseOnOver()', this.TIMER_OVER);
		}
		if ( (this.idOverType == 'content') || (this.idOverType == 'breadscrumb') )
		{
			this.timerOnOver = window.setTimeout(this.instance + '.contentOnOver()', this.TIMER_OVER);
		}
	}

	/**
	 * @method: cancelTimers
	 * @desc: cancel all timers
	 * @output: (void)
	**/
	DragDropTree.prototype.cancelTimers = function()
	{
		if ( this.timerOnCatch )
		{
			window.clearTimeout(this.timerOnCatch);
			this.timerOnCatch = false;
		}
		if ( this.timerOnOver )
		{
			window.clearTimeout(this.timerOnOver);
			this.timerOnOver = false;
		}
	}

	/**
	 * @method: displayFloatBox
	 * @desc: fill the floatbox with the caught item and display it
	 * @output: (void)
	**/
	DragDropTree.prototype.displayFloatBox = function ()
	{
		if ( this.dragging )
		{
			var action = this.getAction(this.idCaught);
			if ( action === 'close' )
			{
				this.openCloseBranch(this.idCaught, 'close');
			}

			var iconSrc = this.icons.iEmpty;
			if ( this.icons.iSpecial )
			{
				var img_node = document.getElementById('img_' + this.idCaughtType + '_' + this.idCaught);
				var actionableRegExp = new RegExp('actionable');
				if ( img_node && img_node.className && !img_node.className.match(actionableRegExp) )
				{
					iconSrc = this.icons.iSpecial;
				}
			}

			var iOver = new Image();
			iOver.src = iconSrc;
			iOver.alt = '';
			document.getElementById(this.floatbox).appendChild(iOver);

			var txtNode = 'txt_' + this.idCaughtType + '_' + this.idCaught;
			var text = '';
			for ( var i=0; i < document.getElementById(txtNode).childNodes.length; i++ )
			{
				if ( document.getElementById(txtNode).childNodes[i].nodeType == 3 )
				{
					text = document.getElementById(txtNode).childNodes[i].nodeValue;
				}
			}
			if ( text )
			{
				var tOver = document.createTextNode(text);
				document.getElementById(this.floatbox).appendChild(tOver);
			}
			this.moveFloatBox();
			document.getElementById(this.floatbox).style.display = '';
			if ( document.getElementById('tree_' + this.idCaught) )
			{
				document.getElementById('tree_' + this.idCaught).style.fontStyle = 'italic';
			}
			if ( document.getElementById('content_' + this.idCaught) )
			{
				document.getElementById('content_' + this.idCaught).style.fontStyle = 'italic';
			}
		}
	}

	/**
	 * @method: moveFloatBox
	 * @desc: fill the floatbox with the caught item and display it
	 * @output: (boolean) false
	**/
	DragDropTree.prototype.moveFloatBox = function ()
	{
		if ( this.dragging )
		{
			document.getElementById(this.floatbox).style.top = this.mousePos.y + this.MOUSEY_OFFSET + 'px';
			document.getElementById(this.floatbox).style.left = this.mousePos.x + this.MOUSEX_OFFSET + 'px';
		}
		return false;
	}

	/**
	 * @method: hideFloatBox
	 * @desc: hide the floatbox and delete its content
	 * @output: (void)
	**/
	DragDropTree.prototype.hideFloatBox = function ()
	{
		if ( this.dragging )
		{
			document.getElementById(this.floatbox).style.display = 'none';
			deleteChildren(document.getElementById(this.floatbox));
			if ( document.getElementById('tree_' + this.idCaught) )
			{
				document.getElementById('tree_' + this.idCaught).style.fontStyle = 'normal';
			}
			if ( document.getElementById('content_' + this.idCaught) )
			{
				document.getElementById('content_' + this.idCaught).style.fontStyle = 'normal';
			}
		}
	}

	/**
	 * @method: setCursor
	 * @desc: change the cursor for the idOver item
	 * @input: status: true: special, false, auto
	 * @output: (void)
	**/
	DragDropTree.prototype.setCursor = function (status)
	{
		if ( this.idOver !== false )
		{
			var cursorStyle = status && this.idCaught && (this.idOver == this.idCaught) ? 'not-allowed' : 'pointer';
			if ( document.getElementById('node_tree_' + this.idOver) )
			{
				document.getElementById('node_tree_' + this.idOver).style.cursor = cursorStyle;
			}
			if ( document.getElementById('img_tree_' + this.idOver) )
			{
				document.getElementById('img_tree_' + this.idOver).style.cursor = cursorStyle;
			}
			if ( document.getElementById('txt_tree_' + this.idOver) )
			{
				document.getElementById('txt_tree_' + this.idOver).style.cursor = cursorStyle;
			}

			if ( document.getElementById('img_content_' + this.idOver) )
			{
				document.getElementById('img_content_' + this.idOver).style.cursor = cursorStyle;
			}
			if ( document.getElementById('txt_content_' + this.idOver) )
			{
				document.getElementById('txt_content_' + this.idOver).style.cursor = cursorStyle;
			}

			if ( status )
			{
				this.startOver();
			}
			else
			{
				this.endOver();
			}
		}
	}

	/**
	 * @method: startOver
	 * @desc: set over decorations
	 * @output: (void)
	**/
	DragDropTree.prototype.startOver = function ()
	{
		if ( (this.idOver !== false) && (this.idCaught !== false) && (this.idOver != this.idCaught) )
		{
			if ( this.idOverType == 'tree' )
			{
				if ( this.idOverActionable && this.idOverAction && document.getElementById('img_tree_' + this.idOver) )
				{
					// replace picto
					if ( this.idOverAction == 'open' )
					{
						document.getElementById('img_tree_' + this.idOver).src = this.icons.iOpenOver;
					}
					else if ( this.idOverAction == 'close' )
					{
						document.getElementById('img_tree_' + this.idOver).src = this.icons.iCloseOver;
					}
					else if ( this.idOverAction == 'leaf' )
					{
						document.getElementById('img_tree_' + this.idOver).src = this.icons.iEmptyOver;
					}
				}

				// underline
				if ( !this.idOverActionable || (this.idOverAction == 'close') )
				{
					if ( document.getElementById('childs_tree_' + this.idOver) )
					{
						document.getElementById('childs_tree_' + this.idOver).style.borderTop = '1px solid black';
					}
					else
					{
						document.getElementById('node_tree_' + this.idOver).style.borderBottom = '1px solid black';
					}
				}
			}

			if ( this.idOverType == 'content' )
			{
				if ( this.idOverRoot )
				{
					document.getElementById('tree_content_add').style.borderLeft = '1px solid black';
				}
				if ( !this.idOverLinkable )
				{
					if ( document.getElementById('content_' + this.idOver) )
					{
						document.getElementById('content_' + this.idOver).style.borderRight = '1px solid black';
					}
				}
				// replace picto
				else if ( this.idOverLinkable && document.getElementById('img_content_' + this.idOver) )
				{
					document.getElementById('img_content_' + this.idOver).src = this.icons.iContentOver;
				}
			}
		}
	}

	/**
	 * @method: endOver
	 * @desc: cancel over decorations
	 * @output: (void)
	**/
	DragDropTree.prototype.endOver = function ()
	{
		if ( (this.idOver !== false) && (this.idCaught !== false) && (this.idOver != this.idCaught) )
		{
			if ( this.idOverType == 'tree' )
			{
				if ( document.getElementById('node_tree_' + this.idOver) )
				{
					document.getElementById('node_tree_' + this.idOver).style.borderBottom = 'none';
				}
				if ( document.getElementById('childs_tree_' + this.idOver) )
				{
					document.getElementById('childs_tree_' + this.idOver).style.borderTop = 'none';
				}

				// replace picto
				if ( document.getElementById('img_tree_' + this.idOver) )
				{
					if ( this.idOverAction == 'open' )
					{
						document.getElementById('img_tree_' + this.idOver).src = this.icons.iOpen;
					}
					else if ( this.idOverAction == 'close' )
					{
						document.getElementById('img_tree_' + this.idOver).src = this.icons.iClose;
					}
					else if ( this.idOverAction == 'leaf' )
					{
						document.getElementById('img_tree_' + this.idOver).src = this.icons.iEmpty;
					}
				}
			}

			if ( this.idOverType == 'content' )
			{
				if ( this.idOverRoot )
				{
					document.getElementById('tree_content_add').style.borderLeft = 'none';
				}
				if ( document.getElementById('content_' + this.idOver) )
				{
					document.getElementById('content_' + this.idOver).style.borderRight = 'none';
				}
				// replace picto
				if ( this.idOverLinkable && document.getElementById('img_content_' + this.idOver) )
				{
					document.getElementById('img_content_' + this.idOver).src = this.icons.iContent;
				}
			}
		}
	}

	/**
	 * @method: getAction
	 * @desc: return the action from the class of the id
	 * @input: (string) id: targeted id
	 * @output: (mixed): false if not found, open/close/leaf otherwise
	**/
	DragDropTree.prototype.getAction = function (id)
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


	/**
	 * @method: contentOnOver
	 * @desc: display the right part
	 * @output: (boolean) true
	**/
	DragDropTree.prototype.contentOnOver = function ()
	{
		if ( this.idOver && this.idOverLinkable )
		{
			this.displayContent(this.idOver);
			this.cancelTimers();
		}
		return true;
	}

	/**
	 * @method: displayContent
	 * @desc: display the right part
	 * @input: (string) id
	 * @output: (boolean) false
	**/
	DragDropTree.prototype.displayContent = function (id)
	{
		var urlRegExp = new RegExp('(.+)/([0-9]+)$');
		var parms = 'content/' + id;
		var uri = parms.replace(urlRegExp, this.uri);
		var result = this.request(uri);
		if ( result )
		{
			var idResize = 'treecontent';
			var node = document.getElementById(idResize).firstChild;
			if ( node )
			{
				deleteChildren(node);
			}
			resetSize(idResize);
			document.getElementById(idResize).innerHTML = result;
			resize(idResize);
			this.resizeAdd();
		}
		this.idContent = id;
		return false;
	}

	/**
	 * @method: request
	 * @desc: ajax call for html
	 * @input: (string) url: url to call
	 * @output: (mixed) false if error, html otherwise
	**/
	DragDropTree.prototype.request = function (uri)
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

