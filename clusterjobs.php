<?php

require __DIR__ . '/vendor/autoload.php';

session_start();

if (isset($_SESSION['connected']) && ($_SESSION['connected'] == True)) {
	$_SESSION['page'] = 2;
	include 'header.php';
	include 'core.php';

	// Récupération de la liste des pods
	$data = json_decode(getPods(),True);
	if (isset($data['items'])) {
		$data = $data['items'];
		$listPods = array();
		if (!empty($data)) {
			foreach ($data as $pod) {
				$listPods = array_merge($listPods,array($pod['status']['containerStatuses'][0]['name'] => array('podName' => $pod['metadata']['name'], 'podStatus' => $pod['status']['containerStatuses'][0]['state'])));
			}
		}
	}

	?>

	<div class="container-fluid">
		<?php 
		if (isset($_GET['podLogs'])) {
			$podId = @$listPods[$_GET['podLogs']]['podName'];
			$url = str_replace("v3","k8s",KUBERNETES_API_URL).'/api/v1/namespaces/'.KUBERNETES_NAMESPACE.'/pods/'.$podId.'/log';
		?>
		<div class="row">
			<div class="col-12 mt-3">
				<a class="btn btn-secondary" href="clusterjobs.php" style="float:right;">X Close</a><h3><?php echo $_GET['podLogs'] ?> logs :</h3>
			</div>
			<div class="col-12">
				<div id="console">
					<p><?php getPodLogs($url); ?></p>
				</div>
			</div>
		</div>

	<?php }

		if (isset($_GET['deleteWorkload'])) {
			deleteWorkload($_GET['deleteWorkload'],$_GET['type']);
		}
	 ?>
		<div class="row">
			<div class="col-12">
				<div class="tab-pane" id="data_ci">
					<form action="data_ci.php" method="post">
						<div id="data_ci_content" class="m-4">
							<h3 id="datetimejs"></h3>
							<table class="table table-striped" id="jobs">
								<thead>
									<tr>
										<th>Created</th>
										<th>Completed</th>
										<th>Status</th>
										<th>State</th>
										<th>Name</th>
										<th>Image</th>
										<th>Job</th>
										<th>Type</th>
										<th>Schedule</th>
										<th>LastRun</th>
										<th>Pods</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
								<?php 

									function printStatus($value)
									{
										if ($value == "active") {
											return('<span class="badge badge-success">'.$value.'</span>');
										} else {
											return('<span class="badge badge-danger">'.$value.'</span>');
										}
									}

									function printState($value)
									{
										$val = @array_key_first($value);
										if ($val == "running") {
											return('<span class="badge badge-success">'.$val.'</span>');
										} else if($val == "terminated") {
											$val = array_pop($value)['reason'];
											if($val == "Completed") {
												return('<span class="badge badge-success">'.$val.'</span>');
											} else {
												return('<span class="badge badge-danger">'.$val.'</span>');
											}
										} else {
											return('<span class="badge badge-danger">'.$val.'</span>');
										}
									}

									function printType($value)
									{
										if ($value == "cronJob") {
											return('<span class="badge badge-primary">'.$value.'</span>');
										} else {
											return('<span class="badge badge-secondary">'.$value.'</span>');
										}
									}

									function printModal($workload)
									{
										return('	<div class="modal" id="'.$workload['name'].'" tabindex="-1" role="dialog">
													  <div class="modal-dialog" role="document">
													    <div class="modal-content">
													      <div class="modal-header">
													        <h5 class="modal-title">Delete Job ?</h5>
													        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
													          <span aria-hidden="true">&times;</span>
													        </button>
													      </div>
													      <div class="modal-body">
													        <p>Voulez-vous supprimer le workload <b>'.$workload['name'].'</b> ?</p>
													      </div>
													      <div class="modal-footer">
													      	<a class="btn btn-danger" href="clusterjobs.php?deleteWorkload='.$workload['name'].'&type='.$workload['type'].'">Yes</a>
													        <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
													      </div>
													    </div>
													  </div>
													</div>');
									}


									function printDagModal($workload)
									{
										return('	<div class="modal" id="'.$workload['name'].'-dag-modal" tabindex="-1" role="dialog">
													  <div class="modal-dialog" style="min-width:800px;"  role="document">
													    <div class="modal-content">
													      <div class="modal-header">
													        <h5 class="modal-title">Airflow KubernetesPodOperator View</h5>
													        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
													          <span aria-hidden="true">&times;</span>
													        </button>
													      </div>
													      <div class="modal-body">
													      Attention à bien importer :
													      <div class="alert alert-primary" role="alert">
															  from airflow.contrib.kubernetes import secret<br/>from airflow.contrib.operators import kubernetes_pod_operator</div>
													          <div class="form-group">
															    <label for="dagairflowkubernetespodoperator">KubernetesPodOperator representation of the job :</label>
															    <textarea disabled class="form-control" id="dagairflowkubernetespodoperator" rows="15">'.scribe($workload).'</textarea>
															  </div>
													      </div>
													      <div class="modal-footer">
													        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
													      </div>
													    </div>
													  </div>
													</div>');
									}
									
									function printActions($workload)
									{
										return(printDagModal($workload).'<button data-toggle="modal" data-target="#'.$workload['name'].'-dag-modal" type="button" class="btn btn-info"><i class="fas fa-asterisk"></i></button>'.printModal($workload).'<button data-toggle="modal" data-target="#'.$workload['name'].'" type="button" class="btn btn-danger"><i class="fa fa-trash" aria-hidden="true"></i></button>');
									}

									function printPods($workload)
									{
										return('<a class="btn btn-primary" href="?podLogs='.$workload['name'].'">Logs</a>');
									}

									foreach (json_decode(getWorkloads(),True)['data'] as $workload) {
										?>
										<tr>
											<td><?php echo(str_replace(["T","Z"]," ",$workload['created'])); ?></td>
											<td><?php 
											if(isset($workload['jobStatus']['completionTime'])) {echo(str_replace(["T","Z"]," ",$workload['jobStatus']['completionTime'])); } ?></td>
											<td><?php echo(printStatus($workload['state'])); ?></td>
											<td><?php echo(printState($listPods[$workload['name']]['podStatus'])); ?></td>
											<td><?php echo('<a href="'.$workload['links']['self'].'">'.$workload['name'].'</a>'); ?></td>
											<td><?php $arr = preg_split("/\//",$workload['containers'][0]['image']); echo(end($arr)); ?></td>
											<td><?php $arr = @preg_split("/\//",$workload['containers'][0]['environment']['JOBTORUN']); echo(end($arr)); ?></td>
											<td><?php echo(printType($workload['type'])); ?></td>
											<td><?php echo(@$workload['cronJobConfig']['schedule']); ?></td>
											<td><?php echo(@str_replace(["T","Z"]," ",$workload['cronJobStatus']['lastScheduleTime'])); ?></td>
											<td><?php echo(printPods($workload)); ?></td>
											<td><?php echo(printActions($workload)); ?></td>
										</tr>
										<?php
									}
								?>
								</tbody>
							</table>
						</div>
					</form>
				</div>

			</div>
		</div>
	</div>
	<script type="text/javascript">

		setInterval("horloge()", 1000);
		var boite = document.querySelector('#datetimejs');
		function horloge() 
		{
			var heure =new Date();
			boite.textContent = "Time " + heure.getFullYear() +"-" + heure.getMonth() + "-" + heure.getDate() + " "+ heure.getHours()+":"+ heure.getMinutes()+":"+ heure.getSeconds();
		}

		$(document).ready(function() {

			horloge();

			$("#jobs").dataTable( {
				"iDisplayLength": 25,
				"order": [[ 0, "desc" ]],
			} );

		} );
	</script>
	<?php

	include 'footer.php';

} else {
	include 'login.php';
}

?>
