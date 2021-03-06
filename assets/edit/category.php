<?php
// /assets/edit/category.php
// 
// DomainMOD is an open source application written in PHP & MySQL used to track and manage your web resources.
// Copyright (C) 2010 Greg Chetcuti
// 
// DomainMOD is free software; you can redistribute it and/or modify it under the terms of the GNU General
// Public License as published by the Free Software Foundation; either version 2 of the License, or (at your
// option) any later version.
// 
// DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
// implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
// for more details.
// 
// You should have received a copy of the GNU General Public License along with DomainMOD. If not, please see
// http://www.gnu.org/licenses/
?>
<?php
include("../../_includes/start-session.inc.php");
include("../../_includes/config.inc.php");
include("../../_includes/database.inc.php");
include("../../_includes/software.inc.php");
include("../../_includes/auth/auth-check.inc.php");
include("../../_includes/timestamps/current-timestamp.inc.php");

$page_title = "Editing A Category";
$software_section = "categories-edit";

// 'Delete Category' Confirmation Variables
$del = $_GET['del'];
$really_del = $_GET['really_del'];

$pcid = $_GET['pcid'];

// Form Variables
$new_category = $_REQUEST['new_category'];
$new_stakeholder = $_REQUEST['new_stakeholder'];
$new_notes = $_REQUEST['new_notes'];
$new_pcid = $_REQUEST['new_pcid'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if ($new_category != "") {

		$sql = "UPDATE categories
				SET name = '" . mysql_real_escape_string($new_category) . "', 
					stakeholder = '" . mysql_real_escape_string($new_stakeholder) . "',
					notes = '" . mysql_real_escape_string($new_notes) . "',
					update_time = '" . $current_timestamp . "'
				WHERE id = '" . $new_pcid . "'";
		$result = mysql_query($sql,$connection) or die(mysql_error());
		
		$new_category = $new_category;
		$new_stakeholder = $new_stakeholder;
		$new_notes = $new_notes;

		$pcid = $new_pcid;
		
		$_SESSION['result_message'] = "Category <font class=\"highlight\">$new_category</font> Updated<BR>";

		header("Location: ../categories.php");
		exit;

	} else {
	
		$_SESSION['result_message'] = "Please enter the category name<BR>";

	}

} else {

	$sql = "SELECT name, stakeholder, notes
			FROM categories
			WHERE id = '" . $pcid . "'";
	$result = mysql_query($sql,$connection);
	
	while ($row = mysql_fetch_object($result)) { 
	
		$new_category = $row->name;
		$new_stakeholder = $row->stakeholder;
		$new_notes = $row->notes;
	}

}
if ($del == "1") {

	$sql = "SELECT cat_id
			FROM domains
			WHERE cat_id = '" . $pcid . "'";
	$result = mysql_query($sql,$connection);
	
	while ($row = mysql_fetch_object($result)) {
		$existing_domains = 1;
	}
	
	if ($existing_domains > 0) {

		$_SESSION['result_message'] = "This Category has domains associated with it and cannot be deleted<BR>";

	} else {

		$_SESSION['result_message'] = "Are you sure you want to delete this Category?<BR><BR><a href=\"$PHP_SELF?pcid=$pcid&really_del=1\">YES, REALLY DELETE THIS CATEGORY</a><BR>";

	}

}

if ($really_del == "1") {

	$sql = "DELETE FROM categories 
			WHERE id = '" . $pcid . "'";
	$result = mysql_query($sql,$connection);
	
	$_SESSION['result_message'] = "Category <font class=\"highlight\">$new_category</font> Deleted<BR>";
	
	header("Location: ../categories.php");
	exit;

}
?>
<?php include("../../_includes/doctype.inc.php"); ?>
<html>
<head>
<title><?=$software_title?> :: <?=$page_title?></title>
<?php include("../../_includes/layout/head-tags.inc.php"); ?>
</head>
<body>
<?php include("../../_includes/layout/header.inc.php"); ?>
<form name="edit_category_form" method="post" action="<?=$PHP_SELF?>">
<strong>Category Name (150)</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<input name="new_category" type="text" value="<?php if ($new_category != "") echo $new_category; ?>
" size="50" maxlength="150">
<BR><BR>
<strong>Stakeholder (100)</strong><BR><BR>
<input name="new_stakeholder" type="text" value="<?php if ($new_stakeholder != "") echo $new_stakeholder; ?>
" size="50" maxlength="100">
<BR><BR>
<strong>Notes</strong><BR><BR>
<textarea name="new_notes" cols="60" rows="5"><?=$new_notes?></textarea>
<BR><BR>
<input type="hidden" name="new_pcid" value="<?=$pcid?>">
<input type="submit" name="button" value="Update This Category &raquo;">
</form>
<BR><BR><a href="<?=$PHP_SELF?>?pcid=<?=$pcid?>&del=1">DELETE THIS CATEGORY</a>
<?php include("../../_includes/layout/footer.inc.php"); ?>
</body>
</html>
