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
 * Prints a particular instance of content
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_content
 * @copyright  2015 Leo Renis Santos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace content with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/lib.php');
require_once("$CFG->libdir/resourcelib.php"); // Apagar

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... content instance ID - it should be named as the first character of the module.
$edit = optional_param('edit', -1, PARAM_BOOL);    // Edit mode
$pageid = optional_param('pageid', 0, PARAM_INT); // Chapter ID

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

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Log this request.
$event = \mod_content\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $content);
$event->trigger();


$allowedit  = has_capability('mod/content:edit', $context);

if ($allowedit) {
    if ($edit != -1 and confirm_sesskey()) {
        $USER->editing = $edit;
    } else {
        if (isset($USER->editing)) {
            $edit = $USER->editing;
        } else {
            $edit = 0;
        }
    }
} else {
    $edit = 0;
}

// read pages
$pages = content_preload_pages($content);

if ($allowedit and !$pages) {
    redirect('edit.php?cmid='.$cm->id); // No pages - add new one.
}

// Print the page header.

$PAGE->set_url('/mod/content/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($content->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/mod/content/module.js'));

// Recupera primeira pagina a ser apresentada
$startpage  = $pageid ? content_get_pagenum_by_pageid($pageid) : content_get_startpagenum($content, $context);
$showpage = content_get_fullpagecontent($startpage, $content, $context);

content_add_fake_block($pages, $startpage, $content, $cm, $edit); //ADICIONA BLOCO SUMARIO
// =====================================================
// Content display HTML code
// =====================================================

// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading($content->name);

// Conditions to show the intro can change to look for own settings or whatever.
if ($content->intro) {
    echo $OUTPUT->box(format_module_intro('content', $content, $cm->id), 'generalbox mod_introbox', 'contentintro');
}
// Caixa de conteudo
echo $OUTPUT->box_start('content-page', 'pages');
echo $showpage->fullpagecontent;
echo $OUTPUT->box_end();

echo content_buttons($pages);
// Finish the page.
echo $OUTPUT->footer();
?>