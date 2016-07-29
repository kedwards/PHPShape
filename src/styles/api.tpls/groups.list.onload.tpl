
mouseHandler.listener.onclick = function (event){return dd.onClick(event);}
mouseHandler.listener.onmousedown = function (event){return dd.onMouseDown(event);}
mouseHandler.listener.onmouseup = function (event){return dd.onMouseUp(event);}
mouseHandler.listener.onmousemove = function (event){return dd.onMouseMove(event);}
mouseHandler.listener.onmouseover = function (event){return dd.onMouseOver(event);}
mouseHandler.listener.onmouseout = function (event){return dd.onMouseOut(event);}

resizeBoxes.resize();
dd.resizeAdd();
