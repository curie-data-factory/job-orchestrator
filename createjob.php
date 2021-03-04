<?php 

require __DIR__ . '/vendor/autoload.php';

session_start();

if (isset($_SESSION['connected'])) {
	include 'header.php';
	include 'core.php';

	$errorMessage = "";
	$throwError = False;
	$successMessage = "";
	$throwSuccess = False;

	// On check si on a soumit le formulaire pour run les containers
	if (isset($_POST['submitJob']) && !empty($_POST['submitJob'])) {

		$data = unserialize(urldecode($_POST['value']));
		$cifsEnabled = false;

		if (isset($_POST['contextarguments'])) {
			$contextarguments = $_POST['contextarguments'];
		} else {
			$contextarguments = "";
		}

		$envArray = array('JOBTORUN' => $_POST['ressourceToRun'],
						  'ROOTFOLDER' => '/folderrunner',
						  'CONTEXTARGUMENTS' => base64_encode($contextarguments));

		// On check la configuration CIFS
		if (@$_POST['switchmontagecifs'] == "on") {
			$endpoint = $_POST['CIFSendpoint'];
			$source = $_POST['CIFSsourcePath'];
			$dest = $_POST['CIFSdest'];

			if (($source == "") || ($dest == "")) {
				$throwError = True;
				$errorMessage = "You need to set a source and a destination path for the CIFS mount.";
			} else {
				$cifsEnabled = true;
				// On load le fichier de credentials cifs
				$string = file_get_contents("./conf/conf_cifs.json");
				$cifsData = json_decode($string,true);
				$cifsData = $cifsData[$endpoint];

				$cifs['endpoint'] = $endpoint;
				$cifs['user'] = $cifsData['user'];
				$cifs['password'] = $cifsData['password'];
				$cifs['domain'] = $cifsData['domain'];
				$cifs['source'] = $source;
				$cifs['dest'] = $dest;

				$cifsArray  = array('CIFSENDPOINT' => $cifs['endpoint'],
									'CIFSUSER' => $cifs['user'],
									'CIFSPASSWORD' => $cifs['password'],
									'CIFSDOMAIN' => $cifs['domain'],
									'CIFSSOURCE' => $cifs['source'],
									'CIFSDEST' => $cifs['dest']);

				// On ajoute aux variables d'environnement celles pour le montage CIFS
				$envArray = array_merge($envArray,$cifsArray);
			}
		}

		// On check si on a besoin des creds de proxy
		if (@$_POST['switchproxy'] == "on") {
			$proxyArray  = array('HTTP_PROXY' => PROXY_CONF,
								'HTTPS_PROXY' => PROXY_CONF,
								'NO_PROXY' => ".curie.net, .curie.fr");

			// On ajoute aux variables d'environnement celles pour le montage CIFS
			$envArray = array_merge($envArray,$proxyArray);
		}

		// Creation de la configmap
		if(isset($_POST['secretmap']) && ($_POST['secretmap'] != "")) {

			$configName = "config";
			if (isset($_POST['configName']) && ($_POST['configName'] != "")) {
				$configName = $_POST['configName'];
			}

			$jsonData = '{"yaml":"apiVersion: v1\nkind: Secret\nmetadata:\n  name: '.$_POST['jobName'].'-secret\n  namespace: '.KUBERNETES_NAMESPACE.'\ndata:\n  '.$configName.': '.base64_encode($_POST['secretmap']).'\n"}';

			$response = executeDeployement($jsonData);
			$message = json_decode($response,True)['message'];
			$lastElem = preg_split('/ /',$message);
			$lastElem = end($lastElem);

			if($lastElem == "created\n") {
				$throwSuccess = True;
				$successMessage .= " Config Created\n";
			}
		}
	
		// Check si l'expression CRON est bien renseignée
		if (($_POST['runStrategy'] == "cron-recurrent-job") && ($_POST['cronSchedule'] == "")) {
			$throwError = True;
			$errorMessage = "You need to set a CRON schedule expression in order to run a CRON JOB";
		} 
		else {

			$memoryLimit="80000Mi";
			$cpuLimit="25";
			$memoryRequest="1000Mi";
			$cpuRequest="1";

			// Pour les CRON JOBS
			if ($_POST['runStrategy'] == "cron-recurrent-job") {

				$version="v1beta1";
				$kind="CronJob";
				$spec='\n  concurrencyPolicy: Allow\n  failedJobsHistoryLimit: 10\n  jobTemplate:\n    spec:\n      activeDeadlineSeconds: 10\n      backoffLimit: 0\n      completions: 1\n      parallelism: 1';
				$env='\n          - env:';

				// On ajoute les variables d'environnement
				foreach ($envArray as $name => $value) {
					$env .= '\n            - name: '.$name.'\n              value: '.$value;
				}

				// On ajoute à la main les secrets pour la connexion NEXUS
				$env .='\n            - name: PASSWORDNEXUS\n              valueFrom:\n                secretKeyRef:\n                  key: PASSWORDNEXUS\n                  name: nexus-id\n            - name: USERNEXUS\n              valueFrom:\n                secretKeyRef:\n                  key: USERNEXUS\n                  name: nexus-id';

				// Construction du yaml
				$jsonData = '{"yaml":"apiVersion: batch/'.$version.'\nkind: '.$kind.'\nmetadata:\n  labels:\n    cattle.io/creator: kubernetes-job-orchestrator\n  name: '.$_POST['jobName'].'\n  namespace: '.KUBERNETES_NAMESPACE.'\nspec:'.$spec.'\n      template:\n        spec:\n          containers:'.$env.'\n            image: '.$_POST['selectRunner'].'\n            imagePullPolicy: IfNotPresent\n            name: '.$_POST['jobName'].'\n            resources:\n              limits:\n                cpu: \"'.$cpuLimit.'\"\n                memory: '.$memoryLimit.'\n              requests:\n                cpu: \"'.$cpuRequest.'\"\n                memory: '.$memoryRequest.'\n            securityContext:\n              allowPrivilegeEscalation: true\n              privileged: true\n';

				if(isset($_POST['secretmap']) && ($_POST['secretmap'] != "")) {
					// Données de la configmaps
					$configmap = '            volumeMounts:\n              - mountPath: /folderrunner/conf\n                name: '.$_POST['jobName'].'-secret\n          volumes:\n          - name: '.$_POST['jobName'].'-secret\n            secret:\n              secretName: '.$_POST['jobName'].'-secret\n';

					$jsonData .= $configmap;
				}

				$jsonData .= '          imagePullSecrets:\n          - name: registrygitlab-curie\n          restartPolicy: Never\n  schedule: \''.$_POST['cronSchedule'].'\' \n  suspend: false","defaultNamespace":"'.KUBERNETES_NAMESPACE.'"}';

			} else {

				$version="v1";
				$kind="Job";
				$spec='\n  backoffLimit: 0\n  completions: 1\n  parallelism: 1';
				$env='\n      - env:';

				// On ajoute les variables d'environnement
				foreach ($envArray as $name => $value) {
					$env .= '\n        - name: '.$name.'\n          value: '.$value;
				}

				// On ajoute à la main les secrets pour la connexion NEXUS
				$env .='\n        - name: PASSWORDNEXUS\n          valueFrom:\n            secretKeyRef:\n              key: PASSWORDNEXUS\n              name: nexus-id\n        - name: USERNEXUS\n          valueFrom:\n            secretKeyRef:\n              key: USERNEXUS\n              name: nexus-id';

				// On génère la ressource Kubernetes à envoyer à l'API
				$jsonData = '{"yaml":"apiVersion: batch/'.$version.'\nkind: '.$kind.'\nmetadata:\n  labels:\n    cattle.io/creator: kubernetes-job-orchestrator\n  name: '.$_POST['jobName'].'\n  namespace: '.KUBERNETES_NAMESPACE.'\nspec:'.$spec.'\n  template:\n    metadata:\n      labels:\n        job-name: '.$_POST['jobName'].'\n    spec:\n      containers:'.$env.'\n        image: '.$_POST['selectRunner'].'\n        imagePullPolicy: IfNotPresent\n        name: '.$_POST['jobName'].'\n        resources:\n          limits:\n            cpu: \"'.$cpuLimit.'\"\n            memory: '.$memoryLimit.'\n          requests:\n            cpu: \"'.$cpuRequest.'\"\n            memory: '.$memoryRequest.'\n        securityContext:\n          allowPrivilegeEscalation: true\n          privileged: true\n';

				if(isset($_POST['secretmap']) && ($_POST['secretmap'] != "")) {
					// Données de la configmaps
					$configmap = '        volumeMounts:\n          - mountPath: /folderrunner/conf\n            name: '.$_POST['jobName'].'-secret\n      volumes:\n      - name: '.$_POST['jobName'].'-secret\n        secret:\n          secretName: '.$_POST['jobName'].'-secret\n';
					$jsonData .= $configmap;
				}

				$jsonData .= '      imagePullSecrets:\n      - name: registrygitlab-curie\n      restartPolicy: Never","defaultNamespace":"'.KUBERNETES_NAMESPACE.'"}';
			}

			$response = executeDeployement($jsonData);
			$message = json_decode($response,True)['message'];
			$lastElem = preg_split('/ /',$message);
			$lastElem = end($lastElem);

			if($lastElem == "created\n") {
				$throwSuccess = True;
				$successMessage .= "Job Created. You can go to the list.";
			}
		}
	}

	// On récupère les données passées en POST
	$data = unserialize(urldecode($_POST['value']));

	if ($throwError) {
		echo('<div id="error">'.$errorMessage.'</div>');
	}

	if ($throwSuccess) {
		echo('<div id="success">'.$successMessage.'</div>');
	} elseif(isset($message)) {
		echo('<div id="primary">'.$message.'</div>');
	}

	// On load le fichier de credentials cifs
	$string = file_get_contents("./conf/conf_cifs.json");
	$cifsData = json_decode($string,true);

	if (!$throwSuccess) {
		?>
		<form action="#" method="post">
			<div class="container-fluid" style="max-width: 60%;">
				<div class="row p-4" style="background-color: #f9f9f9;">
					<a class="btn btn-secondary mb-2" href="index.php">Retour</a>
					<div class="col-12 m-2">
						<h1>Configuration of the job :</h1>
					</div>
					<div class="col-12">
						<nav aria-label="breadcrumb">
							<ol class="breadcrumb">
								<li class="breadcrumb-item active" aria-current="page">Repository : <span class="badge badge-primary"><?php echo $data['repository']; ?></span></li>
								<li class="breadcrumb-item active" aria-current="page">Format : <span class="badge badge-primary"><?php echo $data['format']; ?></span></li>
								<li class="breadcrumb-item active" aria-current="page">Group : <span class="badge badge-primary"><?php 

								if (isset($data['group'])) {
									echo $data['group']; 
								} ?>

								</span></li>
								<li class="breadcrumb-item active" aria-current="page">Name : <span class="badge badge-primary"><?php 

								if (isset($data['name'])) {
									echo $data['name']; 
								} ?>
								</span></li>
								<li class="breadcrumb-item active" aria-current="page">Version : <span class="badge badge-primary"><?php

								if (isset($data['version'])) {
									echo $data['version']; 
								} ?>
									
								</span></li>
							</ol>
						</nav>
					</div>
					<!-- RESSOURCE -->
					<div class="col-6">
						<fieldset class="border p-3 mb-1">
							<legend class="w-auto">1. Ressource to Run : </legend>
							<div class="form-group">
								<p class="description">The resources to run is the package or compiled runnable file that you want to execute. Basically it's your job. It has a generated job name that you can edit. You can select the exact Nexus resource to run just bellow.</p>
								<label for="jobName">Name of the job : </label>
								<input type="text" required class="form-control" id="jobName" name="jobName" value="<?php 
								if (isset($data['version'])) {
									echo(str_replace("_","-",str_replace(".","-",str_replace("/","-",strtolower($data['name'])))."-".str_replace(".","-",strtolower($data['version'])))); 
								} else {
									echo(str_replace("_","-",str_replace(".","-",str_replace("/","-",strtolower($data['name']))))); 
								}
								?>">
							</div>
							<div class="form-group">
								<label for="ressourceToRun">Select Ressource to run : </label>
								<select class="form-control" id="ressourceToRun" name="ressourceToRun">
									<?php 

									if (isset($_POST['standalone'])) {
										echo('<option value="'.$data['downloadUrl'].'">'.end(preg_split('/\//',$data['downloadUrl'])).'</option>');
									} else {
										foreach ($data['assets'] as $key => $asset) {
											echo('<option value="'.$asset['downloadUrl'].'">'.end(preg_split('/\//',$asset['downloadUrl'])).'</option>');
										}
									}

									?>
								</select>
							</div>
						</fieldset>
					</div>
					<!-- RUNNER -->
					<div class="col-6">
						<fieldset class="border p-3 mb-1">
							<legend class="w-auto">2. Runner : </legend>
							<div class="form-group">
								<p class="description">The runner is the docker container in which your job will run. Be careful to choose a container that fits your executable requirements.</p>
								<label for="selectRunner">Select Runner :</label>
								<select class="form-control" id="selectRunner" name="selectRunner">
									<?php 
										foreach (getDockerImages()['projects'] as $project) {

											if(preg_match('/runner/', $project['name']) === 1) {

												$projectId = $project['id'];
												$repoId = getRegistries($projectId)[0]['id']; 
												$dockerImages = getTags($projectId,$repoId);
												
												foreach ($dockerImages as $image) {
													echo('<option value="'.$image['location'].'">'.$project['name'].'-'.str_replace(".","-",$image['name']).'</option>');
												}
											}
										}
									 ?>
								</select>
							</div>
						</fieldset>
						</div>
						<!-- CONFIGURE -->
						<div class="col-12">
							<fieldset class="border p-3 mb-1">
								<legend class="w-auto">3. Runner Configuration : </legend>
								<p class="description">The runner configuration is all the settings around the container. It lets you set up CRON scheduling, mounting points etc...</p>
								<div class="container">
									<div class="row">
										<div class="col-6">
											<fieldset class="border p-3 mb-1">
											<legend class="w-auto">3.1 Run Strategy</legend>
											<div class="form-group">
												<label for="runStrategy">Run Strategy :</label>
												<select class="form-control" id="runStrategy" name="runStrategy">
													<option>oneshot-running</option>
													<option>cron-recurrent-job</option>
												</select>
											</div>
											<div class="form-group">
												<label for="cronJobSchedule">If CRON Job set value See syntax <a href="https://crontab.guru/" target="_blank" >crontab</a> :</label>
												<input type="text" class="form-control" name="cronSchedule" placeholder="5 4 * * *">
											</div>
											</fieldset>
										</div>
										<div class="col-6">
											<fieldset class="border p-3 mb-1">
											<legend class="w-auto">3.2 CIFS</legend>
											<div class="form-group">
												<label for="switchmontagecifs">Montage CIFS :</label>
												<input type="checkbox" data-toggle="toggle-off" name="switchmontagecifs" id="switchmontagecifs">
											</div>
											<div class="form-group">
												<label for="CIFSendpoint">CIFS Endpoint :</label>
												<select class="form-control" id="runStrategy" name="CIFSendpoint">
													<?php 
													foreach ($cifsData as $value) {
														echo "<option>".$value['name']."</option>";
													}
													 ?>
												</select>
											</div>
											<div class="form-group">
												<label for="CIFSsourcePath">Source Path (on the container) : </label>
												<input type="text" class="form-control" id="CIFSsourcePath" name="CIFSsourcePath" placeholder="exemple : /data">
											</div>
											<div class="form-group">
												<label for="CIFSdest">Dest Path (on the remote server) : </label>
												<input type="text" class="form-control" id="CIFSdest" name="CIFSdest" placeholder="exemple : /Transverse/Direction_Data/Data_Services">
											</div>
											</fieldset>
										</div>
										<div class="col-6">
											<fieldset class="border p-3 mb-1">
											<legend class="w-auto">3.3 Configmap</legend>
											<div class="form-group">
												<label for="configName">Name :</label>
												<input type="text" class="form-control" id="configName" name="configName" placeholder="config">
												<label for="secretmap">Content of configmap :</label>
												<textarea class="form-control" id="secretmap" name="secretmap"  rows="5" cols="33"></textarea>
											</div>
											</fieldset>
										</div>
										<div class="col-6">
											<fieldset class="border p-3 mb-1">
											<legend class="w-auto">3.4 Arguments for Execution Context</legend>
											<div class="form-group">
												<label for="contextarguments">Arguments command :</label>
												<textarea class="form-control" id="contextarguments" name="contextarguments"  rows="5" cols="33"></textarea>
											</div>
											</fieldset>
										</div>
										<div class="col-6">
											<fieldset class="border p-3 mb-1">
											<legend class="w-auto">3.5 Proxy</legend>
											<div class="form-group">
												<label for="switchproxy">Use Proxy link to fetch packages or external content :</label>
												<input type="checkbox" data-toggle="toggle-off" name="switchproxy" id="switchproxy">
											</div>
											</fieldset>
										</div>
									</div>
								</div>
			            	</fieldset>
			            </div>
			            <!-- DEPLOY -->
			           	<div class="col-12">
							<fieldset class="border p-3 mb-1">
								<legend class="w-auto">4. Deploy : </legend>
								<p class="description">When executing the deployment,a Kubernetes resource will be created and all the configurations will be applied. It will then run the chosen container. In the container, it will mount the CIFS volume if configured and pull the Nexus resource. It will then execute the resource.</p>
								<input type="submit" name="submitJob" class="btn btn-primary btn-block" value="Execute the deployement">
				                <input type="hidden" name="value" value="<?php echo(urlencode(serialize($data))) ?>">
				                <?php 
					                if (isset($_POST['standalone'])) {
					                	echo(' <input type="hidden" name="standalone"  value="standalone"> ');
									}
				                ?>
							</fieldset>
			           	</div>
					</div>
				</div>
			</div>
		</form>
		<?php
	}

	include 'footer.php';

} else {
	include 'login.php';
}

?>