<?php
//ini_set('display_errors',1);
$nav_display=5;
include ('sess_start.php');
include ('sp_header_secure.php');

require_once ('/home/mapserver/connects/mitsg_connect.php');

$success_message='';
$errors = array(); // Initialize error array.
$err_flag = array(); // Initialize error array.
$logged_write='false';
$file_upload_reminder_styles='';

//unset($_SESSION['loggedfilename']);

function allow_filetype ($filepath) {
	if (function_exists('finfo_file')) {
	$finfo = finfo_open();
	$mime_long = finfo_file($finfo, $filepath, FILEINFO_MIME);
	$sc_pos=strpos($mime_long,';');
	$mime=substr_replace($mime_long,'',$sc_pos);
	finfo_close($finfo);
	} elseif (function_exists('mime_content_type')) {
	$mime_long=mime_content_type($filepath);
	$sc_pos=strpos($mime_long,':');
	$mime=substr_replace($mime_long,'',0,$sc_pos);
	}
	return $mime;
}

//nullify any previous sql insert file session variable
$_SESSION['csvfile_path'] = NULL;

if (isset($_POST['submit'])) {

// Check for an uploaded file.
	if (!empty($_FILES['upload']['tmp_name'])) {
		//acceptable file type test
		$mime=allow_filetype($_FILES['upload']['tmp_name']);
		if (isset($mime)) {
		  $allowed_filetypes=array(
			  // comment-out the filetypes that are NOT ALLOWED for upload by this particular form
//			  	  'application/pdf' //pdf
//				  'application/postscript',  //ps
//				  'audio/mpeg', //mp3
//				  'audio/x-aiff', //aif, aiff
//				  'audio/x-wav', //wav
//				  'image/bmp', //bmp
//				  'image/gif', //gif
//				  'image/jpeg', //jpg, jpeg
//				  'image/pjpeg', //jpeg
//				  'image/png', //png
//				  'image/tiff', //tif, tiff
//				  'image/x-png', //png
				  'text/plain' //txt
//				  'text/rtf', //rtf
//				  'application/msword', //doc
//				  'application/vnd.ms-office', //ppt
//				  'application/vnd.ms-excel', //xls
//				  'application/zip' //docx, pptx, xlsx, kmz

			  );
		  if (!in_array($mime,$allowed_filetypes)) {
			  $errors[]='The type of file selected for upload ( '.$mime.' ) is not allowed.';
		  }
		} //if isset mime

		$name = uniqid('datalog_');
		$mv_folder='tmp';
		$fullpath="tmp/$name";

		if (empty($errors)) {
			if (move_uploaded_file($_FILES['upload']['tmp_name'], "$mv_folder/{$name}")) {
				$logged_write='true';
				chmod("$fullpath",0750);
			} else {
				$errors[] = 'File Upload Error';
				@unlink($fullpath);
			} //if file moved ok
		} //if errors empty so far
	}//if a file was uploaded

// END Check for an uploaded file.

	// gather submitted variables
	if ($logged_write=='true') {

		$colduptestarray = array();
		$coldupsexist = 'f';
		foreach ($_POST as $key => $value) {
			if ( !in_array($key,array('submit','MAX_FILE_SIZE','header_row','instrument_tz'))) { //if ($key != 'submit' && $key != 'MAX_FILE_SIZE' && !empty($value)) {
				if (!in_array($value,$colduptestarray)) {
					$colduptestarray[] = $value;
					${$key} = $value;
					$vars_entered[$key] = $value;
				} else {
					$coldupsexist = 't';
					$errors[]='You indicated duplicate parameter names in your column mapping. Please try again.';
				}
			}

		}//foreach post var

		$_SESSION['vars_entered'] = $vars_entered;
		$_SESSION['vars_entered_indexes'] = array_values($vars_entered);

		$_SESSION['header_row'] = $_POST['header_row'];

		$instrument_tz = $_POST['instrument_tz'];
		if (empty($instrument_tz)) {
			$errors[] = 'You must specify the time zone that corresponds to the timestamp column in the data file';
		} else {
			$_SESSION['instrument_tz'] = $instrument_tz;
			$_SESSION['posted']['time_reference_datum'] = $instrument_tz;
		}

		#parses uploaded file and writes validated vars to restructured data file for subsequent upload
		$fcon = fopen("$fullpath",'r');

		if (!$fcon) { echo 'Could not open file'; $errors[] = 'Could not open logfile for parsing'; exit; //$_SESSION['lastwrite'] = 'could not open file';
			} else {

				//initialize sql script for inserts
				$event_id = $_SESSION['event_id'];
				$user_id_array = explode('@',$user_id);
				$uniqprefix = $user_id_array[0];
				$csvfile_prefix = uniqid("$uniqprefix");
				$csvfile_path = 'tmp/'.$csvfile_prefix.'.csv';
				$_SESSION['csvfile_path'] = $csvfile_path;
				$_SESSION['csvfile_prefix'] = $csvfile_prefix;
				$csvfile_con = fopen("$csvfile_path",'w');

				//establish upload_id and record the upload
				$upload_date = date("Y-m-d H:i:s");
				$timestamp_compact = date("YmdHis");
				$upload_id = $event_id.'.'.$timestamp_compact;
				$result = pg_query($con,"insert into wq_uploads (upload_id,upload_user,upload_date,event_id) values ('$upload_id','$user_id','$upload_date','$event_id');");
				if (!$result) { $errors[] = 'Could not record the file upload. Please try again and contact MIT Sea Grant if the problem persists.'; exit; }

				$unexpected_rowlength = 'f';

				//set a row counter
				$rownum = 1;

				while (!feof($fcon)) { //for every row of uploaded file
					$row = str_replace('"','',trim(fgets($fcon))); //strip anything but the raw values in a comma-delimited row
					$vars = explode(',',$row);

					$expectedendindex = max(array_keys($_SESSION['vars_entered_indexes'])); //for some reason count() not getting count
					$rowendindex = max(array_keys($vars));

					// all row vectors should be the same length
					if (($rowendindex != $expectedendindex) && !empty($row) && $coldupsexist == 'f') {
						$unexpected_rowlength = 't';
					}

					if (!empty($row) && empty($errors)) {
						//set the rowgroup_id to track corresponding data values
						$rowgroup_id = $upload_id.'.'.$rownum;

						for ($i = 0; $i < count($vars); $i++) { //for every column

							$varname = $_SESSION['vars_entered_indexes'][$i];

							//if ($varname != 'time') {
								//check for nulls and non-numeric
								if (empty($vars[$i]) && $vars[$i] !=0 && $vars[$i] != "0") { //don't delete zeros!
									$vars[$i] = 'NULL';
								} else {
									if ($varname != 'time' && !is_numeric($vars[$i]) && !isset($_POST['header_row'])) {
										$errors[] = 'The parameter '.$varname.' on row '.$rownum.' is non-numeric (value is '.$vars[$i].'.) Only numeric values are permitted at this time. Use 1 and 0 for presence-absence. If your data has a header row, ensure that you have indicated so on the form.';
									}
								} //end check for nulls and non-numeric

								//get instrument, units, and reference datum and write to meta table
								$instrumentname = $varname.'_instrument';
								$instrument = $_SESSION['posted']["$instrumentname"];
								$unitsname = $varname.'_units';
								$units = $_SESSION['posted']["$unitsname"];
								$referencedatumname = $varname.'_reference_datum';
								$referencedatum = $_SESSION['posted']["$referencedatumname"];

								if ($rownum == 1) { //write the metadata for the upload only once!
									$result = pg_query($con,"insert into wq_obs_meta (upload_id,param_short_name,unit_code,reference_datum,instrument_id) values ('$upload_id','$varname','$units','$referencedatum','$instrument');");
									if (!$result) {
										$result = pg_query($con,"delete from wq_uploads where upload_id = '$upload_id';");
										$errors[] = 'Could not record the observation metadata. Please try again and contact MIT Sea Grant if the problem persists.'; exit;
									}
								}

								//OMITTING DUP CHECK, FOR NOW, TO ALLOW ENOUGH FLEXIBILITY FOR LESS-SPECIFIC FIELD-COLLECTED DATA:
								//make sure the record is not a dup
								//$rowdupresult = pg_query($con,"select time,param_short_name,unit_code from wq_obs where event_id = '$event_id' and time = '$timestamp' and param_short_name = '$varname' and unit_code = '$units';");
								//if (pg_num_rows($rowdupresult) > 0) {
								//	$errors[] = 'The record for parameter '.$varname.' with a timestamp of '.$timestamp.' appears to be a duplicate. Records having duplicate timestamp-parameter-units combinations are not permitted.';
								//}

								//write the record
								if ( (isset($_POST['header_row']) && $rownum > 1 && empty($errors)) || (!isset($_POST['header_row']) && empty($errors)) ) { //$i > 0
									//if it's timestamp, need to convert to epoch for storage
									if ( $varname == 'time' ) {
										// Need two "at time zone" conditions to get around issue where the user's timestamp has time zone info included. The first gives an
										// epoch relative to the system's locale, so the second is needed to ensure the epoch is in the locale of the reference datum.
										$result = pg_query($con,"select extract(epoch from timestamp '$vars[$i]' at time zone '$referencedatum' at time zone 'UTC');");
										$timestamp_double_array = pg_fetch_all_columns($result);
										$timestamp_double = $timestamp_double_array[0];
										$line = "$event_id,$upload_id,$rowgroup_id,$varname,$timestamp_double\n";
									} else {
										$line = "$event_id,$upload_id,$rowgroup_id,$varname,$vars[$i]\n";
									}
									$writeresult = fwrite($csvfile_con,"$line");
									if (empty($writeresult)) {
										$errors[] = 'Could not write data for '.$varname.' on row '.$rownum.'. Please check your data and try again.';
										$result = pg_query($con,"delete from wq_uploads where upload_id = '$upload_id';");
									}
								} //end write record
							//} //end if not time
						} //end for every column
					} //end if row not empty
					$rownum++;
					$fcon++;
				} //END while (for every row)

				// putting this here because an error can run off of the screen, requiring browser re-start
				if ($unexpected_rowlength == 't') {
					$errors[] = 'One or more rows had an unexpected number of values (the number of data values does not match the number of columns indicated.)';
				}

				fclose($csvfile_con); //close the file connection

				if (!empty($errors)) {
					@unlink($csvfile_path); //delete the data file if any errors
				} else {
					chmod("$csvfile_path",0750); //otherwise set permissions for next step
				}
			} //end else parse the file if it can be opened
		fclose($fcon);
		@unlink($fullpath); //delete the original uploaded file
		// END write logged file //

		//$_SESSION['loggedfilename']=$_FILES['upload']['name'];

	} //END if $logged_write=='true' (temporary file was moved successfully)

	if (empty($errors)) {
		//$_SESSION['data_submit']='submitted';
		header("Location: https://mit.sea-grant.net/waterquality/data_upload_confirm");
	}

} // if submitted

