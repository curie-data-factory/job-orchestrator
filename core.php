<?php 

require_once 'conf/conf.php';

# Fonction qui requête l'API de GITLAB pour récupérer les projets dans le groupe configuré
function getDockerImages()
{
	$url = GITLAB_API_URL."groups/".GITLAB_GROUP_PROJET;
    $ch = curl_init($url);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Private-Token:'.GITLAB_API_ACCESS_TOKEN)); 

	// Retourner le contenu téléchargé dans une chaine (au lieu de l'afficher directement)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Exécution de la requête
	$server_output = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($server_output,true);
	return $data;
}

# get registries of a gitlab project
function getRegistries($projectId)
{	
	$url = GITLAB_API_URL."projects/".$projectId."/registry/repositories";
    $ch = curl_init($url);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Private-Token:'.GITLAB_API_ACCESS_TOKEN)); 

	// Retourner le contenu téléchargé dans une chaine (au lieu de l'afficher directement)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Exécution de la requête
	$server_output = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($server_output,true);
	return $data;
}

# get registries tags of a gitlab project
function getTags($projectId,$repoId)
{	
	$url = GITLAB_API_URL."projects/".$projectId."/registry/repositories/".$repoId."/tags";
    $ch = curl_init($url);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Private-Token:'.GITLAB_API_ACCESS_TOKEN)); 

	// Retourner le contenu téléchargé dans une chaine (au lieu de l'afficher directement)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Exécution de la requête
	$server_output = curl_exec($ch);
	curl_close($ch);
	$data = json_decode($server_output,true);
	return $data;
}

# Fonction qui permet de requêter une page en SSL
function getSSLPage($url) {

    $ch = curl_init($url);

	//Set to simple authentification
	curl_setopt($ch, CURLOPT_USERPWD, NEXUS_USER . ":" . NEXUS_PASSWORD);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function displayLine($string)
{
	var_dump($string);	
}

# Fonction qui requête le NEXUS et renvoit le résultat de la requête
function nexusSearch($string)
{
	$url = $this->$nexusUrl+"search?repository="+$this->$nexusRepository+"&q="+$string;
	$contents = file_get_contents($url);
	# Si il y a du contenu
	if($contents !== false){
	    # On décode le json
	    $data = json_decode($contents,true);
	    return $data;
	}
}

# Fonction qui requète l'API de Kubernetes pour récupérer les pods qui tournent sur le namespace configuré
function getPods()
{
	$url = str_replace("v3","k8s",KUBERNETES_API_URL).'/api/v1/namespaces/'.KUBERNETES_NAMESPACE.'/pods/';

	//API IDs
	$ACCESS_KEY = KUBERNETES_ACCESS_KEY;
	$ACCESS_SECRET = KUBERNETES_ACCESS_SECRET;

	//Initiate cURL.
	$ch = curl_init($url);

	//Set to simple authentification
	curl_setopt($ch, CURLOPT_USERPWD, $ACCESS_KEY . ":" . $ACCESS_SECRET);

	//Set the content type to application/json
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json','Content-Type: application/json')); 

	// Retourner le contenu téléchargé dans une chaine (au lieu de l'afficher directement)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Exécution de la requête
	$server_output = curl_exec($ch);
	curl_close($ch);

	return $server_output;
}

# Fonction qui récupère les logs de l'url API Kubernetes d'un pod 
function getPodLogs($url)
{
	//API IDs
	$ACCESS_KEY = KUBERNETES_ACCESS_KEY;
	$ACCESS_SECRET = KUBERNETES_ACCESS_SECRET;

	//Initiate cURL.
	$ch = curl_init($url);

	//Set to simple authentification
	curl_setopt($ch, CURLOPT_USERPWD, $ACCESS_KEY . ":" . $ACCESS_SECRET);

	//Set the content type to application/json
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9','Content-Type: text/plain')); 

	// Retourner le contenu téléchargé dans une chaine (au lieu de l'afficher directement)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Exécution de la requête
	$server_output = curl_exec($ch);
	curl_close($ch);

	echo $server_output;
}

# Fonction qui requête l'API Rancher pour récupérer les Workloads qui tournent sur le namespace configuré
function getWorkloads()
{
	// API URL 
	$url = str_replace("clusters","project",KUBERNETES_API_URL).':'.KUBERNETES_PROJECT_KEY.'/workloads/?namespaceId='.KUBERNETES_NAMESPACE;

	//API IDs
	$ACCESS_KEY = KUBERNETES_ACCESS_KEY;
	$ACCESS_SECRET = KUBERNETES_ACCESS_SECRET;

	//Initiate cURL.
	$ch = curl_init($url);

	//Set to simple authentification
	curl_setopt($ch, CURLOPT_USERPWD, $ACCESS_KEY . ":" . $ACCESS_SECRET);

	//Set the content type to application/json
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json','Content-Type: application/json')); 

	// Retourner le contenu téléchargé dans une chaine (au lieu de l'afficher directement)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Exécution de la requête
	$server_output = curl_exec($ch);
	curl_close($ch);

	return $server_output;
}

// delete a workload
function deleteWorkload($name,$type)
{

	$url = str_replace("clusters","project",KUBERNETES_API_URL).':'.KUBERNETES_PROJECT_KEY.'/workloads/'.strtolower($type).':'.KUBERNETES_NAMESPACE.':'.$name;

	//API IDs
	$ACCESS_KEY = KUBERNETES_ACCESS_KEY;
	$ACCESS_SECRET = KUBERNETES_ACCESS_SECRET;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERPWD, $ACCESS_KEY . ":" . $ACCESS_SECRET);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $result = json_decode($result);
    curl_close($ch);

    return $result;
}

