// JavaScript Document
//Funksjoner

//Read Cookie
function getCookieData(label) {
	var labelLen = label.length;
	var cLen = document.cookie.length;
	var i = 0;
	var cEnd
	while (i < cLen) {
		var j = i + labelLen;
		if (document.cookie.substring(i,j) == label) {
			cEnd = document.cookie.indexOf(";",j);
			if (cEnd==-1) {
				cEnd = document.cookie.length;
			}
			return unescape (document.cookie.substring (j+1,cEnd));
		}
		i++;
	}
	return "";
}

function displayRadioValue(formName, radioName)
{
	if (typeof(document[formName][radioName].value) == "undefined"){
		var i;
		var value = " ";
		for (i=0; i < document.forms[formName].elements[radioName].length;
	i++)
		{
			if (document.forms[formName].elements[radioName][i].checked)
			{
				value = document.forms[formName].elements[radioName]
	[i].value;
			}
		}
	}else{
		value=document[formName][radioName].value;
	}

	return (value);
}