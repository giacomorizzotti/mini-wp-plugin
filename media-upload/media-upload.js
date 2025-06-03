jQuery(document).ready( function( $ ) {

    $("#team_1_logo_button").click(function() {

        formfield = $("#team_1_logo").attr("name");
        tb_show( "", "media-upload.php?type=image&TB_iframe=true" );
        window.send_to_editor = function(html) {
        imgurl = $(html).attr("src");
        $("#team_1_logo").val(imgurl);
        tb_remove();
        }

        return false;
    });

    $("#team_2_logo_button").click(function() {

        formfield = $("#team_2_logo").attr("name");
        tb_show( "", "media-upload.php?type=image&TB_iframe=true" );
        window.send_to_editor = function(html) {
        imgurl = $(html).attr("src");
        $("#team_2_logo").val(imgurl);
        tb_remove();
        }

        return false;
    });

});