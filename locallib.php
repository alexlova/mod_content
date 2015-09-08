<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Internal library of functions for module content
 *
 * All the content specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_content
 * @copyright  2015 Leo Renis Santos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Constantes
 */
define('CONTENT_PAGE_MIN_HEIGHT', 500);

require_once(dirname(__FILE__).'/lib.php');

 /**
 * Add the book TOC sticky block to the default region
 *
 * @param array $$pages
 * @param stdClass $$page
 * @param stdClass $content
 * @param stdClass $cm
 * @param bool $edit
 */
function content_add_fake_block($pages, $page, $content, $cm, $edit) {
    global $OUTPUT, $PAGE;

	$toc = content_get_toc($pages, $page, $content, $cm, $edit, 0);
	
    $bc = new block_contents();
    $bc->title = get_string('summary', 'content');
    $bc->attributes['class'] = 'block block_content_toc';
    $bc->content = $toc;
    $defaultregion = $PAGE->blocks->get_default_region();
    $PAGE->blocks->add_fake_block($bc, $defaultregion);
}

/**
 * Generate toc structure
 *
 * @param array $chapters
 * @param stdClass $chapter
 * @param stdClass $book
 * @param stdClass $cm
 * @param bool $edit
 * @return string
 */
function content_get_toc($pages, $page, $content, $cm, $edit) {
    global $USER, $OUTPUT;
	
	$first = 1;
	$toc = '';
	
	$context = context_module::instance($cm->id);
	
	$toc .= html_writer::start_tag('div', array('class' => 'content_toc clearfix'));
	
	// // Teacher's TOC
	if($edit){

		$toc .= html_writer::start_tag('ul');
		foreach ($pages as $pg) {
			$title = trim(format_string($pg->title, true, array('context'=>$context)));
			$toc .= html_writer::start_tag('li', array('class' => 'clearfix')); // Inicio <li>
				$toc .= html_writer::link('#', $title, array('title' => s($title), 'class'=>'load-page page'.$pg->id, 'data-pagenum' => $pg->pagenum, 'data-cmid' => $pg->cmid, 'data-sesskey' => sesskey()));
				
				// Actions
				$toc .= html_writer::start_tag('div', array('class' => 'action-list')); // Inicio <div>
					$toc .= html_writer::link(new moodle_url('edit.php', array('cmid' => $pg->cmid, 'id' => $pg->id)),
	                                        $OUTPUT->pix_icon('t/edit', get_string('edit')), array('title' => get_string('edit')));
	            	$toc .= html_writer::link(new moodle_url('delete.php', array('id' => $pg->cmid, 'pageid' => $pg->id, 'sesskey' => $USER->sesskey)),
	                                        $OUTPUT->pix_icon('t/delete', get_string('delete')), array('title' => get_string('delete')));
				
					if ($pg->hidden) {
	                	$toc .= html_writer::link(new moodle_url('show.php', array('id' => $pg->cmid, 'chapterid' => $pg->id, 'sesskey' => $USER->sesskey)),
	                                            $OUTPUT->pix_icon('t/show', get_string('show')), array('title' => get_string('show')));
		            } else {
		                $toc .= html_writer::link(new moodle_url('show.php', array('id' => $pg->cmid, 'chapterid' => $pg->id, 'sesskey' => $USER->sesskey)),
		                                         $OUTPUT->pix_icon('t/hide', get_string('hide')), array('title' => get_string('hide')));
		            }
					$toc .= html_writer::link(new moodle_url('edit.php', array('cmid' => $pg->cmid, 'pagenum' => $pg->pagenum, 'subpage' => $pg->subpage)),
	                                        $OUTPUT->pix_icon('add', get_string('addafter', 'mod_content'), 'mod_content'), array('title' => get_string('addafter', 'mod_content')));
				$toc .= html_writer::end_tag('div'); 	// Fim </div>
			$toc .= html_writer::end_tag('li'); // Fim </li>
		}
		
		$toc .= html_writer::end_tag('ul');
	}else{	// Normal students view
		$toc .= html_writer::start_tag('ul');
		foreach ($pages as $pg) {
			$title = trim(format_string($pg->title, true, array('context'=>$context)));
			$toc .= html_writer::start_tag('li', array('class' => 'clearfix'));
				$toc .= html_writer::link('#', $title, array('title' => s($title), 'class'=>'load-page page'.$pg->pagenum, 'data-pagenum' => $pg->pagenum, 'data-cmid' => $pg->cmid, 'data-sesskey' => sesskey()));
			$toc .= html_writer::end_tag('li');
		}
		
		$toc .= html_writer::end_tag('ul');
	}
	
	$toc .= html_writer::end_tag('div');
	
	return $toc;
}
 /**
 * Add atributos dinamicos da tela de carregamento de paginas
 * @param stdClass $pagestyle
 * @return void
 */
