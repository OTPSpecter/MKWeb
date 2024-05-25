<?php 
    session_start();
    require_once "../utils.php";

    $id_logement = $_GET['id'];       
    $sql = 'SELECT base_tarif, duree_min_res, delai_avant_res FROM sae._logement';
    $sql .= ' WHERE id = ' . $id_logement;
    $res = request($sql,1);           
    $base_tarif = $res['base_tarif'];
    $base_tarif = $res['base_tarif'];
    $min_jour = $res['duree_min_res'];
    $delai_res = $res['delai_avant_res'];;

    if (isset($_POST['acceptButton'])){
        client_connected_or_redirect();
        $dateDebut = $_POST['dateDebut'];
        $dateFin = $_POST['dateFin'];
        $prix_ht = $_POST['prix_ht'];
        $prix_ttc = $_POST['prix_ttc'];
        $nb_jours = $_POST['nb_jours'];
        $nb_nuit = $_POST['nb_nuit'];
        $taxe = $_POST['taxe'];
        $frais = $_POST['frais'];
        $nb_personne = $_POST['nombre_personnesDevis'];
       
        $reservation = array(
            'id_logement' => $id_logement,
            'id_client'=> client_connected(),
            'date_reservation'=> date('Y-m-d'),
            'date_debut'=>(new DateTime($dateDebut))->format('Y-m-d'),
            'date_fin'=> (new DateTime($dateFin))->format('Y-m-d'),
            'nb_occupant'=>$nb_personne,
            'taxe_sejour'=>$taxe,
            'taxe_commission'=>$frais,
            'prix_ht'=>$prix_ht,
            'prix_ttc'=>$prix_ttc,
            'date_annulation'=> NULL,
            'annulation'=> 0
        
        );

        //on revérifie si y'a pas une résa qui a été faite entre temps
        $sql = 'SELECT * FROM sae._reservation r';
        $sql .= ' WHERE r.id_logement = ' . $id_logement;
        $sql .= " AND ((r.date_debut >= '$dateDebut' AND r.date_debut < '$dateFin') OR (r.date_fin > '$dateDebut' AND r.date_fin <= '$dateFin'))";
        $ret = request($sql);
      
        if(count($ret) == 0){
            $id_resa = insert('sae._reservation', array_keys($reservation), array_values($reservation), 1);
        
            $resa_prix_par_nuit = array(
                'id_reservation'=>$id_resa,
                'prix'=> $base_tarif,
                'nb_nuit'=> $nb_nuit
            );
    
            insert('sae._reservation_prix_par_nuit', array_keys($resa_prix_par_nuit), array_values($resa_prix_par_nuit),0);
            header('Location: detail_reservation.php?id=' . $id_resa);
            die;

        }else{
            print <<<EOT
                <div style="display:flex;" id="date_resa" class="modal_cvg">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <p> Nous sommes désolée cette date n'est plus disponible </p>
                    </div>
                </div>
EOT;
            
        }
       

    }
    //error_reporting(E_ERROR); ini_set("display_errors", 1);
    
    $query = "SELECT sae._logement.id AS log_id, * FROM sae._logement 
    INNER JOIN sae._adresse ON sae._logement.id_adresse = sae._adresse.id 
    INNER JOIN sae._type_logement ON sae._logement.id_type = sae._type_logement.id 
    INNER JOIN sae._categorie_logement ON sae._logement.id_categorie = sae._categorie_logement.id
    WHERE sae._logement.id ='$id_logement';"; 

    $query_photo = "SELECT src, principale, alt FROM sae._image
    INNER JOIN sae._logement ON sae._image.id_logement = sae._logement.id
    WHERE sae._logement.id = $id_logement;";

    $rep_logement = request($query, true);
    if (!$rep_logement || !isset($rep_logement)){
        header('Location: index.php');
        die;
    }

    $query_note = "SELECT avg(note), count(*) from sae._avis where id_logement = $id_logement;";
    $query_amenagement = "SELECT amenagement FROM sae._amenagement_logement INNER JOIN sae._amenagement ON sae._amenagement_logement.id_amenagement = sae._amenagement.id  WHERE sae._amenagement_logement.id_logement = $id_logement;";
    $query_hote = "select prenom, nom, photo_profile from sae._utilisateur inner join sae._logement on sae._utilisateur.id = sae._logement.id_proprietaire where sae._logement.id = $id_logement;";
    $query_langue = "select langue from sae._utilisateur 
    inner join sae._langue_proprietaire on sae._utilisateur.id = sae._langue_proprietaire.id_proprietaire 
    inner join sae._langue on sae._langue_proprietaire.id_langue = sae._langue.id
    inner join sae._logement on sae._logement.id_proprietaire = sae._utilisateur.id
    where sae._logement.id =$id_logement;";
    $query_activite = "select activite, perimetre from sae._activite_logement 
    inner join sae._logement on sae._activite_logement.id_logement = sae._logement.id  
    inner join sae._distance on sae._activite_logement.id_distance = sae._distance.id
    where sae._logement.id = $id_logement;";
    $query_avis = "select commentaire, note, prenom, commune, pays from sae._avis 
    inner join sae._utilisateur on sae._avis.id_client = sae._utilisateur.id 
    inner join sae._adresse on sae._adresse.id = sae._utilisateur.id_adresse
    where sae._avis.id_logement =$id_logement;";
    $query_note_hote = "SELECT AVG(sae._avis.note) 
    FROM sae._avis 
    INNER JOIN sae._logement ON sae._avis.id_logement = sae._logement.id  
    WHERE sae._logement.id_proprietaire = (
        SELECT sae._logement.id_proprietaire 
        FROM sae._logement 
        WHERE sae._logement.id = $id_logement
    );";
    $rep_note = request($query_note)[0];
    $rep_amenagement = request($query_amenagement);
    $rep_hote = request($query_hote)[0];
    $rep_langue = request($query_langue);
    $rep_activite = request($query_activite);
    $rep_avis = request($query_avis);
    $rep_note_hote = request($query_note_hote)[0];
    $rep_photo = request($query_photo);

    $titre_logement =  $rep_logement['titre'] ;
    $moyenne_note = $rep_note['avg'];
    if (isset($moyenne_note)) {
        $moyenne_note = round($moyenne_note, 1);
    }
    

    $ville = $rep_logement['commune'];
    $departement = $rep_logement['departement'];
    $accroche = $rep_logement['accroche'];
    $categorie = $rep_logement['categorie'];
    $type = $rep_logement['type'];
    $surface = $rep_logement['surface'];
    $nb_personne = $rep_logement['nb_max_personne'];
    $nb_chambre = $rep_logement['nb_chambre'];
    $nb_lit_simple =  $rep_logement['nb_lit_simple'];
    $nb_lit_double = $rep_logement['nb_lit_double'];
    $nb_commentaire = $rep_note['count'];
    $description = $rep_logement['description'];
    $nom_hote = $rep_hote['nom'];
    $prenom_hote = $rep_hote['prenom'];
    $note_hote = $rep_note_hote['avg'];
    if (isset($note_hote)) {
        $note_hote = round($note_hote, 1);
    }
    $source = $rep_hote['photo_profile'];

    $liste_amenagement = [];
    foreach($rep_amenagement as $cle => $amenagements){
        foreach($amenagements as $cle => $amenagement){
            $liste_amenagement[] = $amenagement;
        }
    };
    

    $liste_langue = [];
    foreach($rep_langue as $cle => $langues){
        foreach($langues as $cle => $langue){
            $liste_langue[] = $langue;
        }
    }

    $liste_activite = [];
    foreach($rep_activite as $cle => $activite){
        $liste_activite[$activite['activite']] = $activite['perimetre'] ;
    }

    $liste_avis = "";
    foreach($rep_avis as $cle => $avis){
        
        if ($cle > 0) {
            $liste_avis = $liste_avis  . "<br>";
        }
        $liste_avis = $liste_avis . $avis['prenom'] . ", " . $avis['commune'] .', ' . $avis['pays'] .', ' .$avis['note'] .', ' . $avis['commentaire'];
    }


    print <<<EOT
    <script>
        const JOUR_MIN = {$min_jour};
        const DELAI_RES = {$delai_res};
        const NB_VOY = {$nb_personne};
    </script>

    EOT;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/logement.css">
    <title><?= $titre_logement?></title>
    <script src="https://kit.fontawesome.com/7f17ac2dfc.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body>
    <div class="wrapper">
        <?php     include "header.php";?>
        <main class="main">
            <div class="main__container logement">
                <div class="logement__top">
                    <div class="logement__nom">
                        <div class="nom">
                            <h1 id="logement__nom"><?php echo  $titre_logement?></h1>
                            <div class="stars" id="logement__rate">
                                <i class="fas fa-star fa-lg" id="1star"></i>
                                <!-- <i class="fas fa-star fa-lg" id="2star"></i>
                                <i class="fas fa-star fa-lg" id="3star"></i>
                                <i class="fas fa-star fa-lg" id="4star"></i>
                                <i class="fas fa-star fa-lg" id="5star"></i> -->
                            </div>
                            <h6 id="logement__rate__valuernote"><?php echo  $moyenne_note?></h6>
                            <a class="retour" href="index.php" id="logement__retour"><img src="img/back.webp" alt="Retour"></a>
                        </div>
                        <div class="partager">
                            <img src="img/share.webp" alt="Partager">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=https://mkweb.ventsdouest.dev/detail_logement.php?id=<?= $id_logement?>" target="_blank">Partager</a>
                        </div>
                    </div>
                    <div class="logement__adr">
                        <h2 id="logement__adresse"><?php echo  $ville . ", " . $departement?></h2>
                        <a href="#logement__reserver">Réserver</a>
                    </div>
                </div>
                <div class="logement__photos">
                    <div class="photo__grille" id="logement__photo__grille">
                        <?php foreach($rep_photo as $photo) {?>
                            <img src="./img<?=$photo['src']?>" alt="<?=$photo['alt']?>">
                        <?php } ?>
                    </div>
                </div>
                <div class="logement-container">
                    <div class="logement__details">
                        <div class="details__top">
                            <div class="details__nom"><h2 id="log__nom"><?php echo  $titre_logement?></h2> <h2 id="log__details"><?php echo  $accroche?></h2></div>
                            <div class="details__features" id="features">
                                <div class="feature"><?= $categorie?></div>
                                <div class="feature"><?php echo  $type?></div>
                                <div class="feature"><?php echo  $surface?> m²</div>
                                <?php if ($nb_personne == 1) { ?>
                                    <div class="feature">1 voyageur</div>
                                <?php } else if ($nb_personne > 1) { ?>
                                    <div class="feature"><?php echo  $nb_personne?> voyageurs</div>
                                <?php } ?>
                                <div class="feature"><?php echo  $nb_chambre?> chambres</div>
                                <?php if ((!empty($nb_lit_simple)) && ($nb_lit_simple > 1)) { ?>
                                    <div class="feature"><?php echo  $nb_lit_simple?> lits simples</div>
                                <?php } else if ((!empty($nb_lit_simple)) && ($nb_lit_simple == 1)) { ?>
                                    <div class="feature">1 lit simple</div>
                                <?php } ?>
                                <?php if ((!empty($nb_lit_double)) && ($nb_lit_double > 1)) { ?>
                                    <div class="feature"><?php echo  $nb_lit_double?> lits simples</div>
                                <?php } else if ((!empty($nb_lit_double)) && ($nb_lit_double == 1)) { ?>
                                    <div class="feature">1 lit double</div>
                                <?php } ?>
                                
                                
                            </div>
                            <h3>Ce logement vous propose</h3>
                            <div class="logement__proposNote">
                                <?php if (empty($liste_amenagement)) { ?> 
                                    <div class="proposition">Ce logement ne propose aucun aménagement</div>
                                <?php } else { ?>
                                    <div class="logement__propose">
                                        <ul>
                                            <?php foreach($liste_amenagement as $a) { ?>
                                                <li class="proposition"><?php echo $a ?></li>
                                            <?php } ?>
                                        </ul>
                                        <a href="">Conditions d’annulation</a>
                                    </div>
                                <?php }?>  
                                <div class="logement__note">
                                    <h2 id="note"><?php echo  $moyenne_note?> / 5</h2>
                                    <div class="details__stars" id="logement__rate__details">
                                        <i class="fas fa-star fa-lg" id="1star_details"></i>
                                        <!-- <i class="fas fa-star fa-lg" id="2star_details"></i>
                                        <i class="fas fa-star fa-lg" id="3star_details"></i>
                                        <i class="fas fa-star fa-lg" id="4star_details"></i>
                                        <i class="fas fa-star fa-lg" id="5star_details"></i> -->
                                    </div>
                                    <?php 
                                        if($nb_commentaire == 1){?>
                                            <a href=""><span id="nb_comment"></span>1 Commentaire</a>
                                        <?php } else if ($nb_commentaire > 1) { ?>
                                            <a href=""><span id="nb_comment"><?php echo  $nb_commentaire . " "?></span>Commentaires</a>
                                        <?php } else { ?>
                                            <p><span id="nb_comment">Aucun commentaire</span><p>
                                        <?php }
                                    ?>
                                    

                                </div>

                            </div>
                        </div>
                        <div class="apropos">
                            <h3>À propos de ce logement</h3>
                            <p id="logement__descipt">
                                <?php echo  $description?>
                            </p>
                            <button id="decouvrir">Découvrir plus</button>
                        </div>
                        <div class="hote">
                            <div class="hote__info">
                                <img src="img/<?php echo $source?>" alt="Hôte" id="hote__photo">
                                <div class="hote__main">
                                    <div class="hote__nom">
                                        <h3>Hôte: <span id="hote__nm"><?php echo  $prenom_hote?></span></h3>
                                        <div class="hote_rate" id="hote__rate">
                                            <i class="fas fa-star fa-xs" ></i>
                                            <!-- <i class="fas fa-star fa-xs" ></i>
                                            <i class="fas fa-star fa-xs" ></i>
                                            <i class="fas fa-star fa-xs" ></i>
                                            <i class="fas fa-star fa-xs" ></i> -->
                                        </div>
                                        <h6 id="hote__valuernote"><?= $note_hote?></h6>
                                    </div>
                                    <div class="hote__langues">
                                        <i class="fa-solid fa-earth-americas" style="color: #222222;"></i>
                                        <ul>
                                            <?php foreach($liste_langue as $l) { ?>
                                                <li class="proposition"><?php echo $l ?></li>
                                            <?php } ?> 
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="asavoir">
                                <h2>À savoir</h2>
                                <a href="">Conditions de séjour dans сe logement</a>
                                <h3>Moyens de paiement acceptés : PayPal, Carte bancaire</h3>
                            </div>
                        </div>
                        <div class="environs">
                            
                            <div class="environs__map" id="environs__map">
                                <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
                            </div> 
                            <script>
                                var ville = "<?php echo $ville; ?>";
                                var opencageUrl = "https://api.opencagedata.com/geocode/v1/json?q=" + encodeURIComponent(ville) + "&key=90a3f846aa9e490d927a787facf78c7e";
                                console.log(ville);
                                fetch(opencageUrl)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.results.length > 0) {
                                            afficherCommuneSurMap(data.results[0].geometry.lat, data.results[0].geometry.lng);
                                            console.log(data);
                                        } else {
                                            console.error("La ville à afficher n'est pas valide.");
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Erreur lors de la requête de géocodage:", error);
                                    });

                                function afficherCommuneSurMap(lat, lng) {
                                    var map = L.map('environs__map').setView([lat, lng], 9); 
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                        attribution: '© OpenStreetMap contributors'
                                    }).addTo(map);

                                    L.marker([lat, lng]).addTo(map).bindPopup('Le logement est ici !');
                                }
                            </script>
                            <div class="environs__details">
                                <h3>Environs de l'établissement</h3>
                                <?php if (empty($liste_activite)) { ?> 
                                    <div class="environs__ligne">Il n'y a rien à proxmité.</div>
                                <?php } else { ?>
                                    <?php foreach($liste_activite as $act => $distance) { ?>
                                        <div class="environs__ligne">
                                            <p class="environ"><?php echo $act?></p>
                                            <p class="dest"><?php echo $distance?></p>
                                        </div>
                                    <?php } ?>
                                <?php }?>  
                                
                            </div>
                        </div>
                    </div>
                  
                    
            
                    
                    <div class="logement__res" id="logement__reserver">
                        <div class="form__logement">
                            <h2><span  id="logement__prix"><?=$base_tarif?></span> € par  nuit</h2>
                            <h1>Indiquez les dates pour voir les tarifs</h1>
                            <form action="" method="post">
                            <div id="myModal_cvg" class="modal_cvg">
                                <div class="modal-content">
                                    <span class="close">&times;</span>
                                    <div class="accept_cvg">
                                    <p>Je reconnais avoir pris connaissance et j'accepte <a href="/documents_pdf/CGV_CGU.pdf" target="_blank">les conditions générales de ventes</a></p>
                                        <div class="button_cvg">
                                            <input type="button" id="declineButton" value="Refuser">
                                            <input type="submit" name="acceptButton" id="acceptButton" value="Accepter">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="res__fromulaire">
                                
                                    <input type="text" name="dateDebut" hidden>
                                    <input type="text" name="dateFin" hidden>

                                    <input type="text" name="prix_ht"  hidden>
                                    <input type="text" name="prix_ttc" hidden>
                                    <input type="text" name="nb_jours" hidden>
                                    <input type="text" name="nb_nuit" hidden>
                                    <input type="text" name="taxe" hidden>
                                    <input type="text" name="frais" hidden>
                                    
                                    <div id="container-calendar">
                                    <div id="error_periode">Une réservation doit être supérieur à <?=$min_jour?> jours</div>
                                        <h3>Arrivée - Départ</h3>
                                        <div class="calendar-nav">
                                            <button type="button" class="calendar-btn" id="prev">&lt;</button>
                                            <span class="calendar-month" id="month"></span>
                                            <button type="button" class="calendar-btn" id="next">&gt;</button>
                                        </div>
                                        <table id="calendar"></table>
                                    </div>
                                
                                <div class="res__voy">
                                    <label for="nb_personnesDevis">Voyageurs</label>
                                    <input type="number" id="nb_personnesDevis" placeholder="1 voyageur" name="nombre_personnesDevis" min="1" max="13" required>
                                </div>
                                

                            </div>

                            <div id="appear_calcul" style="display:none">
                                <div class="logement__calcules" >
                                    <div class="calcules__ligne">
                                        <div class="ttc__jours">
                                            <p id="prix__TTC" class="calcules__under"></p>
                                            <p class="calcules__under">€  x</p>
                                            <p id="nb_jours" class="calcules__under"></p>
                                            <p class="calcules__under">jours</p>
                                        </div>
                                        <div class="ttc_prix">
                                            <p id="prix__total"></p>
                                            <p>€  HT</p>
                                        </div>
                                    </div>
                                    <div class="calcules__ligne">
                                        <p class="calcules__under">Frais</p>
                                        <div class="frais">
                                            <p id="frais__total"> </p>
                                            <p>€</p>
                                        </div>
                                    </div>
                                    <div class="calcules__ligne">
                                        <p class="calcules__under">Taxes</p>
                                        <div class="frais">
                                            <p id="taxes__total"></p>
                                            <p>€</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="logement__total-ttc">
                                    <p>Total TTC</p>
                                    <p><span id="tot-ttc"></span>€</p>
                                </div>
                                <!--<input type="submit" id="reset" value="Annuler"> -->
                                <input type="button" name="submit_resa" id="submit_resa" value="Réserver">
                                
                            </div>
                        </form>
                        </div>
                    </div>
            </div>
            </div>
        </main>
        <?php include_once 'footer.php'; ?>
    </div>
    
    
    <script src="js/logement.js"></script>
</body>
</html>