<?php
// /assets/registrars.php
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
include("../_includes/start-session.inc.php");
include("../_includes/config.inc.php");
include("../_includes/database.inc.php");
include("../_includes/software.inc.php");
include("../_includes/auth/auth-check.inc.php");
include("../_includes/timestamps/current-timestamp.inc.php");

$page_title = "Domain Registrars";
$software_section = "registrars";

$export = $_GET['export'];

$sql = "SELECT r.id AS rid, r.name AS rname, r.url, r.notes, r.insert_time, r.update_time
		FROM registrars AS r, domains AS d
		WHERE r.id = d.registrar_id
		  AND d.domain NOT IN ('0', '10')
		  AND (SELECT count(*) 
		  	   FROM domains 
			   WHERE registrar_id = r.id 
			     AND active NOT IN ('0','10')) 
				 > 0
		GROUP BY r.name
		ORDER BY r.name asc";

if ($export == "1") {

	$result = mysql_query($sql,$connection) or die(mysql_error());

	$current_timestamp_unix = strtotime($current_timestamp);
	$export_filename = "registrar_list_" . $current_timestamp_unix . ".csv";
	include("../_includes/system/export/header.inc.php");

	$row_content[$count++] = $page_title;
	include("../_includes/system/export/write-row.inc.php");

	fputcsv($file_content, $blank_line);

	$row_content[$count++] = "Status";
	$row_content[$count++] = "Registrar";
	$row_content[$count++] = "Accounts";
	$row_content[$count++] = "Domains";
	$row_content[$count++] = "Default Registrar?";
	$row_content[$count++] = "URL";
	$row_content[$count++] = "Notes";
	$row_content[$count++] = "Inserted";
	$row_content[$count++] = "Updated";
	include("../_includes/system/export/write-row.inc.php");

	if (mysql_num_rows($result) > 0) {
	
		$has_active = "1";

		while ($row = mysql_fetch_object($result)) {
	
			$new_rid = $row->rid;
		
			if ($current_rid != $new_rid) {
				$exclude_registrar_string_raw .= "'" . $row->rid . "', ";
			}
	
			$sql_total_count = "SELECT count(*) AS total_count
								FROM registrar_accounts
								WHERE registrar_id = '" . $row->rid . "'";
			$result_total_count = mysql_query($sql_total_count,$connection);
	
			while ($row_total_count = mysql_fetch_object($result_total_count)) { 
				$total_accounts = $row_total_count->total_count;
			}
	
			$sql_domain_count = "SELECT count(*) AS total_count
								 FROM domains
								 WHERE active NOT IN ('0', '10')
								   AND registrar_id = '" . $row->rid . "'";
			$result_domain_count = mysql_query($sql_domain_count,$connection);
		
			while ($row_domain_count = mysql_fetch_object($result_domain_count)) { 
				$total_domains = $row_domain_count->total_count;
			}
			
			if ($row->rid == $_SESSION['default_registrar']) {
			
				$is_default = "1";
				
			} else {
			
				$is_default = "";
			
			}

			$row_content[$count++] = "Active";
			$row_content[$count++] = $row->rname;
			$row_content[$count++] = number_format($total_accounts);
			$row_content[$count++] = number_format($total_domains);
			$row_content[$count++] = $is_default;
			$row_content[$count++] = $row->url;
			$row_content[$count++] = $row->notes;
			$row_content[$count++] = $row->insert_time;
			$row_content[$count++] = $row->update_time;
			include("../_includes/system/export/write-row.inc.php");
	
			$current_rid = $row->rid;
	
		}
	
	}

	$exclude_registrar_string = substr($exclude_registrar_string_raw, 0, -2); 
	
	if ($exclude_registrar_string == "") {
	
		$sql = "SELECT r.id AS rid, r.name AS rname, r.url, r.notes, r.insert_time, r.update_time
				FROM registrars AS r
				GROUP BY r.name
				ORDER BY r.name asc";
	
	} else {
		
		$sql = "SELECT r.id AS rid, r.name AS rname, r.url, r.notes, r.insert_time, r.update_time
				FROM registrars AS r
				WHERE r.id
				  AND r.id NOT IN (" . $exclude_registrar_string . ")
				GROUP BY r.name
				ORDER BY r.name asc";
	
	}
	
	$result = mysql_query($sql,$connection) or die(mysql_error());
	
	if (mysql_num_rows($result) > 0) { 

		$has_inactive = "1";

		while ($row = mysql_fetch_object($result)) {
	
			$sql_total_count = "SELECT count(*) AS total_count
								FROM registrar_accounts
								WHERE registrar_id = '" . $row->rid . "'";
			$result_total_count = mysql_query($sql_total_count,$connection);
	
			while ($row_total_count = mysql_fetch_object($result_total_count)) { 
				$total_accounts = $row_total_count->total_count;
			}

			if ($row->rid == $_SESSION['default_registrar']) {
			
				$is_default = "1";
				
			} else {
			
				$is_default = "";
			
			}

			$row_content[$count++] = "Inactive";
			$row_content[$count++] = $row->rname;
			$row_content[$count++] = number_format($total_accounts);
			$row_content[$count++] = 0;
			$row_content[$count++] = $is_default;
			$row_content[$count++] = $row->url;
			$row_content[$count++] = $row->notes;
			$row_content[$count++] = $row->insert_time;
			$row_content[$count++] = $row->update_time;
			include("../_includes/system/export/write-row.inc.php");

		}

	}

	include("../_includes/system/export/footer.inc.php");

}
?>
<?php include("../_includes/doctype.inc.php"); ?>
<html>
<head>
<title><?=$software_title?> :: <?=$page_title?></title>
<?php include("../_includes/layout/head-tags.inc.php"); ?>
</head>
<body>
<?php include("../_includes/layout/header.inc.php"); ?>
Below is a list of all the Domain Registrars that are stored in your <?=$software_title?>.<BR><BR>
[<a href="<?=$PHP_SELF?>?export=1">EXPORT</a>]<?php

