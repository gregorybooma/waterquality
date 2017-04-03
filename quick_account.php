<?php  $nav_display=5;
include ('sess_start.php');

$success_message='';

require_once ('/home/mapserver/connects/mitsg_connect.php');

$errors = array(); // Initialize error array.
$message = array(); // Create an empty new variable. used to be initialized as NULL when not array

if (isset($_POST['submit'])) {

	$pfx = 'spx'; //strtolower($project);
	$bigpfx = 'SPX';
	$vars_possible = array('user_id','first_name','last_name');
	$org_vars_possible = array('user_id','first_name','last_name','user_code','organization_id','project_role');
	$vars_required = array("user_id" => 'User ID',"first_name" => 'First name',"last_name" => 'Last name');
	$vars_entered = array();

	foreach ($_POST as $key => $value) {

		if ($key != 'submit' && !empty($value)) {
			${$key} = $value;
			$vars_entered[$key] = $value;
			if (in_array($key,$org_vars_possible)) {
				${$pfx.'_entered'}[$key] = $value;
			}
		}

	}

	$_SESSION['vars_entered'] = $vars_entered;

	if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $user_id)) {
	    $errors[] = 'You did not provide a valid email address.';
	    //$message = '<p><font color="red">You did not provide a valid email address.</font></p>';
	  }

	foreach ($vars_required as $var => $value) {

		if (!in_array($var,array_keys($vars_entered))) {
			$errors[] = "$value is a required field.";
		}

	}

	if (empty($errors)) { // If required fields not null

		// look to see if the user exists
		${$pfx.'_user_query'} = "select * from mitsg_${pfx}_users where user_id = '$user_id' or email_addr = '$user_id';";
		${$pfx.'_user_result'} = pg_query($con,${$pfx.'_user_query'});
		${$pfx.'_user_result_rows'} = pg_num_rows(${$pfx.'_user_result'});
		${$pfx.'_user_array'} = pg_fetch_all_columns(${$pfx.'_user_result'}, pg_field_num(${$pfx.'_user_result'}, 'user_id'));

		$user_query = "select user_id,user_code,first_name,last_name,organization_id,create_user,create_date,mod_user,mod_date from users where user_id = '$user_id';";
		$user_result = pg_query($con,$user_query);
		$user_result_rows = pg_num_rows($user_result);
		$user_array = pg_fetch_all_columns($user_result, pg_field_num($user_result, 'user_id'));

		if (in_array($user_id,${$pfx.'_user_array'})) {
			${$pfx.'_organization_id'} = ${$pfx.'_user_result'}['organization_id']; // not using, but might be good for debugging
			${$pfx.'_user_update'} = "update mitsg_${pfx}_users set (user_status,mod_user,mod_date) = ('active','$user_id',now()) where user_id = '$user_id';";
			${$pfx.'_user_update_result'} = pg_query($con,${$pfx.'_user_update'});
			if (pg_affected_rows(${$pfx.'_user_update_result'}) == 1) { $message[] = "$first_name $last_name ($user_id) already exists as a past user, and has been re-activated."; } else { $errors[] = 'An error occurred (error code 1). Please contact MITSG if the problem persists.'; }

		} else { // does not exist in ${pfx}_users; look in master users table...
			//look in the main users table
			if (in_array($user_id,$user_array)) {
				//make sure just the row with the user_id
				$insert_array = array();
				while ($row = pg_fetch_array($user_result,NULL,PGSQL_ASSOC)) {
					if ($row['user_id'] == $user_id) {
						foreach ($row as $key => $value) {
							if (!empty($value) && in_array($key,$org_vars_possible)) {
								$insert_array[$key] = $value;
							}
						}
					}
				}
				$insert_header_string = implode(',',array_keys($insert_array));
				$insert_value_string = implode("','",array_values($insert_array));
				$insert_value_string = "'".$insert_value_string."'";
				${$pfx.'_user_insert'} = "insert into mitsg_${pfx}_users ($insert_header_string) values ($insert_value_string);";
				${$pfx.'_user_insert_result'} = pg_query($con,${$pfx.'_user_insert'});
				${$pfx.'_user_update'} = "update mitsg_${pfx}_users set (user_group,user_status,project_role,mod_user,mod_date)=('float','active','$project_role','$user_id',now()) where user_id = '$user_id';";
				${$pfx.'_user_update_result'} = pg_query($con,${$pfx.'_user_update'});
				if (pg_affected_rows(${$pfx.'_user_update_result'}) == 1) { $message[] = "$first_name $last_name ($user_id) exists as a user under a different MIT Sea Grant hosted program, and has been activated as a water quality service user."; } else { $errors[] = ${$pfx.'_user_insert'}.' An error occurred (error code 2). Please contact MITSG if the problem persists.'; }

			} else { //does not exist in master table; enter in both master and project table...
				$organization_id = 'INDY';
				$theyear = date("Y");
				$counter = 1;
				$goflag = 1;
				while ($goflag == 1) {
					$thecount = 1000 + $counter;
					$countstring = substr($thecount,-3);
					if (!isset($vars_entered['user_id']) OR empty($vars_entered['user_id'])) {
						$user_id = $pfx.$theyear.strtolower($organization_id).$countstring.'@proximate.us.er';
					} else {
						$vars_entered['email_addr'] = $user_id;
						$email_addr = $user_id;
					}
					$user_code = $bigpfx.$theyear.$organization_id.$countstring;
					$result = pg_query($con,"select * from users where user_code = '$user_code';");
					if (pg_num_rows($result) == 0) {
						$goflag = 0;
						$vars_entered['user_id'] = $user_id;
					} else {
						$counter = $counter + 1;
					}
				}
				$vars_entered_keys = array_keys($vars_entered);
				$vars_write_keys = array_merge(array('user_code','organization_id'),$vars_entered_keys,array('create_user','mod_user'));
				$users_write_keys = $vars_write_keys;

				$position = array_search("project_role",$vars_write_keys);

				unset($users_write_keys[$position]);

				$vars_write_headers = implode(',',$vars_write_keys);
				$vars_entered_fields = array_values($vars_entered);
				$users_entered_fields = $vars_entered_fields;
				$vars_write_fields = array_merge(array($user_code,$organization_id),$vars_entered_fields,array($user_id,$user_id));
				$users_write_fields = $vars_write_fields;

				unset($users_write_fields[$position]);

				$email_position = array_search("email_addr",$users_write_keys);
				unset($users_write_keys[$email_position]);
				unset($users_write_fields[$email_position]);

				$users_write_headers = implode(',',$users_write_keys);

				$vars_write_vals = implode("','",$vars_write_fields);
				$vars_write_vals = "'".$vars_write_vals."'";

				$users_write_vals = implode("','",$users_write_fields);
				$users_write_vals = "'".$users_write_vals."'";

				$user_insert = "insert into users ($users_write_headers) values ($users_write_vals);";
				$user_insert_result = pg_query($con,$user_insert);
				${$pfx.'_user_insert'} = "insert into mitsg_${pfx}_users (user_id,user_code,project_role,first_name,last_name,organization_id,email_addr,create_user,mod_user) values ('$user_id','$user_code','$project_role','$first_name','$last_name','$organization_id','$email_addr','$user_id','$user_id');";
				${$pfx.'_user_insert_result'} = pg_query($con,${$pfx.'_user_insert'});
				${$pfx.'_user_update'} = "update mitsg_${pfx}_users set (user_group,user_status)=('float','active') where user_id = '$user_id';";
				${$pfx.'_user_update_result'} = pg_query($con,${$pfx.'_user_update'});
				if (pg_affected_rows(${$pfx.'_user_update_result'}) == 1) { $message[] = "$first_name $last_name has been added as a user."; } else { $errors[] = 'An error occurred (error code 3). Please contact MITSG if the problem persists.'; }

			}
		}
		// end user insert/update if/else
	} else { // required vars not entered
		$errors[] = 'Please try again.';
	}

	if (empty($errors)) { // If everything's okay.

		//make the password - got from http://www.webtoolkit.info/php-random-password-generator.html - prob. should change
		$length=8;
		$strength=8;
		$vowels = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';
		if ($strength >= 1) {
			$consonants .= 'BDGHJLMNPQRSTVWXZ';
			}
		if ($strength >= 2) {
			$vowels .= "AEUY";
			}
		if ($strength >= 4) {
			$consonants .= '23456789';
			}
		if ($strength >= 8) {
			$consonants .= '@#$%';
			}

		$passwd = '';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++) {
				if ($alt == 1) {
					$passwd .= $consonants[(rand() % strlen($consonants))];
					$alt = 0;
					} else {
						$passwd .= $vowels[(rand() % strlen($vowels))];
						$alt = 1;
						}
			} //end password creation for loop

		$query_pass = "update users set pass_unencr = '$passwd' where user_id = '$user_id';";
		$result_pass = pg_query($con, $query_pass);
		$hashquery = "UPDATE users SET pass_hash=crypt('$passwd',gen_salt('bf')) WHERE user_id='$user_id';";
		$hashresult = pg_query($con,$hashquery); // Run the query.

		if ($result_pass && $hashresult) {
			$_SESSION['user_id']=$user_id;
			mail ("$user_id", "MIT Sea Grant",

				"
				 $message[0] Account information is below.\n\n

				 First Name : ".stripslashes($_POST['first_name'])."\n
				 Last Name : ".stripslashes($_POST['last_name'])."\n
				 Login id : ".stripslashes($_POST['user_id'])."\n\n
				 Password : $passwd \n\n IMPORTANT: Please note that if you use MITSG data services as a participant in another program, this is now your password for that program as well: When you change your password using a particular program's interface, you are changing your password for all programs.
				 ",
				'From: ' . 'mitsg-dataserv@mit.edu');

			$_SESSION['message'] = $message;

			header("Location: https://mit.sea-grant.net/waterquality/quick_account_confirm");

		} else {
			$errors[] = 'An error occurred (error code 4). Please contact MITSG if the problem persists.';
		}//end if password wrote
    } // end if empty errors (all ok)

	pg_close(); // Close the database connection.

} // end if isset post submit

