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
 * Edit content page
 *
 * @package    mod_content
 * @copyright  2005-2016 Leo Santos {@link http://facebook.com/leorenisJC}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/edit_form.php');

$cmid       = required_param('cmid', PARAM_INT);  // Content Course Module ID
$pageid  	= optional_param('id', 0, PARAM_INT); // Page ID
$pagenum    = optional_param('pagenum', 0, PARAM_INT);
$subpage 	= optional_param('subpage', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('content', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$content = $DB->get_record('content', array('id'=>$cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/content:edit', $context);

// Log this request.
$event = \mod_content\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $content);
$event->trigger();

$PAGE->set_url('/mod/content/edit.php', array('cmid'=>$cmid, 'id'=>$pageid, 'pagenum'=>$pagenum, 'subpage'=>$subpage));
$PAGE->set_pagelayout('admin'); // TODO: Something. This is a bloody hack!

if ($pageid) {
    $page = $DB->get_record('content_pages', array('id'=>$pageid, 'contentid'=>$content->id), '*', MUST_EXIST);
} else {
    $page = new stdClass();
    $page->id    		= null;
    $page->pagenum		= content_count_pagenum($content->id) + 1;
}
$page->contentid	= $content->id;
$page->cmid			= $cm->id;

$pagecontentoptions = array('noclean'=>true, 'subdirs'=>true, 'maxfiles'=>-1, 'maxbytes'=>0, 'context'=>$context);
$page = file_prepare_standard_editor($page, 'pagecontent', $pagecontentoptions, $context, 'mod_content', 'page', $page->id);

$mform = new content_pages_edit_form(null, array('page'=>$page, 'pagecontentoptions'=>$pagecontentoptions));

// If data submitted, then process and store.
if ($mform->is_cancelled()) {
    if (empty($page->id)) {
        redirect("view.php?id=$cm->id");
    } else {
        redirect("view.php?id=$cm->id&pageid=$page->id");
    }

} else if ($data = $mform->get_data()){
	
	// codifica aqui
	if ($data->id) {
		// store the files
        $data->timemodified = time();
		$data = file_postupdate_standard_editor($data, 'pagecontent', $pagecontentoptions, $context, 'mod_content', 'page', $data->id);
        $DB->update_record('content_pages', $data);
		
		// Salvando arquivo bgarea filemanager
		file_save_draft_area_files($data->bgimage, $context->id, 'mod_content', 'bgpage',
                   $data->id, array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
				   
        $page = $DB->get_record('content_pages', array('id' => $data->id));
		
		// redirect
		redirect("view.php?id=$cm->id&pageid=$data->id", get_string('msgsucess','content'));
		
	}else{
		$data->pagecontent = '';         		// updated later
		$data->pagecontentformat = FORMAT_HTML; // updated later
		
		$data->id = $DB->insert_record('content_pages', $data);
		$data = file_postupdate_standard_editor($data, 'pagecontent', $pagecontentoptions, $context, 'mod_content', 'page', $data->id);
		
   		$DB->update_record('content_pages', $data);
		
		// Salvando arquivo bgarea filemanager
		file_save_draft_area_files($data->bgimage, $context->id, 'mod_content', 'bgpage',
                   $data->id, array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
				   
		// redirect
		redirect("view.php?id=$cm->id&pageid=$data->id", get_string('msgsucess','content'));
	}
}
// Otherwise fill and print the form.
$PAGE->set_title($content->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($content->name);

$mform->display();

echo $OUTPUT->footer();