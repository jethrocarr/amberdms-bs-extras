<?php
//
// include/errors.php
//
// contains a mixture of error handling functions, data transport functions, and notification services.
//
//
// FUNCTIONS:
// error_render_message()
//	displays the error and/or notification message in the uniform way
//
// error_render_input(value)
//	if the value is "error", it will return a colour statment.
//	if the value is something else, it will return a value statment
//
// error_render_checkbox(value)
//	same as the above, but suited for a checkbox.
//
// error_render_table(value)
//	renders a table cell to the error colour, if the value is of an error. Used to highlight objects such as check boxes.
//



function error_render_message()
{
	if ($_SESSION["error"]["message"])
	{
		print "<tr><td width=\"760\" bgcolor=\"#ffeda4\" style=\"border: 1px dashed #dc6d00; padding: 3px;\">";
		print "<p><b>Error:</b><br><br>" . $_SESSION["error"]["message"] . "</p>";
		print "</td></tr>";
	}
	elseif ($_SESSION["notification"]["message"])
	{
		print "<tr><td width=\"760\" bgcolor=\"#c7e8ed\" style=\"border: 1px dashed #374893; padding: 3px;\">";
		print "<p><b>Notification:</b><br><br>" . $_SESSION["notification"]["message"] . "</p>";
		print "</td></tr>";
	}

	return 1;
}

function error_render_table($value)
{
	// check if error reporting is occuring
	if ($_SESSION["error"]["$value-error"])
	{
		return " bgcolor=\"#ffeda4\"";
	}

}