function content_add_properties_css($pagestyle){
	$style = "background-color: #{$pagestyle->bgcolor}; ";
	$style .= "min-height: ". CONTENT_PAGE_MIN_HEIGHT ."px; ";
	$style .= "border: {$pagestyle->borderwidth}px solid #{$pagestyle->bordercolor};";
	
	if($pagestyle->bgimage){
		$style .= "background-image: url('{$pagestyle->bgimage}')";
	}
	
	return $style;
}
 /**
 * Recupera estilo da pagina. O metodo verifica se a pagina possui os valores suficientes para montar o estilo. 
 * Senão retorna estilo generico do plugin.
 * @param stdClass $content
 * @param stdClass $page
 * @return pagestyle;
 */
function content_get_page_style($content, $page, $context){
	$pagestyle = new stdClass;
	$pagestyle->bgcolor = $page->bgcolor ? $page->bgcolor : $content->bgcolor;
	$pagestyle->borderwidth = $page->borderwidth ? $page->borderwidth : $content->borderwidth;
	$pagestyle->bordercolor = $page->bordercolor ? $page->bordercolor : $content->bordercolor;
	$pagestyle->bgimage = false;
	if($page->showbgimage){
		$pagestyle->bgimage = content_get_page_bgimage($context, $page) ? content_get_page_bgimage($context, $page) : content_get_bgimage($context);
	}
	
	return content_add_properties_css($pagestyle);
}

 /**
 * Add border options
 * @param void 
 * @return array $options
 */
function content_add_borderwidth_options(){
	$arr = array();
	for($i = 0; $i < 50; $i++){
		$arr[$i] = $i.'px';
	}
	return $arr;
}
/**
 * Recupera bgimage do plugin content
 * @param stdClass $context 
 * @return string $fullpath
 */
