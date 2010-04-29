// JavaScript Document
var errorLabels = new Array();
var errors = new Array();
var mandatory = new Array();
var numSteps=12;
var photouploaded=0;
mandatory[2] = [ "correct_answers" ];
mandatory[4] = [ "family_name", "first_name", "date_of_birth", "street","zip_code", "city", "telephone" ];
mandatory[6] = [ "family_name", "first_name", "date_of_birth", "street","zip_code", "city", "telephone" ];

var checkboxes = new Array();
checkboxes[12] = [ "disclaimer", "agree_and_pay" ];

var comboboxes = new Array();
comboboxes[3] = [ "sessions", "morning" ];
comboboxes[4] = [ "country", "nationality", "title" ];

var languages = new Array();
languages[9] = [ "english_reading", "english_speaking", "english_writing",
		"german_reading", "german_speaking", "german_writing",
		"swedish_reading", "swedish_speaking", "swedish_writing" ];
var stepId = 1;

function localsubmit() {
	if (validate()) {
		document.forms['uissform'].submit();
	}
}

function validate() {

	validated = false;
	errors.length = 0;
	resetLabels(document);

	// Check that at least one session is chosen
	checkMandatory();
	checkComboboxes();
	checkCheckboxes();
	checkLanguages();
	if (stepId == 1) {
		checkStudentLevel();
	}
	if (stepId == 2) {
		checkLevel();
	}
	if (stepId == 4) {
		checkEmail();
		checkStateIfUs();
		checkDateOfBirth();
	}
	if (stepId == 5) {
		checkPhoto();
	}
	if (stepId == 6) {
		checkTuitionOnly();
	}
	if (errors.length > 0) {
		showErrors();
	} else {
		validated = true;
	}

	return validated;
}

function showErrors() {

  var errortext = errors.join('<br/>');

  $("<div></div>").html(errortext)
                  .dialog({ title: 'Please correct the following errors',
                            modal: true,
                            resizable: false,
                            dialogClass: 'alert',
                            show: 'blind'
                          });
			   
}

function resetLabels(parent) {
	for ( var i = 0; i < errorLabels.length; i++) {
		label = errorLabels[i];
		pos = label.className.indexOf('foutje');
		if (pos > -1) {
			label.className = label.className.replace(' foutje', '');
		}
	}
	errorLabels.length = 0;
}

function setError(id) {
	label = document.getElementById(id);
	if (label == null) {
		errors.push('Cannot set the label for error on field ' + id);
	} else {
		errorLabels.push(label);
		label.className = label.className + ' foutje';
	}
}

function checkMandatory() {
	if (mandatory[stepId]) {
		for ( var i = 0; i < mandatory[stepId].length; i++) {
			field = document.getElementById(mandatory[stepId][i]);
			if (field != null) {
				if (field.value == "") {
					errors.push("You must enter a value for " + mandatory[stepId][i]);
					setError(mandatory[stepId][i] + '_label');
				}
			} else {
				errors.push("Unknown field" + mandatory[stepId][i]);
				setError(mandatory[stepId][i] + '_label');
			}
		}
	}
}

function checkCheckboxes() {
	if (checkboxes[stepId]) {
		for ( var i = 0; i < checkboxes[stepId].length; i++) {
			field = document.getElementById(checkboxes[stepId][i]);
			if (!field.checked) {
				errors.push("You must check " + checkboxes[stepId][i]);
				setError(checkboxes[stepId][i] + '_label');
			}
		}
	}
}

function checkComboboxes() {
	if (comboboxes[stepId]) {
		for ( var i = 0; i < comboboxes[stepId].length; i++) {
			field = document.getElementById(comboboxes[stepId][i]);
			if (field.value == "choose") {
				errors.push("You must select an item from the list for "
						+ comboboxes[stepId][i]);
				setError(comboboxes[stepId][i] + '_label');
			}
		}
	}
}

function checkPhoto() {
	if (photouploaded == 0) {
  	errors.push("You have to select a photo for uploading");
	} 
}

function checkDateOfBirth() {
	field = document.getElementById('date_of_birth');
	if (field != null) {
		if (field.value != '') {
			if (field.value.indexOf('-')==-1 || field.value.length != 10) {
				errors.push("You must enter a valid date in the format (YYYY-MM-DD)");
				setError('date_of_birth_label');
			} else {
				var date = Date.fromString(field.value);
				//	window.alert(date);
				if (!date) {
					errors.push("You must enter a valid date in the format (YYYY-MM-DD)");
					setError('date_of_birth_label');
				}
			}
		}
	} 
}


function checkLanguages() {
	if (languages[stepId]) {
		for ( var i = 0; i < languages[stepId].length; i++) {
			field = document.getElementById(languages[stepId][i]);
			if (field.value == "choose") {
				errors.push("You must choose a language proficiency for "
						+ languages[stepId][i]);
				langArr = languages[stepId][i].split('_');
				setError(langArr[0] + '_label');
			}
		}
	}
}

function checkLevel() {
	if (getCheckedValue(document.forms['uissform'].elements['level']) == "") {
		errors.push("You have to select a level");
		setError("level_label");
	}
}

function checkStateIfUs() {
	if (document.forms['uissform'].elements['country'].value=='US' && document.forms['uissform'].elements['usstate'].value=='choose') {
		errors.push("You have to select a state if you live in the United States");
		setError("usstate_label");
	}
}