# Fonction qui prend un JSON qui continent les caractéristiques d'un déploiement
function executeDeployement($jsonData)
{
	//API Url
	$url = KUBERNETES_API_URL.'?action=importYaml';

	//API IDs
	$ACCESS_KEY = KUBERNETES_ACCESS_KEY;
	$ACCESS_SECRET = KUBERNETES_ACCESS_SECRET;

	//Initiate cURL.
	$ch = curl_init($url);

	//Set to simple authentification
	curl_setopt($ch, CURLOPT_USERPWD, $ACCESS_KEY . ":" . $ACCESS_SECRET);

	//Set the content type to application/json
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json','Content-Type: application/json')); 

	//Tell cURL that we want to send a POST request.
	curl_setopt($ch, CURLOPT_POST, 1);

	//Attach our encoded JSON string to the POST fields.
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

	// Receive server response ...
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Exécution de la requête
	$server_output = curl_exec($ch);
	curl_close($ch);

	return $server_output;
}

# Cette fonction écrit la représentation en DAG airflow du job executé par joborchestrator
function scribe($workload)
{
	# Initializer
	$papyrus = "";

	# Ajout des variables d'env
	$papyrus .= "env = Variable.get(\"process_env\")\n";
	$papyrus .= "namespace = Variable.get(\"namespace\")\n";
	$papyrus .= "nexus_user = Variable.get(\"nexus_user\")\n";
	$papyrus .= "nexus_password = Variable.get(\"nexus_password\")\n";

	# Ajout de la task id :
	$taskid = str_replace("-","", $workload['name']);

	# Definition de la tache : 
	$papyrus .= $taskid." = KubernetesPodOperator(namespace=namespace,\n                             ";
	$papyrus .= "task_id=\"".$taskid."\",\n                             ";
	$papyrus .= "name=\"".$workload['name']."\",\n                             ";

	# Ajout de l'image du container : 
	$papyrus .= "image=\"".$workload['containers'][0]['image']."\",\n                             ";

	# Ajout des variables d'environnement : 
	$papyrus .= "env_vars={";

	$privileged = FALSE;

	foreach ($workload['containers'][0]['environment'] as $key => $value) {
		switch ($key) {
			case 'CIFSPASSWORD':
				$papyrus .= "'CIFSPASSWORD':cifs_password,";
				$privileged = TRUE;
				break;
			
			default:
				$papyrus .= "'$key':'$value',";
				break;
		}
	}

	# Ajout de la conf statique
	$papyrus .= "'env':env,";
	$papyrus .= "'namespace':namespace,";
	$papyrus .= "'USERNEXUS':nexus_user,";
	$papyrus .= "'PASSWORDNEXUS':nexus_password,";

	$papyrus = substr($papyrus, 0, -1);
	$papyrus .= "},\n                             ";

	# privileged pod if cifs mount
	if($privileged){
		$papyrus .= "full_pod_spec=k8s.V1Pod(metadata=k8s.V1ObjectMeta(name=\"".$taskid."\"),spec=k8s.V1PodSpec(containers=[k8s.V1Container(name=\"base\",security_context=k8s.V1SecurityContext(allow_privilege_escalation=True,privileged=True))])),\n                             ";
	}

	# Finalizer 
	$papyrus .= "image_pull_secrets=\"registrygitlab-curie\",\n                             is_delete_operator_pod=True,\n                             get_logs=True,\n                             dag=dag)";
	return $papyrus;
}

?>