function content_get_bgimage($context){
	global $CFG;
	$fs = get_file_storage();
	$files = $fs->get_area_files($context->id, 'mod_content', 'content', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
	
	if (count($files) >= 1) {
	    $file = reset($files);
	    unset($files);
		
	    $path = '/'.$context->id.'/mod_content/content/'.$file->get_filepath().$file->get_filename();
	    $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
	
	    $mimetype = $file->get_mimetype();
		
		 if (file_mimetype_in_typegroup($mimetype, 'web_image'))   // It's an image
			return $fullurl;
		 return false;
	}
	return false;
}
/**
 * Recupera bgimage das paginas de conteudo do plugin content
 * @param stdClass $context 
 * @return string $fullpath
 */
function content_get_page_bgimage($context, $page){
	global $CFG;
	$fs = get_file_storage();
	$files = $fs->get_area_files($context->id, 'mod_content', 'bgpage', $page->id, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
	
	if (count($files) >= 1) {
	    $file = reset($files);
	    unset($files);
		
	    $path = '/'.$context->id.'/mod_content/bgpage/'.$page->id.$file->get_filepath().$file->get_filename();
	    $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
	
	    $mimetype = $file->get_mimetype();
		
		 if (file_mimetype_in_typegroup($mimetype, 'web_image'))   // It's an image
			return $fullurl;
		 return false;
	}
	return false;
}
/**
 * Preload content pages.
 *
 * Returns array of pages 
 * Please note the content/text of pages is not included.
 *
 * @param  stdClass $content
 * @return array of id=>content
 */
function content_preload_pages($content){
	global $DB;
	$pages = $DB->get_records('content_pages', array('contentid'=>$content->id), 'pagenum', 'id, contentid, cmid, pagenum, subpage, title, hidden');
    if (!$pages) {
        return array();
    }
	// Source here!
	return $pages;
}

/**
 * Carrega botoes do conteudo.
 *
 * Returns buttons of pages 
 *
 * @param  stdClass $pages
 * @return array of id=>content
 */
function content_buttons($pages){
	if(empty($pages)){
		return false;
	}
	// Source here! 
	$pgbuttons = html_writer::start_div('btn_pages', array('id'=> 'fitem_id_submitbutton'));
	$npage = 1;
	foreach ($pages as $page) {
		$pgbuttons .= html_writer::tag('button', $npage, array('title' => s($page->title), 'class'=>'load-page page'.$page->pagenum , 'data-toggle'=> 'tooltip', 'data-placement'=> 'top', 'data-pagenum' => $page->pagenum, 'data-cmid' => $page->cmid, 'data-sesskey' => sesskey()));
		$npage ++;
	}
	$pgbuttons .= html_writer::end_div();
	
	return $pgbuttons;
}

/**
 * Carrega numero da pagina de unicio do usuario logado.
 *
 * Returns array of pages 
 * Please note the content/text of pages is not included.
 *
 * @param  stdClass $content
 * @param  stdClass $context
 * @return array of id=>content
 */
function content_get_startpagenum($content, $context){
	global $DB;
	if(has_capability('mod/content:edit', $context)){
		return content_get_minpagenum($content);
	}
	// REGRA: sistema devera encontrar a pagina que o usuario parou na tabela {content_pages_displayed} e retornar pagina.
	// codigo aqui
	$pagenum = 1;
	return $pagenum;
}

/**
 * Carrega primeira pagina de conteudo do componente.
 *
 * Returns array of pages 
 * Please note the content/text of pages is not included.
 *
 * @param  stdClass $content
 * @return array of id=>content
 */
function content_get_minpagenum($content){
	global $DB;
	
	$sql = "SELECT Min(pagenum) AS minpagenum FROM {content_pages} WHERE contentid = ?;";
	
 	$obj = $DB->get_record_sql($sql, array($content->id));
	
	return $obj->minpagenum;
}

/**
 * Gera numero de uma nova pagina.
 *
 * Returns pagenum
 *
 * @param  int $contentid
 * @return int pagenum
 */
 function content_count_pagenum($contentid){
 	global $DB;
 	$sql = "SELECT Count(pagenum) AS countpagenum FROM {content_pages} WHERE contentid = ?;";
	
 	$obj = $DB->get_record_sql($sql, array($contentid));
	
	return $obj->countpagenum;
 }
 /**
 * Recupera numero da pagina atraves de um Id da pagina.
 *
 * Returns pagenum
 *
 * @param  int $pageid
 * @return int pagenum
 */
 function content_get_pagenum_by_pageid($pageid){
 	global $DB;
 	$sql = "SELECT pagenum  FROM {content_pages} WHERE id = ?;";
	
 	$obj = $DB->get_record_sql($sql, array($pageid));
	
	return $obj->pagenum;
 }
 /*AREAS*/
 
  /**
 * Metodo responsavel por retornar script js que habilita os toltips das paginas
 * @author  Leo santos
 * @return string $js
 */
 function content_get_script_enable_toltip(){
 	$js =  '$(document).ready(function(){
 				$(\'[data-toggle="tooltip"]\').tooltip();
 			}); // End ready
 		';
 	return html_writer::script($js);
 }
 
 /**
 * Metodo responsavel por retornar script js que salva uma nova anotacao: anotacao
 * @author  Leo santos
 * @return string $js
 */
 function content_get_script_save_note(){
  	$js = '$(document).ready(function(){
		  		function onSaveNoteClick(){
					if(!$("#idcommentnote").val().trim()){
						$( "#idcommentnote" ).focus().val("");
						return false;
					}
					var data = {
						"action"		: "savereturnnotes",
						"id"			: $(this).attr(\'data-cmid\'),
						"pageid"		: $(this).attr(\'data-pageid\'),
						"sesskey"		: $(this).attr(\'data-sesskey\'),
						"comment"		: $("#idcommentnote").val(),
						"private"		: $("#idprivate").is(":checked") ? 1 : 0,
						"featured"		: $("#idfeatured").is(":checked") ? 1 : 0,
						"doubttutor"	: 0,
					};
					
					data = $(this).serialize() + "&" + $.param(data);
					
					$.ajax({
				      type: "POST",
				      dataType: "json",
				      url: "ajax.php", //Relative or absolute path to ajax.php file
				      data: data,
				      success: function(data) {
				        $(".content-page").html(
				          data["fullpagecontent"]
				        );
				      }
				    }); // fim ajax
					
					//console.log(data);
				}
				// chamada da funcao
				$("#idbtnsavenote").click(onSaveNoteClick);
		}); // End ready
	';
 	return html_writer::script($js);
 }
 
 /**
 * Metodo responsavel por retornar script js que salva uma nova anotacao: duvida
 * @author  Leo santos
 * @return string $js
 */
  function content_get_script_save_doubt(){
  	$js = '$(document).ready(function(){
		  		function onSaveDoubtClick(){
			
					if(!$("#idcommentdoubt").val().trim()){
						$( "#idcommentdoubt" ).focus().val("");
						return false;
					}
					
					var data = {
						"action"		: "savereturnnotes",
						"id"			: $(this).attr(\'data-cmid\'),
						"pageid"		: $(this).attr(\'data-pageid\'),
						"sesskey"		: $(this).attr(\'data-sesskey\'),
						"comment"		: $("#idcommentdoubt").val(),
						"doubttutor"	: $("#iddoubttutor").is(":checked") ? 1 : 0,
						"private"		: 0,
						"featured"		: 0,
					};
					
					data = $(this).serialize() + "&" + $.param(data);
					
					console.log("passei aqui");
				}
				// chamada da funcao
				$("#idbtnsavedoubt").click(onSaveDoubtClick);
		}); // End ready
	';
 	return html_writer::script($js);
 }
 /**
 * Metodo responsavel por criar area de comentarios das paginas
 * Returns notesarea
 * @param  stdClass $objpage
 * @return string $notesarea
 */
 function content_make_notesarea(stdClass $objpage){
 	global $OUTPUT, $USER;
	
	// Divisor page / notes
	$hr = html_writer::tag('hr', null). html_writer::link(null, null, array('name'=>'notesarea'));
 	// Title page
	$h4 = html_writer::tag('h4', get_string('doubtandnotes', 'mod_content'), array('class'=>'titlenotes'));
	// user image
	$picture = html_writer::tag('div', $OUTPUT->user_picture($USER, array('size'=>60, 'class'=> 'img-thumbnail')), array('class'=>'span1'));
	// fields
	$textareanote = html_writer::tag('textarea', null, array('name'=>'comment', 'id'=>'idcommentnote', 'class'=>'span12', 'maxlength'=> '1024', 'required'=> 'required', 'placeholder'=> get_string('writenotes', 'mod_content')));
	$textareadoubt = html_writer::tag('textarea', null, array('name'=>'comment', 'id'=>'idcommentdoubt', 'class'=>'span12','maxlength'=> '1024', 'required'=> 'required', 'placeholder'=> get_string('writedoubt', 'mod_content')));
	$checkprivate = html_writer::tag('input', null, array('name'=>'private', 'type'=>'checkbox', 'id'=>'idprivate'));
	$labelprivate = html_writer::tag('label', get_string('private', 'mod_content'), array('for'=>'idprivate'));
	$spanprivate = html_writer::tag('span', $checkprivate. $labelprivate, array('class'=>'fieldprivate'));
	$checkfeatured = html_writer::tag('input', null, array('name'=>'featured', 'type'=>'checkbox', 'id'=>'idfeatured'));
	$labelfeatured = html_writer::tag('label', get_string('featured', 'mod_content'), array('for'=>'idfeatured'));
	$spanfeatured = html_writer::tag('span', $checkfeatured. $labelfeatured, array('class'=>'fieldfeatured'));
	$checkdoubttutor = html_writer::tag('input', null, array('name'=>'doubttutor', 'type'=>'checkbox', 'id'=>'iddoubttutor'));
	$labeldoubttutor = html_writer::tag('label', get_string('doubttutor', 'mod_content'), array('for'=>'iddoubttutor'));
	$spandoubttutor = html_writer::tag('span', $checkdoubttutor. $labeldoubttutor, array('class'=>'fielddoubttutor'));
	$btnsavenote = html_writer::tag('button', get_string('save','mod_content'), array('class'=>'btn btn-primary pull-right', 'id' => 'idbtnsavenote', 'data-pageid'=>$objpage->id,'data-cmid'=>$objpage->cmid, 'data-sesskey' => sesskey()));
	$btnsavedoubt = html_writer::tag('button', get_string('save','mod_content'), array('class'=>'btn btn-primary pull-right', 'id' => 'idbtnsavedoubt', 'data-pageid'=>$objpage->id,'data-cmid'=>$objpage->cmid, 'data-sesskey' => sesskey()));
	
	$fieldsnote = html_writer::tag('div', $textareanote. $spanprivate. $spanfeatured. $btnsavenote, array('class'=>'span11'));
	$fieldsdoubt = html_writer::tag('div', $textareadoubt. $spandoubttutor. $btnsavedoubt, array('class'=>'span11'));
	
	// Form
	$formnote = html_writer::tag('div', $picture . $fieldsnote, array('class'=>'fields'));
	$formdoubt = html_writer::tag('div', $picture . $fieldsdoubt, array('class'=>'fields'));
	
	// TAB NAVS
	$note = html_writer::tag('li', 
		html_writer::link('#note', get_string('note', 'content'), array('id'=>'note-tab', 'aria-expanded' => 'true', 'aria-controls'=>'note' ,'role'=>'tab', 'data-toggle'=>'tab')), 
	array('class'=>'active', 'role'=>'presentation'));
	$doubt = html_writer::tag('li', 
		html_writer::link('#doubt', get_string('doubt', 'content'), array('id'=>'doubt-tab', 'aria-expanded' => 'false', 'aria-controls'=>'doubt' ,'role'=>'tab', 'data-toggle'=>'tab')), 
	array('class'=>'', 'role'=>'presentation'));
	
	$tabnav = html_writer::tag('ul', $note .$doubt, array('class'=> 'nav nav-tabs', 'id'=>'tabnav'));
	
	// TAB CONTENT
	$contentnote = html_writer::div($formnote,'tab-pane active', array('role'=>'tabpanel', 'id'=>'note'));
	$contentdoubt = html_writer::div($formdoubt, 'tab-pane', array('role'=>'tabpanel', 'id'=>'doubt'));
	$tabcontent = html_writer::div($contentnote. $contentdoubt, 'tab-content', array('id'=>'idtabcontent'));
	
	// Area Notes
	$notesarea = html_writer::tag('div', $hr. $h4. $tabnav. $tabcontent, array('class'=>'row-fluid notesarea'));
	
 	// return 
 	return $notesarea;
 }
 
/**
 * Gera conteudo de uma pagina e retorna objeto.
 *
 * @param  int 		$pagenum || $startpage
 * @param  stdClass $content
 * @param  stdClass $context
 * @return stdclass	$fullpage
 */
 function content_get_fullpagecontent($pagenum, $content, $context){
 	global $DB, $CFG;
	// PENDENTE: Crirar rotina para gravar logs...
	
	$scriptsjs = content_get_script_enable_toltip();
	$scriptsjs .= content_get_script_save_note();
	$scriptsjs .= content_get_script_save_doubt();
	
 	$objpage = $DB->get_record('content_pages', array('pagenum' => $pagenum, 'contentid' => $content->id));
	
	// Elementos toolbar
	$comments = html_writer::link('#notesarea', '<i class="fa fa-comments fa-lg"></i>', array('title' => s(get_string('comments', 'content')), 'class'=>'icon-comments','data-toggle'=> 'tooltip', 'data-placement'=> 'top', 'data-pagenum' => $objpage->pagenum, 'data-cmid' => $objpage->cmid, 'data-sesskey' => sesskey()));
	$toolbarpage = html_writer::tag('div', $comments.' <i class="fa fa-square-o fa-lg"></i> <i class="fa fa-adjust fa-lg"> </i>', array('class'=>'toolbarpage '));
	
	// Adicionando elemento titulo da pagina
	$title = html_writer::tag('h3', '<i class="fa fa-hand-o-right"></i> '.$objpage->title, array('class'=>'pagetitle'));
	
	// Tratando arquivos da pagina e preparando conteudo
	$objpage->pagecontent = file_rewrite_pluginfile_urls($objpage->pagecontent, 'pluginfile.php', $context->id, 'mod_content', 'page', $pagenum);
	$objpage->pagecontent = format_text($objpage->pagecontent, $objpage->pagecontentformat, array('noclean'=>true, 'overflowdiv'=>true, 'context'=>$context));
	
	// Adicionando elemento que contera a numero da pagina
	$npage = html_writer::tag('div', get_string('page', 'content', $objpage->pagenum), array('class'=>'pagenum'));
	
	// form notes
	$notesarea = content_make_notesarea($objpage);
	
	/* // Adicionando passadores de pagina
	$previous = html_writer::link('#', "<i class='fa fa-angle-left'></i> ".get_string('previous', 'content'), array('title' => s(get_string('pageprevious', 'content')), 'class'=>'previous span6 load-page page'.$objpage->pagenum, 'data-pagenum' => ($objpage->pagenum - 1), 'data-cmid' => $objpage->cmid, 'data-sesskey' => sesskey()));
	$next = html_writer::link('#', get_string('next', 'content')." <i class='fa fa-angle-right'></i>", array('title' => s(get_string('nextpage', 'content')), 'class'=>'next span6 load-page page'.$objpage->pagenum, 'data-pagenum' => ($objpage->pagenum + 1), 'data-cmid' => $objpage->cmid, 'data-sesskey' => sesskey()));
	
	$objpage->navbar = html_writer::tag('div', $previous. $next, array('class'=>'pagenavbar row'));*/
	
	// Preparando conteudo da pagina para retorno
	$fullpagecontent = html_writer::tag('div', $toolbarpage. $title. $objpage->pagecontent . $npage. $notesarea. $scriptsjs, array('class'=>'fulltextpage', 'data-pagenum' => $objpage->pagenum, 'style'=> content_get_page_style($content, $objpage, $context)));
	$objpage->fullpagecontent = $fullpagecontent;
	
	return $objpage;
 }
