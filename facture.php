<?php
    session_start();
    require_once("connexion.php");

    $_GET['page'] = 4;

    $mc = "";

    if(isset($_POST['fac']))
    {
      $mc = htmlspecialchars(trim(addslashes($_POST['fac'])));
    }

    $id_utilisateur = $_SESSION['ID'];
	
    $req = "select * from UTILISATEUR where ID_UTILISATEUR = $id_utilisateur ";
    $rs = mysqli_query($connexion, $req);
    
    $ET = mysqli_fetch_assoc($rs);
	
    $req = "select * from FAC ";
    $rs1 = mysqli_query($connexion, $req);
    
    $ET1 = mysqli_fetch_assoc($rs1);

    if(!isset($_POST['facture']))
    {
        header("location:vente.php?code=3");
	    exit;
    }
?>
<!doctype html>

<html lang="FR">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?php echo $ET100['TITRE']?> - Facture</title>
    <!-- CSS files -->
    <link href="./dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="./dist/css/tabler-flags.min.css" rel="stylesheet"/>
    <link href="./dist/css/tabler-payments.min.css" rel="stylesheet"/>
    <link href="./dist/css/tabler-vendors.min.css" rel="stylesheet"/>
    <link href="./dist/css/demo.min.css" rel="stylesheet"/>
  </head>
  <style>
    .table-responsive{
        height: calc(24rem + 10px);
    }
  </style>
  <body class="antialiased">
    <div class="wrapper">
      <?php include('header.php'); ?>
      <div class="page-wrapper">
        <div class="container-xl">
          <!-- Page title -->
          <div class="page-header d-print-none">
            <div class="row align-items-center">
              <div class="col">
                <h2 class="page-title">
                  Facture
                </h2>
              </div>
              <!-- Page title actions -->
              <div class="col-auto ms-auto d-print-none">
                <a href="<?php if(isset($_GET['id_dette_directe'])){ echo 'liste_dette_directe_client.php?page=4&id_dette_directe='.$_GET['id_dette_directe'];} else{ echo 'vente.php?page=4';} ?>" class="btn btn-primary d-none d-sm-inline-block">
                    <!-- Download SVG icon from http://tabler-icons.io/i/arrow-back-up -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 13l-4 -4l4 -4m-4 4h11a4 4 0 0 1 0 8h-1" /></svg>
                    Retour
                </a>
                <a href="vente.php?page=4" class="btn btn-primary d-sm-none btn-icon">
                    <!-- Download SVG icon from http://tabler-icons.io/i/arrow-back-up -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 13l-4 -4l4 -4m-4 4h11a4 4 0 0 1 0 8h-1" /></svg>
                </a>
                <button type="button" class="btn btn-primary d-none d-sm-inline-block facture" onclick="javascript:window.print();">
                  <!-- Download SVG icon from http://tabler-icons.io/i/printer -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><rect x="7" y="13" width="10" height="8" rx="2" /></svg>
                  Imprimer
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="page-body">
          <div class="container-xl">
            <div class="card card-lg">
              <div class="card-body">
                <div class="row">
                  <div class="col-6">
                    <p class="h3" style="color: #e2e215db!important;font-size: 1.2rem!important;"><?php echo $ET100['TITRE']?></p>
                    <address>
                    <?php echo $ET100['NUM_NATIONAL']?><br>
                    <?php echo $ET100['ADRESSE']?><br>
                    <?php echo $ET100['PHONE']?>
                    </address>
                  </div>
                  <div class="col-6 text-end">
                    <address>Le <?php echo date('d/m/Y à h:m:s'); ?></address>
                  </div>
                  <div class="col-12 my-5 text-center">
                    <h1>FACTURE N°<?php echo ($ET1['FAC'] + 1).'/'.date('dmY/hms').$_SESSION['ID']; ?></h1>
                  </div>
                </div>
                <?php if(isset($_POST['facture'])){ ?>

                      <p>Nom du client: <span class="text-uppercase" id="nom_client"></span></p>

                      <div id="cadrer">

                        <table class="table table-transparent mt-2">
                        <thead>
                            <tr>
                            <th class="text-center" style="width: 2%"></th>
                            <th>Produit</th>
                            <th class="text-center" style="width: 10%">Quantité</th>
                            <th class="text-center" style="width: 15%">P.U</th>
                            <th class="text-center" style="width: 15%">P.T</th>
                            </tr>
                        </thead>
                        
                        <?php
                            $i = 1;
                            $montant = 0;
                            $reste = 0;
                            $code_facture = ($ET1['FAC'] + 1).'/'.date('dmY/hms').$_SESSION['ID'];

                            for ($j=0; $j < count($_POST['facture']); $j++) {

                              $id_facture = $_POST['facture'][$j];

                            $req = "select count(*) as nombre from VENTE where ID_VENTE = $id_facture ";
                            $rs200 = mysqli_query($connexion,$req);
                            $ET200=mysqli_fetch_assoc($rs200);

                            $req = "select * from VENTE where ID_VENTE = $id_facture ";
                            $rs201 = mysqli_query($connexion,$req);
                            $ET201=mysqli_fetch_assoc($rs201);

                                $id_produit = $ET201['ID_PRODUIT'];

                            $req = "select * from PRODUIT where ID_PRODUIT = $id_produit ";
                            $rs20 = mysqli_query($connexion,$req);
                            $ET20=mysqli_fetch_assoc($rs20);
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td>
                            <p class="strong mb-1"><?php echo $ET20['TITRE_PRODUIT'] ?></p>
                            </td>
                            <td class="text-center"><?php echo $ET201['QUANTITE_VENTE'] ?></td>
                            <td class="text-center"><?php echo $ET201['PRIX_VENTE'] ?> $</td>
                            <td class="text-center"><?php echo ($ET201['QUANTITE_VENTE'] * $ET201['PRIX_VENTE']) ?> $</td>
                        </tr>
                        <?php
                            $montant += ($ET201['QUANTITE_VENTE'] * $ET201['PRIX_VENTE']);
                            }
                        ?>
                        <tr>
                        <tr>
                            <td colspan="1" class="font-weight-bold text-uppercase text-end"></td>
                            <td colspan="1" class="font-weight-bold text-left">TVA <?php echo (float)$taux_tva; ?>%<br><br>Motif : Vente</td>
                            <td colspan="2" class="font-weight-bold text-end">Total général:</td>
                            <td colspan="1" class="font-weight-bold text-center"><?php echo $montant ?> $ </td>
                        </tr>
                        </table>
                      </div>
                      <div id="client" style="display:none;"></div>
                <?php } ?>
                <div class="row">
                  <div class="col-12 text-end">
                    <p class="text-left mt-5">Nom du vendeur<br/><br/><span class="text-uppercase"><?php echo $ET['NOM']?></span></p>
                  </div>
                </div>
                
                <p class="text-muted text-center mt-5">Les marchandises vendues ne sont ni reprises, ni échangées, Merci !</p>
              </div>
            </div>
          </div>
        </div>
        <?php include('footer.php') ?>
      </div>
    </div>
    <!-- Libs JS -->
    <!-- Tabler Core -->
    <script src="./dist/js/tabler.min.js"></script>

    <script src="./dist/js/jquery-3.2.1.min.js"></script>
    <script src="./dist/js/sweetalert2.all.js"></script>
    <script src="./dist/js/sweetalert2.js"></script>
    <script src="./dist/js/sweetalert2.min.js"></script>
    <script src="./dist/js/sweetalert2.all.min.js"></script>
    <script src="./dist/js/traitement.js"></script>
    <script src="./dist/js/nous.js"></script>
    <script>
  
  $('.formulaire').on('submit',function(e){
      e.preventDefault();

      var donnees = $(this).serialize();
  
      lien = $(this).attr('action');

      swal.fire({
          html: '<p>Patienter<span class="animated-dots"></span></p>',
          imageUrl: './dist/loader.gif',
          showConfirmButton: false,
          allowOutsideClick: false,
      });

      $.post(lien, donnees, function(response) {
          
          if (response == 1) {

            swal.fire({
                text: "Bien ajouté !",
                icon: "success",
                showConfirmButton: false,
                timer: 2000,
            });

            document.location.reload();
              
          }
          else
          {

            swal.fire({
                text: "Il y a un problème !",
                icon: "warning",
                showConfirmButton: false,
                timer: 4000,
            });

          }
  
      })

  });
    var client = $('#client').text();
    if(client  == '')
    {
      swal.fire({
          text: 'Nom du client',
          input: 'text',
          showConfirmButton: true,
          showCancelButton: false,
          confirmButtonColor: '#3885d6',
          confirmButtonText: 'Valider',
          closeOnConfirm: true,
          animation: 'slide-from-top',
          inputPlaceholder: 'Entrer le nom du client',
          allowOutsideClick: false,
            inputValidator: (value) => {
              if (value == '') {
                return 'Veuillez entrer le nom du client !';
              }
            }
      }).then((result)=>{
          if(result.value){
            $('#nom_client').text(result.value);
          }
      })
    }
    else
    {
      $('#nom_client').text(client);
    }

      $('.btn_deconnexion').on('click',function(e){
          e.preventDefault();
          swal.fire({
              text: 'Vous voulez vous déconnecter ?',
              icon: "question",
              showConfirmButton: true,
              showCancelButton: true,
              confirmButtonColor: '#3885d6',
              cancelButtonColor:'#d33',
              confirmButtonText: 'Oui',
              cancelButtonText: 'Non',
          }).then((result)=>{
              if(result.value){
                  document.location.href='deconnexion.php';
              }
          })
      })

      $('.no_dispo').on('click',function(e){
        e.preventDefault();

        swal.fire({
          html: '<div class="form-label">Vous ne pouvez pas visiter cette option !</div>',
          icon: "info",
          showConfirmButton: false,
          timer: 3000,
        })
      })


      
      $('.facture').on('click',function(e){

        e.preventDefault();
        var lien = "implementer_facture.php";

        $.post(lien)

      })
        
    </script>
    <script>

        if(window.matchMedia("(max-width:818px)").matches)
        {

            $('.btn btn-primary d-none d-sm-inline-block').css('display', 'none');
            $('#cadrer').addClass('table-responsive');
            $('body').css({
                fontSize : '10px'
            });
            $('h1 , .page-title').css({
                fontSize : '15px'
            });
            $('th').css({
                fontSize : '9px'
            });


        }
    </script>
  </body>
</html>