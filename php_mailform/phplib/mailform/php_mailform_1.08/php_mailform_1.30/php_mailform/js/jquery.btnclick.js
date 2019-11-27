

$(document).ready(function() {
    $('.notrepeat').each(function(index, element){
        $(this).click(function () {
            $(this).attr('disabled', true);
        });
    });
});