/*
$(function(){
	
	$('form').on('submit',function(e){
        e.preventDefault();

        var data =  $(this).serialize();
        var lien = $('form').attr('action');

        swal.fire({
            text: 'Patienter ...',
            showConfirmButton: false,
            timer: 3000,
        });

        alert(data);


        $.post(lien, {data}, function() {

            swal.fire({
                text: 'Bien effectué',
                icon: "success",
                showConfirmButton: false,
                timer: 3000,
            });

            document.location.reload();
        })

    })

});
*/