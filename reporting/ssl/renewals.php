<?php
// /reporting/ssl/renewals.php
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
include("../../_includes/timestamps/current-timestamp-basic.inc.php");
include("../../_includes/system/functions/check-date-format.inc.php");

$page_title = $reporting_section_title;
$page_subtitle = "SSL Certificate Renewal Report";
$software_section = "reporting-ssl-renewal-report";
$report_name = "ssl-renewal-report";

// Form Variables
$export = $_GET['export'];
$all = $_GET['all'];
$new_expiry_start = $_REQUEST['new_expiry_start'];
$new_expiry_end = $_REQUEST['new_expiry_end'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if (!CheckDateFormat($new_expiry_start) || !CheckDateFormat($new_expiry_end) || $new_expiry_start > $new_expiry_end) {

		if (!CheckDateFormat($new_expiry_start)) $_SESSION['result_message'] .= "The start date is invalid<BR>";
		if (!CheckDateFormat($new_expiry_end)) $_SESSION['result_message'] .= "The end date is invalid<BR>";
		if ($new_expiry_start > $new_expiry_end) $_SESSION['result_message'] .= "The end date proceeds the start date<BR>";

	}

	$all = "0";

}

if ($all == "1") {

	$range_string = "";
	
} else {

	$range_string = " AND sslc.expiry_date between '$new_expiry_start' AND '$new_expiry_end' ";
	
}

$sql = "SELECT sslc.id, sslc.domain_id, sslc.name, sslcf.type, sslc.expiry_date, sslc.notes, sslc.active, sslc.insert_time, sslc.update_time, sslpa.username, sslp.name AS ssl_provider_name, o.name AS owner_name, (f.renewal_fee * cc.conversion) AS converted_renewal_fee, cc.conversion, d.domain, ip.name AS ip_name, ip.ip, ip.rdns, cat.name AS cat_name
		FROM ssl_certs AS sslc, ssl_accounts AS sslpa, ssl_providers AS sslp, owners AS o, ssl_fees AS f, currencies AS c, currency_conversions AS cc, domains AS d, ssl_cert_types AS sslcf, ip_addresses AS ip, categories AS cat
		WHERE sslc.account_id = sslpa.id
		  AND sslc.type_id = sslcf.id
		  AND sslpa.ssl_provider_id = sslp.id
		  AND sslpa.owner_id = o.id
		  AND sslc.ssl_provider_id = f.ssl_provider_id
		  AND sslc.type_id = f.type_id
		  AND f.currency_id = c.id
		  AND c.id = cc.currency_id
		  AND sslc.domain_id = d.id
		  AND sslc.ip_id = ip.id
		  AND sslc.cat_id = cat.id
		  AND cc.user_id = '" . $_SESSION['user_id'] . "'
		  AND sslc.active NOT IN ('0')
		  " . $range_string . "
		ORDER BY sslc.expiry_date asc, sslc.name asc";	
$result = mysql_query($sql,$connection) or die(mysql_error());
$total_results = mysql_num_rows($result);

$result_cost = mysql_query($sql,$connection) or die(mysql_error());
$total_cost = 0;
while ($row_cost = mysql_fetch_object($result_cost)) {
	$temp_total_cost = $temp_total_cost + $row_cost->converted_renewal_fee;
}

$temp_input_amount = $temp_total_cost;
$temp_input_conversion = "";
$temp_input_currency_symbol = $_SESSION['default_currency_symbol'];
$temp_input_currency_symbol_order = $_SESSION['default_currency_symbol_order'];
$temp_input_currency_symbol_space = $_SESSION['default_currency_symbol_space'];
include("../../_includes/system/convert-and-format-currency.inc.php");
$total_cost = $temp_output_amount;

