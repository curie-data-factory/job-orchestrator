<?php 

require __DIR__ . '/vendor/autoload.php';

session_start();

if (isset($_SESSION['connected']) && ($_SESSION['connected'] == True)) {
    $_SESSION['page'] = 1;
    include 'header.php';
    include 'core.php';

    if (isset($_POST['ressourceToRun'])) {
        $data = unserialize(urldecode($_POST['ressourceToRun']));
        $_SESSION['nexusRepository'] =  $data['name'];
    } 

    $urlRepository = NEXUS_API_URL."repositories";
    # On récupère la liste des répo sur Nexus
    $repoList = json_decode(getSSLPage($urlRepository),True);

    # on récupère l'url du répo à requêter
    if(!isset($_SESSION['nexusUrl'])) {
        $_SESSION['nexusUrl'] = NEXUS_API_URL;
    }
    # on récupère le répo à requêter
    if (!isset($_SESSION['nexusRepository'])) {
        $_SESSION['nexusRepository'] = NEXUS_DEFAULT_REPOSITORY;
    }

    // Affichage par défaut des datas
    $default = True;

    # On redémare la liste 
    if(isset($_POST['resetList'])) {
        unset($_SESSION['continuationToken']);
        $default = False;
    }

    # Si le bouton search est activé, on passe sur une requête de type search et on unset le continuationToken

    # URL a requêter : 
    if(!isset($_POST['searchNexusValue'])){
        $_POST['searchNexusValue'] = "";
    }

    $url = $_SESSION['nexusUrl']."search?q=".trim($_POST['searchNexusValue'])."&repository=".$_SESSION['nexusRepository'];
    unset($_SESSION['continuationToken']);


    # On GET le content
    $contents =  getSSLPage($url);

    # Si il y a du contenu
    if($contents !== false){

        # On décode le json
        $data = json_decode($contents,true);
        # On récupère le token de continuation
            $continuationToken = json_decode($contents,true)['continuationToken'];
            if($continuationToken != null) {
            # On met le token en session
             $_SESSION['continuationToken'] = $continuationToken;
        }
    }

    #######################
    # Formulaire pour selectionner les répo : 
    ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-6">
                <form action="#" method="post">
                    <div class="form-group">
                        <h2 class="pb-2">Select Repository :</h2>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1"><i class="far fa-folder"></i></span>
                            </div>
                            <select class="form-control" id="ressourceToRun" name="ressourceToRun" onChange="submit()">
                                <?php 
                                foreach ($repoList as $key => $asset) {
                                    if ($asset['name'] == $_SESSION['nexusRepository']) {
                                        echo('<option selected="selected" value="'.urlencode(serialize($asset)).'">'.$asset['name'].'</option>');
                                    } else {
                                        echo('<option value="'.urlencode(serialize($asset)).'">'.$asset['name'].'</option>');
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-6">
                <form action="#" method="post">
                    <div class="form-group">
                        <h2 class="pb-2"><?php echo $_SESSION['nexusRepository'] ?> : </h2>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="searchNexusValue" name="searchNexusValue" placeholder="Search Nexus artifact">
                            <div class="input-group-append">
                                <input class="btn btn-primary" type="submit" name="searchNexus" id="searchNexus" value="Search">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 
    ####################
    Page de selection des ressources à lancer : 
     -->
    <div class="container">
        <div class="row p-4" style="background-color: #f9f9f9;">
<!--             <div class="col-lg-12 mb-3">
                <form action="#" method="post">
                    <input class="btn btn-secondary" type="submit" name="resetList" id="resetList" value="Reset">
                    <input class="btn btn-primary" type="submit" name="nextList" id="nextList" value="Next Page">
                </form>
            </div> -->
            <div class="col-sm">
                <?php 

                    // on va modifer la structure des données pour pouvoir agréger les affichages
                    $dataReMap = array();
                    foreach ($data['items'] as $value) {
                        $dataReMap[$value['name']][$value['version']] = $value;
                    }

                    foreach ($dataReMap as $key => $value) {
                        ?>
                        <div class="card">
                            <div class="card-body">
                                <!-- On affice l'entête des ressources à partir du nom et du groupe -->
                                <h6 class="card-title"><?php echo($value[array_key_first($value)]['name']); ?></h6>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo($value[array_key_first($value)]['group']); ?></h6>

                                <?php 

                                foreach ($value as $itemData) {
                                    if($itemData["version"]) {
                                    ?>
                                        <form action="createjob.php" class="inline-form-button" method="post">
                                            <input type="hidden" name="value" value="<?php echo(urlencode(serialize($itemData))) ?>">
                                            <button type="submit" class="btn btn-outline-success">
                                              <i class="fas fa-play"></i> <?php echo $itemData["version"]; ?>
                                            </button>
                                        </form>
                                    <?php
                                    } else {
                                    ?>
                                        <form action="createjob.php" class="inline-form-button" method="post">
                                            <input type="hidden" name="value" value="<?php echo(urlencode(serialize($itemData))) ?>">
                                            <button type="submit" class="btn btn-outline-success">
                                              <i class="fas fa-play"></i> Run
                                            </button>
                                        </form>
                                    <?php
                                    }
                                }
                                
                                ?>
                                
                            </div>
                        </div>
                        <?php
                    }

                ?>
            </div>
        </div>
    </div>
    
<?php
    include 'footer.php';
} else {
    include 'login.php';
}

?>