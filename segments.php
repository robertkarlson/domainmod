<?php
// /segments.php
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
include("_includes/start-session.inc.php");
include("_includes/config.inc.php");
include("_includes/database.inc.php");
include("_includes/software.inc.php");
include("_includes/auth/auth-check.inc.php");

$page_title = "Segment Filters";
$software_section = "segments";

function str_stop($string, $max_length){ 
    if (strlen($string) > $max_length){ 
        $string = substr($string, 0, $max_length); 
        $pos = strrpos($string, ", "); 
        if($pos === false) { 
               return substr($string, 0, $max_length)."..."; 
           } 
        return substr($string, 0, $pos)."..."; 
    }else{ 
        return $string; 
    } 
} 
?>
<?php include("_includes/doctype.inc.php"); ?>
<html>
<head>
<title><?=$software_title?> :: <?=$page_title?></title>
<?php include("_includes/layout/head-tags.inc.php"); ?>
</head>
<body>
<?php include("_includes/layout/header.inc.php"); ?>
<?php
$sql = "SELECT id, name, description, segment, number_of_domains
		FROM segments
		ORDER BY name asc";
$result = mysql_query($sql,$connection) or die(mysql_error());
?>
Segments are lists of domains that can be used to help filter and manage your <a href="domains.php">domain results</a>.<BR>
<BR>
Segment filters will tell you which domains match with domains that are saved in <?=$software_title?>, as well as which domains don't match, and you can easily view and export the results.
<BR>
<?php 
$sql_segment_check = "SELECT id
					  FROM segments
					  LIMIT 1";
$result_segment_check = mysql_query($sql_segment_check,$connection) or die(mysql_error());
if (mysql_num_rows($result_segment_check) == 0) {
?>
	You don't currently have any Segments. <a href="add/segment.php">Click here to add one</a>.<BR><BR>
<?php
}
if (mysql_num_rows($result) > 0) { ?>
    <table class="main_table" cellpadding="0" cellspacing="0">
    <tr class="main_table_row_heading_active">
        <td class="main_table_cell_heading_active">
            <font class="main_table_heading">Segments (<?=mysql_num_rows($result)?>)</font>
        </td>
        <td class="main_table_cell_heading_active">
            <font class="main_table_heading">Domains</font>
        </td>
        <td class="main_table_cell_heading_active">
            <font class="main_table_heading">Segment</font>
        </td>
    </tr>

    <?php 
	while ($row = mysql_fetch_object($result)) { ?>

        <tr class="main_table_row_active">
            <td class="main_table_cell_active" valign="top">
                <a class="invisiblelink" href="edit/segment.php?segid=<?=$row->id?>"><?=$row->name?></a>
            </td>
            <td class="main_table_cell_active" valign="top">
                <a class="invisiblelink" href="edit/segment.php?segid=<?=$row->id?>"><?=$row->number_of_domains?></a>
            </td>
            <td class="main_table_cell_active" valign="top">
                <?php
                $temp_segment = preg_replace("/','/", ", ", $row->segment);
                $temp_segment = preg_replace("/'/", "", $temp_segment);
                $cut_string = str_stop($temp_segment, 100);
				?>
                <a class="invisiblelink" href="edit/segment.php?segid=<?=$row->id?>"><?=$cut_string?></a>
            </td>
        </tr>

    <?php 
	} ?>
<?php 
} ?>
    </table>
<?php include("_includes/layout/footer.inc.php"); ?>
</body>
</html>