include ('date_time_arrays.php');
include ('sp_header.php');

?>

<script type="text/javascript">
    var errorString = "<?php if (!empty($errors)) { echo 'ERROR \\n\n'; foreach ($errors as $msg) { echo "$msg \\n"; } if (!empty($_FILES['upload']['tmp_name'])) { echo '\\n\nRE-SELECT YOUR DATA FILE.\\n\n'; $file_upload_reminder_styles='style="color:red; font-weight:bold;"'; } } ?>";
    if (errorString != "") { alert(errorString); }
</script>

<? $_SESSION['prev_page']= 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; // a fancy way of saying "this page" ?>

	<link rel="stylesheet" href="css/global.css" />
	<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>

<body>
<? //echo $_SESSION['prev_page']; ?>
<div class="container">

    <div id="content">
		<div class="header">
			<p>
			<a href="http://seagrant.mit.edu/ecosys_health.php"><img src="http://nereides.mit.edu/html/MASSBAYweb/mitis/header.gif" width="439" height="44" border="0" alt="MIT Sea Grant Center for Coastal Resources"></a>
			</p>
			<p>
				<div class="subNav"></div>
			</p>
		</div>

	<table width="100%" border="0" cellspacing="5" cellpadding="0">
	  <tr valign="top" align="left">
		<td id="nav_col_td">
		<?php include('navcol.php');?>
		</td>
		<td class="td_border_pad" style="border:none; padding-left:0px; padding-right:0px;">

		<? $data_page_name=$_SERVER['REQUEST_URI']; //str_ireplace('/','',$_SERVER['REQUEST_URI']); ?>
