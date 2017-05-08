var lastExpandedGroupName;
var lastSelectAll = false;
var lastSelectAllState = [];
 
function toggleReportShowHide(className, labelName) {
	var list = document.getElementsByClassName(className);
	if (list.length>0) {
		for (var i = 0; i < list.length; i++) {
			if (reportFiltersState[className]) {
				list[i].style.display="table-row";	
			}
			else {
				list[i].style.display="none";
			}
		}
	}
	reportFiltersState[className]=!reportFiltersState[className];	//toggle
	if (reportFiltersState[className])
		document.getElementById(labelName).innerHTML="+Show";
	else
		document.getElementById(labelName).innerHTML="-Hide";
}

function toggleRealRelativeDate(dateTypeElement, spanTagReal, spanTagRelative) {
	if (dateTypeElement.value == "real") {
		document.getElementById(spanTagReal).style.display = "";
		document.getElementById(spanTagRelative).style.display = "none";
	}
	else {
		document.getElementById(spanTagReal).style.display = "none";
		document.getElementById(spanTagRelative).style.display = "";
	}
}

function toggleCollapsibleSection(group) {
	var rows;
	var hide;
	var spanPlusMinus = group + "_plus";
	var rowName = group + "_row";

	rows = document.getElementsByClassName(rowName);
	if (!rows[0].className.match(/(?:^|\s)hideRow(?!\S)/) ) {
		hide = true;
		lastExpandedGroupName = undefined;
		document.getElementById(spanPlusMinus).innerHTML = "+";
	}
	else {
		hide = false;
		if (typeof lastExpandedGroupName != 'undefined' && lastExpandedGroupName != group) toggleCollapsibleSection(lastExpandedGroupName);
		lastExpandedGroupName = group;
		document.getElementById(spanPlusMinus).innerHTML = "-";		
	}
	for(var i=0; i<rows.length; i++) {
		if (hide) 		
			rows[i].className += " hideRow ";
		else
			rows[i].className = rows[i].className.replace("hideRow" , "");
	}
}

function confirmDeleteReport(id, name, action, adminView, recurs, url) {
	var message;
	message = action + " the report "+name+"?";
	if (recurs==1) message = message + "\r\nAll future recurrences will also be canceled.";
	if (confirm(message)) {
		url = url + "c/" + id + "/";
		if (adminView) url = url + "a/";
		window.location.href = url; 
		return true;
	} 
	return false;
}

function showSelectChoices(list, span) {
	var html='';
	for (var i=0; i<list.length; i++) {
		if (list[i].selected) html = html + list[i].label.trim() + "&nbsp;&nbsp;<br>";
	}
	document.getElementById(span).innerHTML=html;
}

function selectAllMultiselect(listName) {
	var list = document.getElementById(listName);
	for(var i=0; i<list.options.length; i++) {
		list.options[i].selected = true; 
	}
	showSelectChoices(list, listName+"_selected");
}

function toggleAllCheckboxes(className) {
	
	if (className == "executiveReportCheckbox") {
		toggleState = !lastSelectAll;
		lastSelectAll = !lastSelectAll;
		
		for (var key in lastSelectAllState) {
			lastSelectAllState[key] = !lastSelectAllState[key]; 
		}
	}
	else {
		lastSelectAllState[className] = !lastSelectAllState[className];
		toggleState = lastSelectAllState[className];
	}
	
	var list = document.getElementsByClassName(className);
	for (var i=0; i<list.length; i++) {
		list[i].checked = toggleState;
	}
	
	var newText = (toggleState) ? 'Clear All' : 'Select All';
	if (className == "executiveReportCheckbox") document.getElementById("selectAllReportsLink").text = newText;
	
	for (var key in lastSelectAllState) {
		if (className == "executiveReportCheckbox" || className == key) {
			document.getElementById(key+"SelectAllReportsLink").text = newText;
		}
	}
}

function submitExecutiveReport() {
	var numChecked = 0;
	var reportList = document.getElementsByClassName("executiveReportCheckbox");
	for (var i=0; i<reportList.length; i++) {
		if (reportList[i].checked) {
			numChecked++;
			break;
		}
	}
	if (0 == numChecked) {
		alert("Please select one or more reports.");
		return false;
	}
	return true;
}

function setExecutiveReportDate(offset) {
	var today=new Date();
	var thisYear=today.getFullYear();
	var thisMonth=today.getMonth();
	var reportMonth;
	var reportYear;
	var reportYearIndex;
	
	if (0 == offset) {
		if (0 == thisMonth) {
			reportMonth = 11;
			reportYear = thisYear - 1;
		}
		else {
			reportMonth = thisMonth - 1;
			reportYearIndex = 0;
		}
	}
	else {
		reportMonth = document.getElementById("reportMonth").selectedIndex + offset;
		reportYearIndex = document.getElementById("reportYear").selectedIndex;
		if (reportMonth < 0 && reportYearIndex == document.getElementById("reportYear").length-1) return;
		if (reportMonth > thisMonth-1 && reportYearIndex == 0) return;
		if (reportMonth > 11) {
			reportMonth = 0;
			reportYearIndex -= 1;
		}
		else if (reportMonth < 0) {
			reportMonth = 11;
			reportYearIndex += 1;
		}		
	}
	document.getElementById("reportMonth").selectedIndex = reportMonth;
	document.getElementById("reportYear").selectedIndex = reportYearIndex;		
}