include ('sp_header.php');

?>

<script type="text/javascript">
    var errorString = "<?php if (!empty($errors)) { echo 'ERROR \\n\n'; foreach ($errors as $msg) { echo "$msg \\n\\n"; }  } ?>";
    if (errorString != "") { alert(errorString); }
</script>

<link rel="stylesheet" href="css/global.css" />

</head>

<body>

<div class="container">

    <div id="content">
		<div class="header">
			<p>
			<a href="https://seagrant.mit.edu/ecosys_health.php"><img src="https://nereides.mit.edu/html/MASSBAYweb/mitis/header.gif" width="439" height="44" border="0" alt="MIT Sea Grant Center for Coastal Resources"></a>
			</p>
			<p>
				<div class="subNav"></div>
			</p>
		</div>

		<!--<div id="textcontainer" style="border:1px solid blue;">-->
		<table width="98%" border="0" cellspacing="5" cellpadding="0">
		  <tr valign="top" align="left">
			<td id="nav_col_td">
				<?php include ('navcol.php');?>			</td>
			<td class="td_border_pad" style="border:none;">
			<div id="text1">

			<h2>Create Account</h2>
<p><font color="maroon">STOP AND READ: Use this form only if you are an independent and/or informal user. If you are an administrator of a formal scientific, management, or educational program, please contact <a href="https://seagrant.mit.edu/directory.php?t=staff&mode=th">Julie Simpson</a> in our Marine Advisory Services group for further information. </font></p>

