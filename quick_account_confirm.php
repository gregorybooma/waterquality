<?php  $nav_display=5;
include ('sess_start.php');
include ('sp_header.php');

$login = $_SESSION['user_id'];
$message = $_SESSION['message'][0]; //message is an array
?>

<link rel="stylesheet" href="css/global.css" />
<!--[if lt IE 9]>
	<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

</head>

<body>

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

		<!--<div id="textcontainer" style="border:1px solid blue;">-->
		<table width="98%" border="0" cellspacing="5" cellpadding="0">
		  <tr valign="top" align="left">
			<td id="nav_col_td">
				<?php include ('navcol.php');?>			</td>
			<td class="td_border_pad" style="border:none;">
			<div id="text1">

			<h2>Confirmation</h2>


<div class="curved_corners" style="padding:5 15px 15px 15px; margin-bottom:20px; margin-top:20px; line-height:1.75em;">

<p><? echo $message; ?> Your login id is the email address you provided: <? echo $login; ?>. We have sent your temporary password there.<br><a href="http://mit.sea-grant.net/waterquality/index.html">Return to the Water Quality Service page</a>.</p>
</div>

 </div></td>
		  </tr>
		</table>

</body>
</html>
