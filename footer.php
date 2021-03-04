	<script type="text/javascript" src="js/popper.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/all.min.js"></script>
	<script type="text/javascript" src="js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="js/dataTables.bootstrap4.min.js"></script>
<div class="container">
	<div class="row">
		<div class="col-12 mt-1">
			<footer>
				<br/>
				<p>
					Crédits : Data Factory / Direction des données / Institut Curie - <?php 
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
</body>