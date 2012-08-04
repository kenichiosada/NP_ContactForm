/*
 * contactform.js
 *
 * Calling this script within NP_ContactForm adds show/hide functionality to a form. 
 * Form elements to be shown or hidden are determined by a value of dropdown menu.
 *
*/

// find value of selected option of dropdown menu at page load
var rows = document.getElementsByTagName("tr");
var list = rows[1].getElementsByTagName("select");
var selection = list[0].options[list[0].selectedIndex].value;

// hide some form elements according to selection option value
document.body.onload = function(){
	hideElement();
};

list[0].onchange = function(){
	selection = list[0].options[list[0].selectedIndex].value;
	hideElement();
};

function hideElement() {
	if (selection == 0) { 
		rows[6].style.display = "none";
		rows[7].style.display = "none";
		rows[8].style.display = "none";
	} else if (selection == 2) {
		rows[6].style.display = "none";
		rows[7].style.display = "";
		rows[8].style.display = "";
	}else{
		rows[6].style.display = "";
		rows[7].style.display = "";
		rows[8].style.display = "";
	}
}
