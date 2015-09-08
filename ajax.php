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
 * Process ajax requests
 *
 * @copyright 2015 Leo Renis Santos
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_content
 */

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$sesskey = optional_param('sesskey', false, PARAM_TEXT);
#$itemorder = optional_param('itemorder', false, PARAM_SEQUENCE);

if ($id) {
    $cm         = get_coursemodule_from_id('content', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $content  	= $DB->get_record('content', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $content  	= $DB->get_record('content', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $content->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('content', $content->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}
require_sesskey();
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
$allowedit  = has_capability('mod/content:edit', $context);

$return = false;

switch ($action) {
	case 'loadpage':
		$pagenum = required_param('pagenum', PARAM_INT);
		$return = content_ajax_getpage($pagenum, $content, $context);
		break;
	case 'savereturnnotes':
		$pageid = required_param('pageid', PARAM_INT);
		$note = new stdClass;
		$note->comment 		= required_param('comment', PARAM_RAW);
		$note->cmid 		= required_param('id', PARAM_INT);
		$note->featured 	= required_param('featured', PARAM_INT);
		$note->private 		= required_param('private', PARAM_INT);
		$note->doubttutor 	= required_param('doubttutor', PARAM_INT);
		
		$return = content_ajax_savereturnnotes($pageid, $note);
		break;
		// codificar outras acoes...
}

echo json_encode($return);
die;
