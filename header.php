<?php 

require_once 'conf/conf.php';

?>
<!DOCTYPE html>
<html>
<head>
	<title>Job Orchestrator</title>
	<link rel="icon" type="image/png" href="img/favicon.png" />

	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/custom.css">
	<link rel="stylesheet" type="text/css" href="css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" type="text/css" href="css/all.min.css">
	<link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
	
	<script src="js/jquery-3.5.1.min.js"></script>
	<script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-light bg-light">
		<a class="navbar-brand">
		<h3 class="m-0 ml-2 d-inline-block" style="color:#316ce6;">Job <i class="fas fa-cog"></i>rchestrator</h3></a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNav">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item ml-auto">
					<a class="navbar-brand"><span class="badge badge-secondary"><?php echo($_SESSION['user_ids']['displayname']) ?></span></a>
				</li>
			</ul>
			<ul class="navbar-nav m-auto">
				<?php 

					if(!isset($_SESSION['page'])){
						$_SESSION['page'] = 1;
					}

				 ?>
				<li class="nav-item <?php if($_SESSION['page'] == 1){ echo('active'); } ?>">
					<a class="nav-link" href="index.php">Nexus Artifacts</a>
				</li>
				<li class="nav-item <?php if($_SESSION['page'] == 2){ echo('active'); } ?>">
					<a class="nav-link" href="clusterjobs.php">Kubernetes Jobs</a>
				</li>
				<li class="nav-item <?php if($_SESSION['page'] == 5){ echo('active'); } ?>">
					<a class="nav-link" href="<?php echo(AIRFLOW_URL); ?>">Airflow</a>
				</li>
				<li class="nav-item <?php if($_SESSION['page'] == 4){ echo('active'); } ?>">
					<a class="nav-link" href="<?php echo(SPARK_LIVY_URL); ?>">Spark</a>
				</li>
				<li class="nav-item <?php if($_SESSION['page'] == 3){ echo('active'); } ?>">
					<a class="nav-link" href="<?php echo(NEXUS_URL); ?>">Nexus</a>
				</li>
				<li class="nav-item <?php if($_SESSION['page'] == 6){ echo('active'); } ?>">
					<a class="nav-link" href="gitlabmonitor.php">Gitlabmonitor</a>
				</li>
			</ul>
			<ul class="navbar-nav ml-auto">
				<li class="nav-item <?php if($_SESSION['page'] == 7){ echo('active'); } ?>">
					<a class="nav-link" href="help.php">Help</a>
				</li>
			</ul>
			<form class="form-inline my-2 my-lg-0 ml-4">
				<a class="btn btn btn-outline-danger" href="logout.php">Sign Out</a>
			</form>
		</div>
	</nav>
