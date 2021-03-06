<?php
// /assets/edit/host.php
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

$page_title = "Editing A Web Host";
$software_section = "hosting-edit";

$del = $_GET['del'];
$really_del = $_GET['really_del'];

$whid = $_GET['whid'];
$new_host = $_REQUEST['new_host'];
$new_url = $_POST['new_url'];
$new_notes = $_REQUEST['new_notes'];
$new_whid = $_REQUEST['new_whid'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if ($new_host != "" && $new_url != "") {

		$sql = "UPDATE hosting
				SET name = '" . mysql_real_escape_string($new_host) . "', 
					url = '" . mysql_real_escape_string($new_url) . "',
					notes = '" . mysql_real_escape_string($new_notes) . "',
					update_time = '" . $current_timestamp . "'
				WHERE id = '" . $new_whid . "'";
		$result = mysql_query($sql,$connection) or die(mysql_error());
		
		$new_host = $new_host;
		$new_notes = $new_notes;

		$whid = $new_whid;
		
		$_SESSION['result_message'] = "Web Host <font class=\"highlight\">$new_host</font> Updated<BR>";

		header("Location: ../hosting.php");
		exit;

	} else {

		if ($new_host == "") $_SESSION['result_message'] .= "Please enter the web host's name<BR>";
		if ($new_url == "") $_SESSION['result_message'] .= "Please enter the web host's URL<BR>";

	}

} else {

	$sql = "SELECT name, url, notes
			FROM hosting
			WHERE id = '" . $whid . "'";
	$result = mysql_query($sql,$connection);
	
	while ($row = mysql_fetch_object($result)) { 
	
		$new_host = $row->name;
		$new_url = $row->url;
		$new_notes = $row->notes;

	}

}
if ($del == "1") {

	$sql = "SELECT hosting_id
			FROM domains
			WHERE hosting_id = '" . $whid . "'";
	$result = mysql_query($sql,$connection);
	
	while ($row = mysql_fetch_object($result)) {
		$existing_domains = 1;
	}
	
	if ($existing_domains > 0) {

		$_SESSION['result_message'] = "This Web Host has domains associated with it and cannot be deleted<BR>";

	} else {

		$_SESSION['result_message'] = "Are you sure you want to delete this Web Host?<BR><BR><a href=\"$PHP_SELF?whid=$whid&really_del=1\">YES, REALLY DELETE THIS WEB HOST</a><BR>";

	}

}

if ($really_del == "1") {

	$sql = "DELETE FROM hosting 
			WHERE id = '" . $whid . "'";
	$result = mysql_query($sql,$connection);
	
	$_SESSION['result_message'] = "Web Host <font class=\"highlight\">$new_host</font> Deleted<BR>";
	
	header("Location: ../hosting.php");
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
<form name="edit_host_form" method="post" action="<?=$PHP_SELF?>">
<strong>Web Host Name (100)</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<input name="new_host" type="text" value="<?php if ($new_host != "") echo $new_host; ?>
" size="50" maxlength="100">
<BR><BR>
<strong>Registrar's URL (100)</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<input name="new_url" type="text" value="<?=$new_url?>" size="50" maxlength="100">
<BR><BR>
<strong>Notes</strong><BR><BR>
<textarea name="new_notes" cols="60" rows="5"><?=$new_notes?></textarea>
<input type="hidden" name="new_whid" value="<?=$whid?>">
<BR><BR>
<input type="submit" name="button" value="Update This Web Host &raquo;">
</form>
<BR><BR><a href="<?=$PHP_SELF?>?whid=<?=$whid?>&del=1">DELETE THIS WEB HOST</a>
<?php include("../../_includes/layout/footer.inc.php"); ?>
</body>
</html>
