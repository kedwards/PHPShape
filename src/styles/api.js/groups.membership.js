/**
 * @file: groups.membership.js
 * @desc: groups membership javascript
 *
**/

/**
 * @class: ContentTree
 * @desc: Drag & drop handler for trees
**/
function ContentTree()
{
	return this;
}
	/**
	 * @method: set
	 * @desc: initialisation
	 * @input:
	 *	o (string) instance: instance name,
	 *	o (string) uri: url to call: $1: tree, $2: action, $3: id
	 *	o (string) idContent: selected id for the content part
	 *	o (string) idContentTree: content comming from tree
	 *	o (string) floatbox: floatbox div identifier,
	 *	o (object) icons: all icons:
	 *		o (string) iOpen: icon url for openable folder
	 *		o (string) IOpenOver: icon url for openable folder with "add into" flag
	 *		o (string) iClose: icon url for opened folder
	 *		o (string) iCloseOver: icon url for opened folder with "add into" flag
	 *		o (string) iEmpty: icon url for empty folder
	 *		o (string) iEmptyOver: icon url for empty folder with "add into" flag
	 *		o (string) iContentLTree: icon url for groups, content side
	 *		o (string) iContentRTree: icon url for users, content side
	 * @output: (void)
	**/
	ContentTree.prototype.set = function (instance, uri, idContent, idContentTree, floatbox, icons)
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
		this.idCaughtTree = false; // user/grp

		this.idOver = false; // overed item
		this.idOverType = false; // overed type (tree|content|breadscrumb)
		this.idOverTree = false; // user/grp
		this.idOverIcon = false; // over item icon save
		this.idOverActionable = false; // true if overed item is an image
		this.idOverLinkable = false; // true if overed item is clickable for opening the right content
		this.idOverAction = false; // overed item action to do (open/close/leaf)

		this.idContent = idContent; // content
		this.idContentTree = idContentTree; // content tree

		this.timerOnCatch = false; // catch timer using TIMER_CATCH delay
		this.timerOnOver = false; // overing timer using TIMER_OVER delay
		this.dragging = false; // when an item is dragged: having an idCaught doesn't mean it is dragged (yet)

		// mouse position
		this.mousePos = false;

		// content container
		this.leftContainer = 'tree';
		this.contentContainer = 'treecontentcombined';
		this.rightContainer = 'treeright';
	}

	ContentTree.prototype.resizeAdd = function ()
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
	ContentTree.prototype.onClick = function (event)
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
		var idRegExp = new RegExp('(user|group)_(tree|content|breadscrumb)_([0-9]+)$');
		var clickableRegExp = new RegExp('actionable|linkable');
		var matches = false;
		if ( !event.target.className.match(clickableRegExp) || !(matches = event.target.id.match(idRegExp)) )
		{
			return true;
		}
		var tree = matches[1];
		var type = matches[2];
		var id = matches[3];

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
			this.displayContent(id, tree);
		}
		if ( actionable )
		{
			var action = this.getAction(id, tree, type);
			if ( action === false )
			{
				return true;
			}
			this.releaseItem();
			if ( (action == 'open') || (action == 'close') )
			{
				return this.openCloseBranch(id, tree, type, action);
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
	ContentTree.prototype.onMouseDown = function (event)
	{
		this.mousePos = event.pos;
		if ( !event.target || !event.target.id || !event.target.className )
		{
			return true;
		}

		var idRegExp = new RegExp('(user|group)_(tree|content|breadscrumb)_([0-9]+)$');
		var catchableRegExp = new RegExp('catchable');
		var matches = false;
		if ( !event.target.className.match(catchableRegExp) || !(matches = event.target.id.match(idRegExp)) )
		{
			this.releaseItem();
			return true;
		}

		// catch item
		var tree = matches[1];
		var type = matches[2];
		var id = matches[3];
		if ( !this.idCaught )
		{
			this.idCaught = id;
			this.idCaughtType = type;
			this.idCaughtTree = tree;
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
	ContentTree.prototype.onMouseUp = function (event)
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
	ContentTree.prototype.onMouseMove = function (event)
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
	ContentTree.prototype.onMouseOver = function (event)
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

		this.idOver = false;
		this.idOverType = false;
		this.idOverTree = false;
		this.idOverIcon = false;
		this.idOverActionable = false;
		this.idOverLinkable = false;
		this.idOverAction = false;

		var contentRegExp = new RegExp('_content');
		if ( event.target.id.match(contentRegExp) )
		{
			this.idOver = this.idContent;
			this.idOverTree = this.idContentTree;
			this.idOverType = 'content';
		}
		else
		{
			var idRegExp = new RegExp('(user|group)_(tree|breadscrumb)_([0-9]+)$');
			var matches = false;
			if ( (matches = event.target.id.match(idRegExp)) )
			{
				var actionableRegExp = new RegExp('actionable');
				var linkableRegExp = new RegExp('linkable');
				this.idOver = matches[3];
				this.idOverType = matches[2];
				this.idOverTree = matches[1];
				this.idOverActionable = event.target.className.match(actionableRegExp);
				this.idOverLinkable = event.target.className.match(linkableRegExp);
			}
		}
		if ( !this.idOver )
		{
			return false;
		}

		if ( (this.idOverType == 'tree') && (this.idOverTree == 'group') )
		{
			this.idOverAction = this.getAction(this.idOver, this.idOverTree, this.idOverType);
			if ( this.idOverActionable )
			{
				this.startTimerOnOver();
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
	ContentTree.prototype.onMouseOut = function (event)
	{
		this.mousePos = event.pos;

		// cancel the timer
		this.cancelTimers();
		this.setCursor(false);

		// cancel over
		this.idOver = false;
		this.idOverType = false;
		this.idOverTree = false;
		this.idOverIcon = false;
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
	ContentTree.prototype.openCloseBranch = function (id, tree, type, action)
	{
		var item = false;
		if ( (item = document.getElementById(tree + '_' + type + '_' + id)) )
		{
			var urlRegExp = new RegExp('(.+)/(.+)/([0-9]+)$');
			var parms = tree + '/' + action + '/' + id;
			var result = this.request(parms.replace(urlRegExp, this.uri));
			if ( result )
			{
				var idResize = this.leftContainer;
				var scroll = getScroll(idResize);
				deleteChildren(item);
				resetSize(idResize);
				item.innerHTML = result;
				resize(idResize);
				this.resizeAdd();
				setScroll(idResize, scroll);
				if ( this.idOverIcon && (this.idOverType == 'tree') )
				{
					this.idOverIcon = false;
					if ( document.getElementById('img_' + tree + '_' + type + '_' + id) )
					{
						this.idOverIcon = document.getElementById('img_' + tree + '_' + type + '_' + id).src;
					}
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
	ContentTree.prototype.startDrag = function ()
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
	ContentTree.prototype.cancelDrag = function ()
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
	ContentTree.prototype.stopDrag = function ()
	{
		var idCaught = this.idCaught;
		var idCaughtType = this.idCaughtType;
		var idCaughtTree = this.idCaughtTree;
		var idOver = this.idOver;
		var idOverType = this.idOverType;
		var idOverTree = this.idOverTree;
		var idOverIcon = this.idOverIcon;
		var idOverAction = this.idOverAction;
		var idOverActionable = this.idOverActionable;
		var idOverLinkable = this.idOverLinkable;
		var dragging = this.dragging;

		var idResize = this.contentContainer;
		var scroll = getScroll(idResize);

		this.cancelDrag();
		if ( dragging )
		{
			if ( idOver && idOverTree && (idOverTree !== idCaughtTree) )
			{
				var urlRegExp = new RegExp('(.+)/(.+)/([0-9]+)$');
				var parms = idCaughtTree + '/move/' + idCaught;
				var toId = '&tid=' + idOver;
				var contentId = this.idContentTree ? '&ct=' + this.idContentTree + '&cid=' + this.idContent : '';
				var uri = parms.replace(urlRegExp, this.uri) + toId + contentId;
				var result = this.request(uri);
				if ( result )
				{
					deleteChildren(document.getElementById(idResize));
					resetSize(idResize);
					document.getElementById(idResize).innerHTML = result;
					resize(idResize);
					this.resizeAdd();
					setScroll(idResize, scroll);
				}
			}
			else if ( (!idOverType || (idOverType !== 'content')) && (idCaughtType == 'content') && this.idContentTree )
			{
				var urlRegExp = new RegExp('(.+)/(.+)/([0-9]+)$');
				var parms = idCaughtTree + '/remove/' + idCaught;
				var contentId = '&ct=' + this.idContentTree + '&cid=' + this.idContent;
				var uri = parms.replace(urlRegExp, this.uri) + contentId;
				var result = this.request(uri);
				if ( result )
				{
					deleteChildren(document.getElementById(idResize));
					resetSize(idResize);
					document.getElementById(idResize).innerHTML = result;
					resize(idResize);
					this.resizeAdd();
					setScroll(idResize, scroll);
				}
			}
		}
		return false;
	}

	/**
	 * @method: openCloseOnOver
	 * @desc: open or close the branch if over
	 * @output: (boolean) true
	**/
	ContentTree.prototype.openCloseOnOver = function ()
	{
		var item = false;
		if ( this.idOver && ((this.idOverAction == 'open') || (this.idOverAction == 'close')) )
		{
			this.openCloseBranch(this.idOver, this.idOverTree, this.idOverType, this.idOverAction);
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
	ContentTree.prototype.releaseItem = function ()
	{
		this.cancelTimers();
		this.setCursor(false);
		this.hideFloatBox();
		this.idCaught = false;
		this.idCaughtType = false;
		this.idCaughtTree = false;
	}

	/**
	 * @method: startTimerOnCatch
	 * @desc: start the catch timer
	 * @output: (void)
	**/
	ContentTree.prototype.startTimerOnCatch = function()
	{
		this.timerOnCatch = window.setTimeout(this.instance + '.startDrag()', this.TIMER_CATCH);
	}

	/**
	 * @method: startTimerOnOver
	 * @desc: start the over timer
	 * @output: (void)
	**/
	ContentTree.prototype.startTimerOnOver = function()
	{
		if ( this.idOverType == 'tree' )
		{
			this.timerOnOver = window.setTimeout(this.instance + '.openCloseOnOver()', this.TIMER_OVER);
		}
	}

	/**
	 * @method: cancelTimers
	 * @desc: cancel all timers
	 * @output: (void)
	**/
	ContentTree.prototype.cancelTimers = function()
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
	ContentTree.prototype.displayFloatBox = function ()
	{
		if ( this.dragging )
		{
			var iconSrc = this.idCaughtTree == 'group' ? this.icons.iContentLTree : this.icons.iContentRTree;

			var iOver = new Image();
			iOver.src = iconSrc;
			iOver.alt = '';
			document.getElementById(this.floatbox).appendChild(iOver);

			var txtNode = 'txt_' + this.idCaughtTree + '_' + this.idCaughtType + '_' + this.idCaught;
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
		}
	}

	/**
	 * @method: moveFloatBox
	 * @desc: fill the floatbox with the caught item and display it
	 * @output: (boolean) false
	**/
	ContentTree.prototype.moveFloatBox = function ()
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
	ContentTree.prototype.hideFloatBox = function ()
	{
		if ( this.dragging )
		{
			document.getElementById(this.floatbox).style.display = 'none';
			deleteChildren(document.getElementById(this.floatbox));
		}
	}

	/**
	 * @method: setCursor
	 * @desc: change the cursor for the idOver item
	 * @input: status: true: special, false, auto
	 * @output: (void)
	**/
	ContentTree.prototype.setCursor = function (status)
	{
		if ( !this.idOver )
		{
			return false;
		}
		if ( status )
		{
			this.startOver();
		}
		else
		{
			this.endOver();
		}
		return true;
	}

	/**
	 * @method: startOver
	 * @desc: set over decorations
	 * @output: (void)
	**/
	ContentTree.prototype.startOver = function ()
	{
		if ( this.idOver && this.idCaught )
		{
			if ( this.idCaughtTree !== this.idOverTree )
			{
				// over content add
				if ( (this.idOverType == 'content') && (this.idOverTree == this.idContentTree) && (this.idOver === this.idContent) )
				{
					// not existing within content
					if ( !document.getElementById('tree_' + this.idCaughtTree + '_content_' + this.idCaught) )
					{
						if ( document.getElementById('tree_content_add') )
						{
							document.getElementById('tree_content_add').style.borderLeft = 'solid 1px black';
						}
						if ( document.getElementById('tree_content_more') )
						{
							document.getElementById('tree_content_more').style.borderTop = 'solid 1px black';
						}
					}
				}

				// over text turned to bold
				if ( document.getElementById('txt_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver) )
				{
					document.getElementById('txt_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).style.fontWeight = 'bold';
				}

				// user caught, over an icon
				if ( (this.idCaughtTree == 'user') )
				{
					// over group tree
					if ( (this.idOverType == 'tree') && document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver) )
					{
						// replace picto
						if ( this.idOverAction == 'open' )
						{
							this.idOverIcon = document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src;
							document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src = this.icons.iOpenOver;
						}
						else if ( this.idOverAction == 'close' )
						{
							this.idOverIcon = document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src;
							document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src = this.icons.iCloseOver;
						}
						else if ( this.idOverAction == 'leaf' )
						{
							this.idOverIcon = document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src;
							document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src = this.icons.iEmptyOver;
						}
					}

					// over group content
					if ( (this.idOverType == 'content') && document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver) )
					{
						this.idOverIcon = document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src;
						document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src = this.icons.iEmptyOver;
					}
				}
			}
		}
	}

	/**
	 * @method: endOver
	 * @desc: cancel over decorations
	 * @output: (void)
	**/
	ContentTree.prototype.endOver = function ()
	{
		if ( this.idOver && this.idCaught )
		{
			if ( this.idCaughtTree !== this.idOverTree )
			{
				// over content add
				if ( (this.idOverType == 'content') && (this.idOverTree == this.idContentTree) && (this.idOver === this.idContent) )
				{
					// not existing within content
					if ( !document.getElementById('tree_' + this.idCaughtTree + '_content_' + this.idCaught) )
					{
						if ( document.getElementById('tree_content_add') )
						{
							document.getElementById('tree_content_add').style.borderLeft = 'none';
						}
						if ( document.getElementById('tree_content_more') )
						{
							document.getElementById('tree_content_more').style.borderTop = 'none';
						}
					}
				}

				// over text turned to bold
				if ( document.getElementById('txt_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver) )
				{
					document.getElementById('txt_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).style.fontWeight = 'normal';
				}

				// user caught, over an icon
				if ( (this.idCaughtTree == 'user') && document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver) )
				{
					// over group tree
					if ( this.idOverType == 'tree' )
					{
						// replace picto
						if ( this.idOverAction == 'open' )
						{
							document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src = this.idOverIcon;
						}
						else if ( this.idOverAction == 'close' )
						{
							document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src = this.idOverIcon;
						}
						else if ( this.idOverAction == 'leaf' )
						{
							document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src = this.idOverIcon;
						}
					}

					// over group content
					if ( this.idOverType == 'content' )
					{
						document.getElementById('img_' + this.idOverTree + '_' + this.idOverType + '_' + this.idOver).src = this.idOverIcon;
					}
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
	ContentTree.prototype.getAction = function (id, tree, type)
	{
		var actionRegExp = new RegExp('tree_(opened|closed|leaf)');
		var matches = false;
		var node = document.getElementById('node_' + tree + '_' + type + '_' + id);
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
	ContentTree.prototype.contentOnOver = function ()
	{
		if ( this.idOver && this.idOverLinkable )
		{
			this.displayContent(this.idOver, this.idOverTree);
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
	ContentTree.prototype.displayContent = function (id, tree)
	{
		var urlRegExp = new RegExp('(.+)/(.+)/([0-9]+)$');
		var parms = tree + '/content/' + id;
		var uri = parms.replace(urlRegExp, this.uri);
		var result = this.request(uri);
		if ( result )
		{
			var idResize = this.contentContainer;
			deleteChildren(document.getElementById(idResize));
			resetSize(idResize);
			document.getElementById(idResize).innerHTML = result;
			resize(idResize);
			this.resizeAdd();
		}
		this.idContent = id;
		this.idContentTree = tree;
		return false;
	}


	/**
	 * @method: request
	 * @desc: ajax call for html
	 * @input: (string) url: url to call
	 * @output: (mixed) false if error, html otherwise
	**/
	ContentTree.prototype.request = function (uri)
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
