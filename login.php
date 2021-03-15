<?php 

session_start();

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/conf/conf.php';

if(LDAP){

require __DIR__ . '/ldapconf/conf.php';
$ad = new \Adldap\Adldap();

$ad->addProvider($config);
$provider = $ad->getDefaultProvider();
$failed = false;

if(isset($_POST['login']) AND isset($_POST['password'])) {
	$username = $_POST['login'];
	$password = $_POST['password'];

	try {
	    if ($provider->auth()->attempt($username, $password,$bindAsUser = true)) {

	        // Retriving data
	        $search = $provider->search();
			try {

			    $record = $search->findByOrFail('samaccountname', $username);
	        	$_SESSION['user_ids'] = array('displayname' => $record->displayname[0],
	        								  'mail' => $record->mail[0],
	        								  'memberof' => $record->memberof);
			} catch (Adldap\Models\ModelNotFoundException $e) {
			    // Record wasn't found!
			}

			// On check les crédentials du User :
			foreach ($_SESSION['user_ids']['memberof'] as $key => $value) {

				// Si on arrive à matcher une des authorizations avec celle du user on valide la connexion
				if ($value == $service_ldap_authorization_domain) {
			    	// Authentification succeded
			        $_SESSION['connected'] = true;
	        		header('location:index.php');
				}
			}

			// Si on a parcouru toutes les credentials du User et qu'on a rien matché, alors on renvoit un refus de crédentials :
	        $failed = true;
	        $failed_message = "You don't have enough rights.";

	    } else {
	        // Failed.
	        $failed = true;
	        $failed_message = "Wrong Login or Password.";
	    }
	} catch (Adldap\Auth\UsernameRequiredException $e) {
	    // The user didn't supply a username.
	} catch (Adldap\Auth\PasswordRequiredException $e) {
	    // The user didn't supply a password.
	}
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Kubernetes Job Orchestrator</title>
	<link rel="icon" type="image/png" href="img/favicon.png" />
	<link href="/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<link href="/css/login.css" rel="stylesheet">
	<script src="js/jquery-3.2.1.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
<!------ Include the above in your HEAD tag ---------->
</head>
<body>
<div class="container py-5">
	<div class="row">
		<div class="col-md-12">
			<div class="col-md-12 text-center mt-4 mb-4">
				<p><img src="img/logo.png"></p>
				<p style="max-width: 500px;margin: auto;background-color: #f7f7f7;padding: 20px;">Help Manages jobs by connecting Gitlab Runners / Nexus Ressources with Kubernetes</p>
			</div>
			<div class="row">
				<div class="col-md-6 mx-auto mt-4">
					<!-- form card login -->
					<div class="card rounded-0" id="login-form">
						<div class="card-header">
							<h3 class="mb-0">LDAP Login</h3>
						</div>
						<div class="card-body">
							<?php if ($failed) {
								echo('<div class="alert alert-danger" role="alert">'.$failed_message.'</div>');
							} ?>
							<form class="form" role="form" method="POST">
								<div class="form-group">
									<label for="uname1">Login : </label>
									<input type="text" class="form-control form-control-lg rounded-0" name="login" id="login" placeholder="login" required>
								</div>
								<div class="form-group">
									<label>Password :</label>
									<input type="password" class="form-control form-control-lg rounded-0" name="password" id="password"  placeholder="**********"  required>
								</div>
								<div>
									<label class="custom-control custom-checkbox">
										<a href="javascript:void('forgot-form-link');" class="forgot-form-link">Forgot Password</a>
									</label>
								</div>
								<button type="submit" class="btn btn-orange btn-lg float-right" id="btnLogin">Login</button>
							</form>
						</div>
					</div>
					<!-- /form card login end-->
					
					<!-- form card forgot -->
					<div class="card rounded-0" id="forgot-form">
						<div class="card-header">
							<h3 class="mb-0">Reset Password</h3>
						</div>
						<div class="card-body">
							<form class="form" role="form" autocomplete="off" novalidate="" method="POST">
								<div class="form-group">
									<label>Contacts : </label>
									<p>To edit your password, please go to Windows and reset your password.</p><br>
									<p>If you need help, please contact your LDAP administrator.</p>
								</div>
								<div>
									<label class="custom-control custom-checkbox">
										<a href="javascript:void('login-form-link');" class="login-form-link">< Back to Login Page </a>
									</label>
								</div>
								<div class="btn btn-orange btn-lg float-right" id="btnLogin">Reset Password</div>
							</form>
						</div>
					</div>
					<!-- /form card forgot end -->
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container">
	<div class="row">
		<div class="col-12">
			<footer>
				<br/>
				<p>
					Data Factory / Direction des données / Institut Curie - <?php 
					echo(date('Y'));

			        # ouverture du json
					$json_version = file_get_contents('./version/version.json');
					$json_version_data = json_decode($json_version);

					echo(" - Version : ".$json_version_data->version);                
					?>
				</p>
			</footer>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function(){

		$(document).ready(function(){
			$("#register-form").hide();
			$("#forgot-form").hide();	
			$(".register-form-link").click(function(e){
				$("#login-form").slideUp(0);
				$("#forgot-form").slideUp(0)	
				$("#register-form").fadeIn(300);	
			});

			$(".login-form-link").click(function(e){
				$("#register-form").slideUp(0);
				$("#forgot-form").slideUp(0);	
				$("#login-form").fadeIn(300);	
			});

			$(".forgot-form-link").click(function(e){
				$("#login-form").slideUp(0);	
				$("#forgot-form").fadeIn(300);	
			});
		});

	});
</script>
</body>
</html>
<?php 

} else {
    $_SESSION['connected'] = true;
	header('location:index.php');
}

 ?>