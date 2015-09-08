/**
 * Prints a particular instance of content
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_content
 * @copyright  2015 Leo Renis Santos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$(document).ready(function(){
	
	// Carrega pagina
	function onLoadPageClick(){
		var data = {
			"action": "loadpage",
			"id": $(this).attr('data-cmid'),
			"pagenum": $(this).attr('data-pagenum'),
			"sesskey": $(this).attr('data-sesskey')
		};
		// Carregando pagina
		$(".content-page")
			.children('.fulltextpage')
			.prepend(
				$('<div />')
					.addClass('loading')
					.html('<img src="pix/loading.gif" alt="Loading" class="img-loading" />')
			)
			.css('opacity', '0.5');
			
		// Ativa link ou botao da pagina atual
		onActive(data['pagenum']);
		
		data = $(this).serialize() + "&" + $.param(data);
	  	$.ajax({
	    	type: "POST",
	    	dataType: "json",
	    	url: "ajax.php", //Relative or absolute path to ajax.php file
	    	data: data,
	    	success: function(data) {
	    		$(".content-page").html(data.fullpagecontent);
	    	}
	    }); // fim ajax
	    
	} // End onLoad..
	
	// Alterando estilo do link ou botoes da pagina ativa
	function onActive(pagenum){
		var pagenum = pagenum;
		$(".load-page").removeClass("active");
		$(".page"+ pagenum).addClass("active");
	}
	
	/*** Chamada de eventos ***/
	
	onActive($(".fulltextpage").attr('data-pagenum'));		// Recupera numero da pagina
  	$(".load-page").click(onLoadPageClick); 				// Captura evento click
  	
}); // End ready