function checkStudentLevel() {
	if (getCheckedValue(document.forms['uissform'].elements['studentlevel']) == "") {
		errors.push("You have to select a student level");
	}
}

function checkTuitionOnly() {
	if (getCheckedValue(document.forms['uissform'].elements['tuition_only']) == "") {
		errors.push("You have to select a whether you need boarding");
	}
}

function checkEmail() {

	at = "@";
	dot = ".";
	str = document.forms['uissform'].elements['e-mail'].value;
	lat = str.indexOf(at);
	lstr = str.length;
	ldot = str.indexOf(dot);
	if ((str.indexOf(at) == -1)
			|| (str.indexOf(at) == -1 || str.indexOf(at) == 0 || str
					.indexOf(at) == lstr)
			|| (str.indexOf(dot) == -1 || str.indexOf(dot) == 0 || str
					.indexOf(dot) == lstr)
			|| (str.indexOf(at, (lat + 1)) != -1)
			|| (str.substring(lat - 1, lat) == dot || str.substring(lat + 1,
					lat + 2) == dot) || (str.indexOf(dot, (lat + 2)) == -1)
			|| (str.indexOf(" ") != -1)) {
		errors.push("The e-mail address is invalid");
		setError('e-mail_label');
	}
}

// return the value of the radio button that is checked
// return an empty string if none are checked, or
// there are no radio buttons
function getCheckedValue(radioObj) {
	if (!radioObj)
		return "";
	radioLength = radioObj.length;
	if (radioLength == undefined)
		if (radioObj.checked)
			return radioObj.value;
		else
			return "";
	for ( var i = 0; i < radioLength; i++) {
		if (radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}

function next() {
	if (validate()) {
		oldStep = stepId;
		
		if (oldStep==1 && getCheckedValue(document.forms['uissform'].elements['studentlevel'])=='beginner') {
		  newStep = 3;
		} else {
      if (oldStep==6 && getCheckedValue(document.forms['uissform'].elements['tuition_only'])=='yes') {
        newStep = 8;
      } else {
        newStep = Math.min(numSteps, stepId + 1);
      }
    }
		if (oldStep != newStep) {
			hideStep(oldStep);
			showStep(newStep);
			stepId = newStep;
		}
	}
}

function back() {
	oldStep = stepId;
	if (oldStep==3 && getCheckedValue(document.forms['uissform'].elements['studentlevel'])=='beginner') {
	  newStep = 1;
	} else {
    if (oldStep==8 && getCheckedValue(document.forms['uissform'].elements['tuition_only'])=='yes') {
      newStep = 6;
    } else {
      newStep = Math.max(1, stepId - 1);
    }
  }
	if (oldStep != newStep) {
		hideStep(oldStep);
		showStep(newStep);
		stepId = newStep;
	}
}

function navigation(stepId) {
	if (stepId == 1) {
    $("#back").hide();
	} else {
    $("#back").show();
	}
	if (stepId == numSteps) {
		$("#next").hide();
		$("#submit").show();
	} else {
		$("#next").show();
		$("#submit").hide();
	}
}

function showStep(stepId) {

	navigation(stepId);
  $('#step' + stepId).show('blind');

}


function hideStep(stepId) {
	navigation(stepId);
  $('#step'+stepId).hide('blind');
}


$(function() {

  $(".date-pick").datepicker({
      dateFormat: 'yy-mm-dd',
      firstDay: 1,
      showOn: 'button',
      showAnim: 'blind',
			buttonImage: '../images/calendar.png',
			buttonImageOnly: true,
			minDate: '1900-01-01',
      changeYear: true,
			endDate: (new Date()).asString()
	});
  
  $(".fieldset").addClass("ui-widget-content");
  $(".fieldset").addClass("ui-corner-all");
  $(".fieldset").addClass("positionable");
  $(".fieldset").hide();

  $(".legend").addClass("ui-widget-header");
  $(".legend").addClass("ui-corner-all");

  new AjaxUpload('photoarea', 
				 				 {action: 'ajaxupload.php', 
									autoSubmit: true,
									onSubmit : function(file , ext){
										if (! (ext && /^(jpg|png|jpeg|gif)$/.test(ext))){
											// extension is not allowed
											alert('Error: invalid file extension');
											// cancel upload
											return false;
										}
										this.setData({'family_name': document.forms['uissform'].elements['family_name'].value,
																	'first_name': document.forms['uissform'].elements['first_name'].value,
																	'date_of_birth': document.forms['uissform'].elements['date_of_birth'].value});
									},
									onComplete: function(file,response){
										var container = document.getElementById("photoarea");
										// remove all children of photoarea
										var olddiv = document.getElementById("uploadresult");
										if (olddiv) {
										  container.removeChild(olddiv);
										}
										var newdiv = document.createElement("div");
										newdiv.setAttribute("id","uploadresult");
										newdiv.innerHTML = response;
										container.appendChild(newdiv);
										photouploaded=1;
									},
					});

  $("#next").click(function() { return next(); });

  $("#back").click(function() { return back(); });
  
  $("#submit").click(function() { return localsubmit(); });  

  $("#next").button({label: 'next'});
  $("#back").button({label: 'back'});
  $("#submit").button({label: 'submit'});

  showStep(1);
});