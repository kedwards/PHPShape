/**
 * @file: admin.menu.js
 * @desc: monitor tab change in admin
 *
**/

function adminmenu(uri)
{
	var id = false;
	var type = false;
	if ( document.getElementById('contentid') )
	{
		id = parseInt(document.getElementById('contentid').value);
	}
	if ( !id )
	{
		return true;
	}
	if ( document.getElementById('contenttype') )
	{
		type = document.getElementById('contenttype').value;
	}
	var alphaRegExp = new RegExp('^[A-Za-z]{1,2}$');
	if ( !type || !type.match(alphaRegExp) )
	{
		return true;
	}
	window.location.href = uri + '&jct=' + type + '&jcid=' + id;
	return false;
}