<? //if (isset($_SESSION['vars_entered'])) {echo $_SESSION['writequery']; print_r($_SESSION['posted']); echo '<br>'; print_r($_POST); echo $_SESSION['varcount']; echo '<br>expected end is '.$expectedendindex;  echo '<br>row end is '.$rowendindex;}//echo '<br>'; print_r($_SESSION['included']); echo '<br>'; print_r($_POST);} ?>

	<form enctype="multipart/form-data" action="<? echo $data_page_name; ?>" method="post">
	<h3 style="margin-top:0px;">Upload Data</h3>

	<input type="hidden" name="MAX_FILE_SIZE" value="10000000000">

	<fieldset  style="margin-bottom:10px; padding:0px 10px 10px 10px;"><legend style="letter-spacing:.25em;"><b>Data file</b></legend>
	<p style="padding-top:0px;"><font color="red">IMPORTANT REQUIREMENTS:</font>
	<ol>
	<li>We currently accept data as flat, plain text comma-delimited tables only (use a .csv or .txt file extension.) Columns may be in any order, and inclusion of a header row is optional.</li>
	<li>One table column must contain measurement timestamps. Our preferred format is as follows: %Y-%m-%d %H:%M:%S (for example, 2012-01-31 23:59:01.) Other valid formats can be found on our <a href="timestamp_format.html" target="_blank">page outlining valid date and time types</a>.</li>
	<li>Note that it is possible that the time zone corresponding to the timestamp in the data file is not the same as the local time zone where the sampling took place. Some instruments, for example, are set to UTC by default. Please confirm before upload.</li>
	<li>The measurement units, instruments, and reference datums for every parameter selected below must apply to every corresponding record in the uploaded file. Variable columns with mixed units should be subsetted to a different file based upon units, and uploaded separately.</li>
	<li>Only numeric data is accepted, though presence-absence variables with binary coding (1 or 0) may be included. At this time, any fields with no data must be left completely blank (i.e. they may not contain a no data identifier).</li>
	</ol>
	</p>
	<p style="padding-top:0px;"><b>Select a data file to upload</b></p>

	<p style="margin-top:10px;"><input type="file" name="upload" <? echo $file_upload_reminder_styles; ?>/>

	<p style="padding-top:0px;"><b>Indicate if the file has a header row</b></p>
	<p style="margin-top:10px;"><? echo '<input type="checkbox" name="header_row" value="header_row"'; if ($_POST['header_row']=='header_row') { echo ' checked';} echo'> File includes header row'; ?>

	<p style="padding-top:0px;"><b>Select the time zone that corresponds to the timestamp column in the data file</b></p>

	<p style="padding-top:0px;">
		<select name="instrument_tz">
		<option value="">Unspecified</option>
		<?php

		//Only get the name, NOT the abbrev. PostgreSQL changes the abbrev based upon daylight savings status, while the name remains constant.
		//Get the US first
		$tz_array = array();
		//next line should work, but google doesn't do the US/* zone names, so switch if need be
		$tz_result = pg_query($con,"select name from pg_timezone_names where name like 'US/%' and name != 'US/Pacific-New' order by name;");