if ($export == "1") {

	$result = mysql_query($sql,$connection) or die(mysql_error());

	$current_timestamp_unix = strtotime($current_timestamp);
	if ($all == "1") {
		$export_filename = "ssl_renewal_report_all_" . $current_timestamp_unix . ".csv";
	} else {
		$export_filename = "ssl_renewal_report_" . $new_expiry_start . "--" . $new_expiry_end . ".csv";
	}
	include("../../_includes/system/export/header.inc.php");

	$row_content[$count++] = $page_subtitle;
	include("../../_includes/system/export/write-row.inc.php");

	fputcsv($file_content, $blank_line);

	if ($all != "1") {

		$row_content[$count++] = "Date Range:";
		$row_content[$count++] = $new_expiry_start;
		$row_content[$count++] = $new_expiry_end;
		include("../../_includes/system/export/write-row.inc.php");

	} else {

		$row_content[$count++] = "Date Range:";
		$row_content[$count++] = "ALL";
		include("../../_includes/system/export/write-row.inc.php");

	}

	$row_content[$count++] = "Total Renewal Cost:";
	$row_content[$count++] = $total_cost;
	$row_content[$count++] = $_SESSION['default_currency'];
	include("../../_includes/system/export/write-row.inc.php");

	$row_content[$count++] = "Number of SSL Certificates:";
	$row_content[$count++] = number_format($total_results);
	include("../../_includes/system/export/write-row.inc.php");

	fputcsv($file_content, $blank_line);

	$row_content[$count++] = "SSL Cert Status";
	$row_content[$count++] = "Expiry Date";
	$row_content[$count++] = "Renew?";
	$row_content[$count++] = "Renewal Fee";
	$row_content[$count++] = "Host / Label";
	$row_content[$count++] = "Domain";
	$row_content[$count++] = "SSL Provider";
	$row_content[$count++] = "SSL Provider Account";
	$row_content[$count++] = "Username";
	$row_content[$count++] = "SSL Type";
	$row_content[$count++] = "IP Address Name";
	$row_content[$count++] = "IP Address";
	$row_content[$count++] = "IP Address rDNS";
	$row_content[$count++] = "Category";
	$row_content[$count++] = "Owner";
	$row_content[$count++] = "Notes";

	$sql_field = "SELECT name
				  FROM ssl_cert_fields
				  ORDER BY name";
	$result_field = mysql_query($sql_field,$connection);
	
	while ($row_field = mysql_fetch_object($result_field)) {
		
		$row_content[$count++] = $row_field->name;
	
	}

	$row_content[$count++] = "Inserted";
	$row_content[$count++] = "Updated";
	include("../../_includes/system/export/write-row.inc.php");

	while ($row = mysql_fetch_object($result)) {
		
		if ($row->active == "0") { $ssl_status = "EXPIRED"; } 
		elseif ($row->active == "1") { $ssl_status = "ACTIVE"; } 
		elseif ($row->active == "3") { $ssl_status = "PENDING (RENEWAL)"; } 
		elseif ($row->active == "4") { $ssl_status = "PENDING (OTHER)"; } 
		elseif ($row->active == "5") { $ssl_status = "PENDING (REGISTRATION)"; } 
		else { $ssl_status = "ERROR -- PROBLEM WITH CODE IN SSL-CERT-RENEWALS.PHP"; } 
		
		$temp_input_amount = $row->converted_renewal_fee;
		$temp_input_conversion = "";
		$temp_input_currency_symbol = $_SESSION['default_currency_symbol'];
		$temp_input_currency_symbol_order = $_SESSION['default_currency_symbol_order'];
		$temp_input_currency_symbol_space = $_SESSION['default_currency_symbol_space'];
		include("../../_includes/system/convert-and-format-currency.inc.php");
		$export_renewal_fee = $temp_output_amount;

		$row_content[$count++] = $ssl_status;
		$row_content[$count++] = $row->expiry_date;
		$row_content[$count++] = "";
		$row_content[$count++] = $export_renewal_fee;
		$row_content[$count++] = $row->name;
		$row_content[$count++] = $row->domain;
		$row_content[$count++] = $row->ssl_provider_name;
		$row_content[$count++] = $row->ssl_provider_name . ", " . $row->owner_name . " (" . $row->username . ")";
		$row_content[$count++] = $row->username;
		$row_content[$count++] = $row->type;
		$row_content[$count++] = $row->ip_name;
		$row_content[$count++] = $row->ip;
		$row_content[$count++] = $row->rdns;
		$row_content[$count++] = $row->cat_name;
		$row_content[$count++] = $row->owner_name;
		$row_content[$count++] = $row->notes;

		$sql_field = "SELECT field_name
					  FROM ssl_cert_fields
					  ORDER BY name";
		$result_field = mysql_query($sql_field,$connection);
		
		$array_count = 0;
		$field_data = "";
		
		while ($row_field = mysql_fetch_object($result_field)) {
			
			$field_array[$array_count] = $row_field->field_name;
			$array_count++;
		
		}
		
		foreach($field_array as $field) {
		
			$sql_data = "SELECT " . $field . " 
						 FROM ssl_cert_field_data
						 WHERE ssl_id = '" . $row->id . "'";
			$result_data = mysql_query($sql_data,$connection);
			
			while ($row_data = mysql_fetch_object($result_data)) {
		
				$row_content[$count++] = $row_data->{$field};
			
			}
		
		}

		$row_content[$count++] = $row->insert_time;
		$row_content[$count++] = $row->update_time;
		include("../../_includes/system/export/write-row.inc.php");

	}

	include("../../_includes/system/export/footer.inc.php");

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
<?php include("../../_includes/layout/reporting-block.inc.php"); ?>
<?php include("../../_includes/layout/table-export-top.inc.php"); ?>
    <form name="export_ssl_certs_form" method="post" action="<?=$PHP_SELF?>"> 
        <a href="<?=$PHP_SELF?>?all=1">View All</a> or Expiring Between 
        <input name="new_expiry_start" type="text" size="10" maxlength="10" <?php if ($new_expiry_start == "") { echo "value=\"$current_timestamp_basic\""; } else { echo "value=\"$new_expiry_start\""; } ?>> 
        and 
        <input name="new_expiry_end" type="text" size="10" maxlength="10" <?php if ($new_expiry_end == "") { echo "value=\"$current_timestamp_basic\""; } else { echo "value=\"$new_expiry_end\""; } ?>> 
        &nbsp;&nbsp;<input type="submit" name="button" value="Generate Report &raquo;"> 
        <?php if ($total_results > 0) { ?>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>[<a href="<?=$PHP_SELF?>?export=1&new_expiry_start=<?=$new_expiry_start?>&new_expiry_end=<?=$new_expiry_end?>&all=<?=$all?>">EXPORT REPORT</a>]</strong>
        <?php } ?>
    </form>
<?php include("../../_includes/layout/table-export-bottom.inc.php"); ?>
<?php if ($total_results > 0) { ?>
<BR><font class="subheadline"><?=$page_subtitle?></font><BR><BR>
<?php if ($all != "1") { ?>
	<strong>Date Range:</strong> <?=$new_expiry_start?> - <?=$new_expiry_end?><BR><BR>
<?php } else { ?>
	<strong>Date Range:</strong> ALL<BR><BR>
<?php } ?>
<strong>Total Cost:</strong> <?=$total_cost?> <?=$_SESSION['default_currency']?><BR><BR>
<strong>Number of SSL Certificates:</strong> <?=number_format($total_results)?><BR>
<table class="main_table" cellpadding="0" cellspacing="0">
<tr class="main_table_row_heading_active">
<?php if ($_SESSION['display_ssl_expiry_date'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Expiry Date</font>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_fee'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Fee</font>
    </td>
<?php } ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Host / Label</font>
    </td>
<?php if ($_SESSION['display_ssl_domain'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Domain</font>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_provider'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">SSL Provider</font>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_account'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">SSL Account</font>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_type'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Type</font>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_ip'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">IP Address</font>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_category'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Category</font>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_owner'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Owner</font>
    </td>
<?php } ?>
</tr>
<?php while ($row = mysql_fetch_object($result)) { ?>
<?php 
$renewal_fee_individual = $row->renewal_fee * $row->conversion;
$total_renewal_cost = $total_renewal_cost + $renewal_fee_individual; 
?>
<tr class="main_table_row_active">
<?php if ($_SESSION['display_ssl_expiry_date'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->expiry_date?>
	</td>
<?php } ?>
<?php if ($_SESSION['display_ssl_fee'] == "1") { ?>
	<td class="main_table_cell_active">
		<?php
		$temp_input_amount = $row->converted_renewal_fee;
		$temp_input_conversion = "";
		$temp_input_currency_symbol = $_SESSION['default_currency_symbol'];
		$temp_input_currency_symbol_order = $_SESSION['default_currency_symbol_order'];
		$temp_input_currency_symbol_space = $_SESSION['default_currency_symbol_space'];
		include("../../_includes/system/convert-and-format-currency.inc.php");
		echo $temp_output_amount;
		?>
	</td>
<?php } ?>
	<td class="main_table_cell_active">
		<?=$row->name?>
	</td>
<?php if ($_SESSION['display_ssl_domain'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->domain?>
	</td>
<?php } ?>
<?php if ($_SESSION['display_ssl_provider'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->ssl_provider_name?>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_account'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->ssl_provider_name?>, <?=$row->owner_name?> (<?=substr($row->username, 0, 15);?><?php if (strlen($row->username) >= 16) echo "..."; ?>)
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_type'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->type?>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_ip'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->ip_name?> (<?=$row->ip?>)
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_category'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->cat_name?>
    </td>
<?php } ?>
<?php if ($_SESSION['display_ssl_owner'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->owner_name?>
    </td>
<?php } ?>
</tr>
<?php } ?>
</table>
<?php } else { ?>
<BR>The results that will be shown below will display the same columns as you have on your <a href="ssl-certs.php">SSL Certificates</a> page, but when you export the results you will be given even more information.<BR><BR>
The full list of fields in the export is:<BR><BR>
Certificate Status<BR>
Expiry Date<BR>
Renewal Fee<BR>
Total Renewal Cost<BR>
SSL Cert Name<BR>
Associated Domain<BR>
SSL Provider<BR>
SSL Account<BR>
SSL Type<BR>
IP Address Name<BR>
IP Address<BR>
IP Address rDNS<BR>
Category<BR>
Owner<BR>
Notes<BR>
Insert Time<BR>
Last Update Time<BR>
<?php
$sql = "SELECT name
		FROM ssl_cert_fields
		ORDER BY name";
$result = mysql_query($sql,$connection);

if (mysql_num_rows($result) > 0) {

	echo "<BR><strong>Custom Fields</strong><BR>";

	while ($row = mysql_fetch_object($result)) {
		echo $row->name . "<BR>";
	}
	
}
?>
<?php } ?>
<?php include("../../_includes/layout/footer.inc.php"); ?>
</body>
</html>