$result = mysql_query($sql,$connection) or die(mysql_error());

if (mysql_num_rows($result) > 0) {

	$has_active = "1"; ?>
    <table class="main_table" cellpadding="0" cellspacing="0">
    <tr class="main_table_row_heading_active">
        <td class="main_table_cell_heading_active">
            <font class="main_table_heading">Active Registrars (<?=mysql_num_rows($result)?>)</font>
        </td>
        <td class="main_table_cell_heading_active">
            <font class="main_table_heading">Accounts</font>
        </td>
        <td class="main_table_cell_heading_active">
            <font class="main_table_heading">Domains</font>
        </td>
        <td class="main_table_cell_heading_active">
            <font class="main_table_heading">Options</font>
        </td>
    </tr><?php 

    while ($row = mysql_fetch_object($result)) {

	    $new_rid = $row->rid;
    
        if ($current_rid != $new_rid) {
			$exclude_registrar_string_raw .= "'" . $row->rid . "', ";
		} ?>
    
        <tr class="main_table_row_active">
            <td class="main_table_cell_active">
                <a class="invisiblelink" href="edit/registrar.php?rid=<?=$row->rid?>"><?=$row->rname?></a><?php if ($_SESSION['default_registrar'] == $row->rid) echo "<a title=\"Default Registrar\"><font class=\"default_highlight\">*</font></a>"; ?>
            </td>
            <td class="main_table_cell_active"><?php
                $sql_total_count = "SELECT count(*) AS total_count
									FROM registrar_accounts
									WHERE registrar_id = '" . $row->rid . "'";
                $result_total_count = mysql_query($sql_total_count,$connection);
        
                while ($row_total_count = mysql_fetch_object($result_total_count)) { 
                    $total_accounts = $row_total_count->total_count;
                }
                
				if ($total_accounts >= 1) { ?>
		
					<a class="nobold" href="registrar-accounts.php?rid=<?=$row->rid?>"><?=number_format($total_accounts)?></a><?php 
		
				} else {
					
					echo number_format($total_accounts);

				} ?>
            </td>
            <td class="main_table_cell_active"><?php
                $sql_domain_count = "SELECT count(*) AS total_count
									 FROM domains
									 WHERE active NOT IN ('0', '10')
									   AND registrar_id = '" . $row->rid . "'";
                $result_domain_count = mysql_query($sql_domain_count,$connection);
        
                while ($row_domain_count = mysql_fetch_object($result_domain_count)) { 
                    $total_domains = $row_domain_count->total_count;
                }		
        
				if ($total_accounts >= 1) { ?>
		
					<a class="nobold" href="../domains.php?rid=<?=$row->rid?>"><?=number_format($total_domains)?></a><?php 
		
				} else {
					
					echo number_format($total_domains);
					
				} ?>
            </td>
            <td class="main_table_cell_active">
				<a class="invisiblelink" href="edit/registrar-fees.php?rid=<?=$row->rid?>">fees</a>&nbsp;&nbsp;<a class="invisiblelink" target="_blank" href="<?=$row->url?>">www</a>
            </td>
        </tr><?php 

		$current_rid = $row->rid;

	}

}

