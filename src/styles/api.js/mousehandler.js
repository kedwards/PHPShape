/**
 * @file: sys.mousehandler.js
 * @desc: mouse handler
 *
**/

/**
 * @class: MouseHandler
 * @desc: handle mouse events
 *
**/
function MouseHandler()
{
	return this;
}
	/**
	 * @method: set
	 * @desc: init the object
	 * @output: (void)
	**/
	MouseHandler.prototype.set = function()
	{
		// true if mouse button pressed
		this.status = false;

		// external listeners
		this.listener = {
			onclick: false,
			onmousedown: false,
			onmouseup: false,
			onmousemove: false,
			onmouseover: false,
			onmouseout: false
		};

		// local listeners
		document.onclick = function (e){return mouseHandler.onClick(e);}
		document.onmousedown = function (e){return mouseHandler.onMouseDown(e);}
		document.onmouseup = function (e){return mouseHandler.onMouseUp(e);}
		document.onmousemove = function (e){return mouseHandler.onMouseMove(e);}
		document.onmouseover = function (e){return mouseHandler.onMouseOver(e);}
		document.onmouseout = function (e){return mouseHandler.onMouseOut(e);}
	}

	/**
	 * @method: onMouseDown
	 * @desc: catch button pressed
	 * @input: (object) e: mouse event
	 * @output: (boolean) false if handled, true otherwise
	**/
	MouseHandler.prototype.onMouseDown = function(e)
	{
		if ( !e ){var e = window.event;}
		this.status = true;
		return !this.listener.onmousedown || this.listener.onmousedown(this.getEventData(e)) ? true : this.removeEvent(e);
	}

	/**
	 * @method: onMouseUp
	 * @desc: catch button release
	 * @input: (object) e: mouse event
	 * @output: (boolean) false if handled, true otherwise
	**/
	MouseHandler.prototype.onMouseUp = function(e)
	{
		if ( !e ){var e = window.event;}
		this.status = false;
		return !this.listener.onmouseup || this.listener.onmouseup(this.getEventData(e)) ? true : this.removeEvent(e);
	}

	/**
	 * @method: onClick
	 * @desc: catch the onclick event
	 * @input: (object) e: mouse event
	 * @output: (boolean) false if handled, true otherwise
	 *
	**/
	MouseHandler.prototype.onClick = function(e)
	{
		if ( !e ){var e = window.event;}
		this.status = false;
		return !this.listener.onclick || this.listener.onclick(this.getEventData(e)) ? true : this.removeEvent(e);
	}

	/**
	 * @method: onMouseMove
	 * @desc: catch mouse moves
	 * @input: (object) e: mouse event
	 * @output: (boolean) false if handled, true otherwise
	**/
	MouseHandler.prototype.onMouseMove = function(e)
	{
		if ( !e ){var e = window.event;}
		return !this.status || !this.listener.onmousemove || this.listener.onmousemove(this.getEventData(e)) ? true : this.removeEvent(e);
	}

	/**
	 * @method: onMouseOver
	 * @desc: catch mouse over
	 * @input: (object) e: mouse event
	 * @output: (boolean) false if handled, true otherwise
	**/
	MouseHandler.prototype.onMouseOver = function(e)
	{
		if ( !e ){var e = window.event;}
		return !this.status || !this.listener.onmouseover || this.listener.onmouseover(this.getEventData(e)) ? true : this.removeEvent(e);
	}

	/**
	 * @method: onMouseOut
	 * @desc: catch mouse out
	 * @input: (object) e: mouse event
	 * @output: (boolean) false if handled, true otherwise
	**/
	MouseHandler.prototype.onMouseOut = function(e)
	{
		if ( !e ){var e = window.event;}
		return !this.status || !this.listener.onmouseout || this.listener.onmouseout(this.getEventData(e)) ? true : this.removeEvent(e);
	}

	/**
	 * @method: removeEvent
	 * @desc: kill the event
	 * @input: (object) mouse event
	 * @output: (boolean) false
	 *
	**/
	MouseHandler.prototype.removeEvent = function(e)
	{
		// DOM
		if ( e.stopPropagation )
		{
			e.stopPropagation();
		}
		if ( e.preventDefault )
		{
			e.preventDefault();
		}
		// IE
		e.cancelBubble = true;
		e.returnValue = false;
		return false;
	}

	/**
	 * @method: getEventData
	 * @desc: get the target and mouse pos for this event
	 * @input: event
	 * @output: (object): {target, pos, status}
	 *	o (object) target,
	 *	o (object) pos {(integer) x, (integer) y}: mouse coordonates relative to body
	 *  o (boolean) status: true if button pressed, false otherwise
	**/
	MouseHandler.prototype.getEventData = function(e)
	{
		// target
		var mouseTarget = false;
		if ( e.target )
		{
			mouseTarget = e.target;
		}
		else if ( e.srcElement )
		{
			mouseTarget = e.srcElement;
		}
		// and this one for the Apple webkit bug
		if ( mouseTarget && (mouseTarget.nodeType == 3) )
		{
			mouseTarget = mouseTarget.parentNode;
		}

		// position
		var mousePos = e.pageX || e.pageY ? {x: e.pageX, y: e.pageY} : {x: e.clientX + document.body.scrollLeft - document.body.clientLeft, y: e.clientY + document.body.scrollTop  - document.body.clientTop};

		// result
		return {
			target: mouseTarget,
			pos: mousePos,
			status: this.status
		}
	}
