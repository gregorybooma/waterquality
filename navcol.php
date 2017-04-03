<div class="navCol" id="navCol">
		  <ul class="nav-col-list" id="nav-col-list">
			<li>
			  <a href="index.html">Home</a>
			</li>
			<li>
			  <a href="https://mit.sea-grant.net/waterquality/explore" >View Data</a>
			</li>
			<li><?
			  if (isset($_SESSION['login_id'])) { echo '<a href="event_params.php" >Submit Data</a>';
			  } else { echo 'Submit Data (<a href="login.php?dest=submit">login</a>)';
			  } ?>
			  </li>
			  <li><?
			  if (isset($_SESSION['login_id'])) { // && ($_SESSION['usr_type']=='T')) {
			      echo '<a href="account_management.php">Manage Account</a>';
			  } else {
			      echo 'Manage Account (<a href="login.php">login</a>)';
			  } ?>
			  </li>
			</ul>
		  <br />
</div> <!-- navCol -->