$exclude_registrar_string = substr($exclude_registrar_string_raw, 0, -2); 

if ($exclude_registrar_string == "") {

	$sql = "SELECT r.id AS rid, r.name AS rname, r.url
			FROM registrars AS r
			WHERE r.id
			GROUP BY r.name
			ORDER BY r.name asc";

} else {
	
	$sql = "SELECT r.id AS rid, r.name AS rname, r.url
			FROM registrars AS r
			WHERE r.id
			  AND r.id NOT IN (" . $exclude_registrar_string . ")
			GROUP BY r.name
			ORDER BY r.name asc";

}

$result = mysql_query($sql,$connection) or die(mysql_error());

if (mysql_num_rows($result) > 0) { 

	$has_inactive = "1";
	if ($has_active == "1") echo "<BR>";
	if ($has_active != "1" && $has_inactive == "1") echo "<table class=\"main_table\" cellpadding=\"0\" cellspacing=\"0\">"; ?>

        <tr class="main_table_row_heading_inactive">
            <td class="main_table_cell_heading_inactive">
                <font class="main_table_heading">Inactive Registrars (<?=mysql_num_rows($result)?>)</font>
            </td>
            <td class="main_table_cell_heading_inactive">
                <font class="main_table_heading">Accounts</font>
            </td>
            <td class="main_table_cell_heading_inactive">
                <font class="main_table_heading">Options</font>
            </td>
        </tr><?php 
    
    while ($row = mysql_fetch_object($result)) { ?>
    
        <tr class="main_table_row_inactive">
            <td class="main_table_cell_inactive">
                <a class="invisiblelink" href="edit/registrar.php?rid=<?=$row->rid?>"><?=$row->rname?></a><?php if ($_SESSION['default_registrar'] == $row->rid) echo "<a title=\"Default Registrar\"><font class=\"default_highlight\">*</font></a>"; ?>
            </td>
            <td class="main_table_cell_inactive"><?php
                $sql_total_count = "SELECT count(*) AS total_count
									FROM registrar_accounts
									WHERE registrar_id = '" . $row->rid . "'";
                $result_total_count = mysql_query($sql_total_count,$connection);
        
                while ($row_total_count = mysql_fetch_object($result_total_count)) { 
                    $total_accounts = $row_total_count->total_count;
                }
                
				if ($total_accounts >= 1) { ?>
		
					<a class="nobold" href="registrar-accounts.php?rid=<?=$row->rid?>"><?=number_format($total_accounts)?></a><?php 
		
				} else {
					
					echo number_format($total_accounts);

				} ?>
            </td>
            <td class="main_table_cell_inactive">
				<a class="invisiblelink" href="edit/registrar-fees.php?rid=<?=$row->rid?>">fees</a>&nbsp;&nbsp;<a class="invisiblelink" target="_blank" href="<?=$row->url?>">www</a>
            </td>
        </tr><?php 

	}

}

if ($has_active == "1" || $has_inactive == "1") echo "</table>";

if ($has_active || $has_inactive) { ?>
	<BR><font class="default_highlight">*</font> = Default Registrar<?php
} 

if (!$has_active && !$has_inactive) { ?>
	<BR>You don't currently have any Domain Registrars. <a href="add/registrar.php">Click here to add one</a>.<?php
} ?>
<?php include("../_includes/layout/footer.inc.php"); ?>
</body>
</html>