//		$tz_result = pg_query($con,"select name from pg_timezone_names where name like 'America/%' order by name;");
		$numrows = pg_num_rows($tz_result);
		$i = 0;
		while ($tzrow = pg_fetch_array($tz_result)) {
			$tz_array[$i] = $tzrow['name'];
			$i++;
		}
		for ($i = 0; $i < $numrows; $i++) {
			echo '<option value="'.$tz_array[$i].'"'; if ($tz_array[$i]==$instrument_tz) { echo ' selected="selected"'; } echo '>'.$tz_array[$i].'</option>"';
		}
		//Then the rest of the world
		$tz_array = array();
		//see comments about google above
		$tz_result = pg_query($con,"select name from pg_timezone_names where name not like '%GMT%' and name not like 'posix%' and name not like 'US/%' order by name;");
//		$tz_result = pg_query($con,"select name from pg_timezone_names where name not like '%GMT%' and name not like 'posix%' and name not like 'America/%' order by name;");
		$numrows = pg_num_rows($tz_result);
		$i = 0;
		while ($tzrow = pg_fetch_array($tz_result)) {
			$tz_array[$i] = $tzrow['name'];
			$i++;
		}
		for ($i = 0; $i < $numrows; $i++) {
			echo '<option value="'.$tz_array[$i].'"'; if ($tz_array[$i]==$instrument_tz) { echo ' selected="selected"'; } echo '>'.$tz_array[$i].'</option>"';
		}
		?>
		</select>
	</p>

	<p style="padding-top:0px;"><b>Indicate the column order of your table</b> (left to right)</p>

	<?
	$included_string = "'".implode("','",$_SESSION['included'])."'"; //echo count($_SESSION['included']); echo $included_string;
	$included_result = pg_query($con,"select param_short_name,param_long_name from wq_params where param_short_name in ($included_string) order by param_long_name;");

	// build params included array
	$params_included = array();

	while ($row = pg_fetch_array($included_result)) {
		$params_included[$row['param_short_name']] = $row['param_long_name'];
	}
	// php won't recycle a result, so have to duplicate the array for the number of column selects
	for ($colnum = 1; $colnum <= count($_SESSION['included']); $colnum++) {
		$params_included_name = 'params_included_'.$colnum;
		${$params_included_name} = $params_included;
		//print_r(${$params_included_name}); echo '<br>';
	}

	// now write the table
	$bg = '#FFFFFF';
	echo '<table>';

	for ($colnum = 1; $colnum <= count($_SESSION['included']); $colnum++) {
		$array_name = 'params_included_'.$colnum;
		echo '<tr bgcolor="' . $bg . '">';
		echo '<td>The parameter in column '.$colnum.' is: </td>';
		$select_name = 'col'.$colnum.'_param';
		echo '<td><select name="'.$select_name.'">';
		foreach (${$array_name} as $k => $v) {
			echo '<option value="'.$k.'"'; if ($k==$_POST["$select_name"]) { echo ' selected="selected"'; } echo '>'.$v.'</option>';
		}
		echo '</select></td></tr>';
		$bg = ($bg=='#FFFFFF' ? '#CCCCCC' : '#FFFFFF');
	}
	echo '</table>';

	?>

	</fieldset>

	<input type="submit" name="submit" value="Verify Input">

	</form>


</td>
	  </tr>
	</table>
<?php
pg_close($con);
//print_r($_SESSION['event']); echo '<br>'; print_r($_SESSION['included']); echo '<br>'; print_r($_SESSION['posted']); echo '<br>'; print_r($_POST);
?>
</div>
</div>
</body>
</html>