<?php

/*
* Check if required fields are filled
* */
function da_core_check_required_fields($required_array)
{
	$error_fields = array();
	foreach ($required_array as $fieldname) {
		if (!isset($_POST[$fieldname]) || (empty($_POST[$fieldname]))) {
			$error_fields[] = $fieldname;
		}
	}
	return $error_fields;
}


function da_core_prepare($data)
{
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

/*
 * Make sure content in fields is not too long
 * */
function da_core_check_field_length($fieldsMaxLengths)
{
	$error_fields = array();
	foreach ($fieldsMaxLengths as $fieldname => $maxlength) {
		if (strlen(da_core_prepare($_POST[$fieldname])) > $maxlength) {
			$error_fields[] = $fieldname;
		}
	}
	return $error_fields;
}
