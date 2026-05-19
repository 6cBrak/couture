<?php
    session_start();
    require_once("connexion.php");

    $id_produit = htmlspecialchars(trim(addslashes($_GET['id_produit'])));

    $req = "select * from PRODUIT where ID_PRODUIT = $id_produit";
    $rs20 = mysqli_query($connexion,$req);
    $ET20=mysqli_fetch_assoc($rs20);


      echo '
      <div class="col-12">
        <div class="card">
          <div class="table-responsive">
            <table class="table table-vcenter card-table">
              <tbody>
                <tr>
                    <th colspan="2"><h1>'.$ET20['TITRE_PRODUIT'].'</h1></th>
                </tr>
                <tr>
                    <th>Stock</th>
                    <th>' .$ET20['QUANTITE_PRODUIT']. '</th>
                </tr>
                <tr>
                    <th>Prix</th>
                    <th>' .$ET20['PRIX_VENTE_PRODUIT']. ' $</th>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>';
?>