<div class="curved_corners" style="padding:5 15px 15px 15px; margin-bottom:20px; margin-top:20px; line-height:1.75em;">


<form action="quick_account.php" method="post">

<table width="550" border="0" cellspacing="8" cellpadding="0" style="font-size:1.1em;" align="center">
				  <tr valign="top">
				    <td align="right" valign="middle"><b>User Information</b></td>
				    <td align="left">&nbsp;</td>
			      </tr>
				  <tr valign="top">
					<td align="right" valign="middle">First Name</td>
					<td align="left"><input name="first_name" class="input_text" type="text" style="width:98%;" value="<?php if (!empty($errors)) { echo $_POST['first_name']; } else { echo $row['first_name']; } ?>" /></td>
				  </tr>
				  <tr valign="top">
					<td  align="right" valign="middle">Last Name</td>
					<td  align="left"><input name="last_name" type="text"  class="input_text" style="width:98%;" value="<?php  if (!empty($errors)) { echo $_POST['last_name']; } else { echo $row['last_name']; } ?>" /></td>
				   </tr>
				  <tr valign="top">
					<td  align="right" valign="middle">Email Address (this will be your login id)</td>
					<td align="left"><input name="user_id" type="text"  class="input_text" style="width:175px;" value="<?php if (!empty($errors)) { echo $_POST['user_id']; } else { echo $row['user_id']; } ?>" /></td>
				   </tr>
				  <tr valign="top">
					<td  align="right" valign="middle">Best description of you</td>
					<td align="left">
					<select name="project_role">
					<?php
						$project_role_array = array('CitizenInterested' => 'Interested person','CitizenScientist' => 'Citizen scientist', 'StudentElementary' => 'Elementary school student', 'StudentSecondary' => 'Secondary school student');
						foreach ($project_role_array as $k => $v) {
						echo '<option value="'.$k.'"'; if ($project_role == $_POST["project_role"]) { echo ' selected="selected"'; } echo '>'.$v; echo '</option>"';
						}
					?>
					</select>
					</td>
					</tr>
				  <tr align="left" valign="middle">
					<td colspan="2" align="center"><input type="submit" name="submit" value="CREATE ACCOUNT"></td>
		          </tr>
</table>
</form>


</div>

 </div></td>
		  </tr>
		</table>

</body>
</html>