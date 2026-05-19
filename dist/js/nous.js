
$('.nous').on('click',function(e){
    e.preventDefault();
    swal.fire({
        title: 'Logiciel réalisé par Smart',
        html: "<p><b>Contactez-nous sur whatsapp aux :</b></p><p>+226 808583610</p><p>+226 820409035</p><p><b>Contactez-nous sur appel normal aux :</b></p><p>+226 979465430</p><p>+226 810396484</p><p><b>Contactez-nous sur E-Mail aux :</b></p><p>yvon.bonga@gmail.com</p><p>kagalacenyange3@gmail.com</p>",
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonColor:'#e6e64cfa',
        cancelButtonText: 'Quitter',
    })
})