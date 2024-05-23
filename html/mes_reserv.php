<?php
session_start();
require_once "../utils.php";
$id = client_connected_or_redirect();
$query = "SELECT sae._reservation.id, sae._reservation.id_logement, sae._reservation.date_annulation ,sae._reservation.prix_ttc ,sae._logement.titre, sae._reservation.date_debut, sae._reservation.date_fin, sae._adresse.commune, img.*
FROM sae._reservation 
INNER JOIN sae._logement ON sae._reservation.id_logement = sae._logement.id
INNER JOIN sae._adresse ON sae._logement.id = sae._adresse.id
INNER JOIN sae._image img ON sae._reservation.id_logement = img.id_logement AND img.principale = true
WHERE id_client = $id";
$results = request($query, false);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/logement.css">
    <link rel="stylesheet" href="css/mes_reserv.css">
    <title>Mes réservations</title>
    <script src="https://kit.fontawesome.com/7f17ac2dfc.js" crossorigin="anonymous"></script>
</head>

<body class="page">
    <div class="wrapper">
        <?php require_once 'header.php'; ?>
        <main class="main">
            <div class="main__container reserv">
                <div class="mes__reserv__titre">
                    <div class="test__TEST">
                        <h1>Mes réservations</h1>
                        <img src="img/filter-3.webp" alt="">
                        <img src="img/arrows.webp" alt="">
                    </div>
                    <!-- <a href="#" id="export-reservation-btn"><img src="img/downloads.webp" alt="Download"></a> -->
                </div>
                <?php if (empty($results)) { ?>
                    <div class="mes__reserv__empty">
                        <h4>Vous n'avez pas encore de réservations</h4>
                    </div>
                <?php } else {
                    foreach ($results as $result) { ?>
                        <a href="detail_reservation.php?id=<?php echo $result["id_logement"] ?>">
                            <div class="card__reserv">
                                <img src=<?= "img/".$result["src"]?> alt=<?=$result["alt"]?>>
                                <div class="mes__reserv__cont_desc_prix">
                                    <div class="mes__reserv__description">
                                        <h4><?php echo $result["titre"] ?></h4>
                                        <div class="mes_reserv__numero">
                                            <h4>Numéro de réservation : </h4>
                                            <h4><?php echo $result["id"] ?></h4>
                                        </div>
                                        <div class="mes__reserv__date">
                                            <h4><?php echo $result["date_debut"] ?> – <?php echo $result["date_fin"] ?></h4>
                                            <i class="fa-solid fa-circle" style="color: #222222;"></i>
                                            <h4><?php echo $result["commune"] ?></h4>
                                        </div>
                                        <?php if ($result["date_annulation"] == null) { ?>
                                            <p class="green">Confirmé</p>
                                        <?php } else { ?>
                                            <p class="red">Annulée</p>
                                        <?php } ?>
                                    </div>
                                    <div class="mes__reserv__prix">
                                        <h4 class="mes__reserv__prix_color"><?php echo $result["prix_ttc"] . "€"; ?></h4>
                                        <a href=""><i class="fa-solid fa-ellipsis-vertical"></i></a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php } ?>
                <?php } ?>
            </div>
        </main>
        <?php require_once 'footer.php'; ?>
    </div>
    <script src="js/script.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var exportButton = document.getElementById("export-reservation-btn");
            exportButton.addEventListener("click", function (e) {
                e.preventDefault();

                fetch("../exporter_reservation.php?id=<?php echo $id ?>&type=0")
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error("Une erreur s'est produite lors du téléchargement des réservations.");
                        }
                        return response.blob();
                    })
                    .then(function (blob) {
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'reservations.csv';
                        document.body.appendChild(a);
                        a.click();

                        window.URL.revokeObjectURL(url);
                    })
                    .catch(function (error) {
                        alert(error.message);
                    });
            });
        });
    </script>
</body>

</html>