function submitReport(action) {

	var userInputs;
	var dateTypeName;
	var colName;
	
	if (document.getElementById("name").value.length==0) {
		alert("Please enter a report name.");
		return false;
	}
	
	if (action == 'save') {	
		return true;
	}

	userInputs = document.getElementsByClassName('userInput');
	for (var i=0; i<userInputs.length; i++) {
		if (userInputs[i].classList.contains('userText') && userInputs[i].value=="") {
			if (userInputs[i].name.indexOf("_") != -1)
				colName = columnNames[userInputs[i].name.substring(0,userInputs[i].name.indexOf("_"))];
			else
				colName = columnNames[userInputs[i].name];
			alert("Please enter a value for " + colName);
			userInputs[i].focus();
			return false;
		}
		else if (userInputs[i].classList.contains('userInteger') && (userInputs[i].value=="" || !validateInteger(userInputs[i].value))) {
			if (userInputs[i].name.indexOf("_") != -1)
				colName = columnNames[userInputs[i].name.substring(0,userInputs[i].name.indexOf("_"))];
			else
				colName = columnNames[userInputs[i].name];
			alert("Please enter an integer for " + colName);
			userInputs[i].focus();
			return false;
		}
		else if (userInputs[i].classList.contains('userDate')) {
			dateTypeName = userInputs[i].name.replace("_date","_type");
			if (document.getElementById(dateTypeName).value == 'real' && !validDate(userInputs[i].value) ) {
				alert("You must specify a valid date for " + columnNames[userInputs[i].name.substring(0,userInputs[i].name.indexOf("_"))]);
				userInputs[i].focus();
				return false;
			}
		}
		else if (userInputs[i].classList.contains('userSelect') && userInputs[i].selectedIndex==-1) {
			alert("Please select a value for " + columnNames[userInputs[i].name.replace("[]","")]);
			userInputs[i].focus();
			return false;			
		}
	}
	
	if (!document.getElementById("intervalRadioOnce").checked && !document.getElementById("intervalRadioRecur").checked) {
	    alert("Please select the recurrence interval.");
	    return false;
	}
	if (document.getElementById("intervalRadioRecur").checked && (document.getElementById("interval").selectedIndex==0 || document.getElementById("intervalPeriod").selectedIndex==0)) {
		alert("Please specify the recurrence frequency.");
		return false;
	}
	
	if (!document.getElementById("runTimeRadioASAP").checked && !document.getElementById("runTimeRadioScheduled").checked) {
	    alert("Please select the run time.");
	    return false;
	}
	if (document.getElementById("runTimeRadioScheduled").checked) {
		if (document.getElementById("runDate").value=="") {
			alert("Please specify the scheduled run date.");
			return false;
		}
		if (!validDate(runDate.value)) {
			alert("Please specify a valid scheduled run date.");
			return false;			
		}
	}
	
	if (!document.getElementById("excelOutput").checked && !document.getElementById("csvOutput").checked && !document.getElementById("htmlOutput").checked) {
		alert("Please choose an output format.");
		return false;
	}
	
	return true;
}

function setDaysLabel(elem, spanTag){
	if (elem.value == -1)
		document.getElementById(spanTag).innerHTML = "Day";
	else
		document.getElementById(spanTag).innerHTML = "Days";
}

function setMonthsLabel(elem, spanTag){
	if (elem.value == -1)
		document.getElementById(spanTag).innerHTML = "Month";
	else
		document.getElementById(spanTag).innerHTML = "Months";
}

function setYearsLabel(elem, spanTag){
	if (elem.value == -1)
		document.getElementById(spanTag).innerHTML = "Year";
	else
		document.getElementById(spanTag).innerHTML = "Years";
}

function validateInteger(n) {
	if (isNaN(parseFloat(n)) || !isFinite(n)) return false;
	return true;
}

function emptyString(s) {
	return (s=="");
}

function validDate(date) {
	var matches = /^(\d{2})[-\/](\d{2})[-\/](\d{4})$/.exec(date);
    if (matches == null) return false;
    var d = matches[2];
    var m = matches[1];
    var y = matches[3];
    if (!validateInteger(y) || !validateInteger(m) || !validateInteger(d)) return false;
    if (m < 1 || m > 12) return false;
    if (d < 1 || d > 31) return false;
    if ((m == 4 || m == 6 || m == 9 || m == 11) && d > 30) return false;
	if (m ==2) {
		if ((y % 400) == 0 || ((y % 4) == 0 && (y % 100) != 0)) { 	//leap year
			if (d > 29) return false;
		}	
		else {	//not leap year
			if (d > 28) return false;
		}
	}
    return true;
}

var win1=null;
function openWindow(html) {
	if (win1 != null && win1.open) win1.close();
	win1=window.open('','win1','height=250,width=800,toolbar=no,location=no,menubar=no,titlebar=no,status=no,scrollbars=yes,resizable=yes,modal=yes');
	win1.document.write("<pre>"+html+"</pre>");
}
