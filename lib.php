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
 * Library of interface functions and constants for module content
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the content specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_content
 * @copyright  2015 Leo Renis Santos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Example constant, you probably want to remove this :-)
 */
define('CONTENT_ULTIMATE_ANSWER', 42);

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function content_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
		case FEATURE_GRADE_OUTCOMES:
			return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the content into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $content Submitted data from the form in mod_form.php
 * @param mod_content_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted content record
 */
function content_add_instance(stdClass $content, mod_content_mod_form $mform = null) {
    global $DB;

    $content->timecreated = time();

    // You may have to add extra stuff in here.

    $content->id = $DB->insert_record('content', $content);

    content_grade_item_update($content);

    return $content->id;
}

/**
 * Updates an instance of the content in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $content An object from the form in mod_form.php
 * @param mod_content_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function content_update_instance(stdClass $content, mod_content_mod_form $mform = null) {
    global $DB;

    $content->timemodified = time();
    $content->id = $content->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('content', $content);

    content_grade_item_update($content);

    return $result;
}

/**
 * Removes an instance of the content from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function content_delete_instance($id) {
    global $DB;

    if (! $content = $DB->get_record('content', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.
	$DB->delete_records('content_pages', array('contentid'=>$content->id));
    $DB->delete_records('content', array('id' => $content->id));

    content_grade_item_delete($content);

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $content The content instance record
 * @return stdClass|null
 */
function content_user_outline($course, $user, $mod, $content) {

    /*$return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return; */
	
	global $CFG;

    require_once("$CFG->libdir/gradelib.php");
    $grades = grade_get_grades($course->id, 'mod', 'content', $content->id, $user->id);

    $return = new stdClass();
    if (empty($grades->items[0]->grades)) {
        $return->info = get_string("no")." ".get_string("attempts", "content");
    } else {
        $grade = reset($grades->items[0]->grades);
        $return->info = get_string("grade") . ': ' . $grade->str_long_grade;

        //datesubmitted == time created. dategraded == time modified or time overridden
        //if grade was last modified by the user themselves use date graded. Otherwise use date submitted
        //TODO: move this copied & pasted code somewhere in the grades API. See MDL-26704
        if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
            $return->time = $grade->dategraded;
        } else {
            $return->time = $grade->datesubmitted;
        }
    }
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $content the module instance record
 */
function content_user_complete($course, $user, $mod, $content) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in content activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function content_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link content_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function content_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link content_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function content_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function content_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function content_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of content?
 *
 * This function returns if a scale is being used by one content
 * if it has support for grading and scales.
 *
 * @param int $contentid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given content instance
 */
function content_scale_used($contentid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('content', array('id' => $contentid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of content.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any content instance
 */
function content_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('content', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given content instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $content instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function content_grade_item_update(stdClass $content, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
	
    $item = array();
    $item['itemname'] = clean_param($content->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    if ($content->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $content->grade;
        $item['grademin']  = 0;
    } else if ($content->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$content->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/content', $content->course, 'mod', 'content',
            $content->id, 0, null, $item);
}

/**
 * Delete grade item for given content instance
 *
 * @param stdClass $content instance object
 * @return grade_item
 */
function content_grade_item_delete($content) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/content', $content->course, 'mod', 'content',
            $content->id, 0, null, array('deleted' => 1));
}

/**
 * Update content grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $content instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function content_update_grades(stdClass $content, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/content', $content->course, 'mod', 'content', $content->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function content_get_file_areas($course, $cm, $context) {
    $areas['page'] = get_string('page', 'mod_content');
    return $areas;
}

/**
 * File browsing support for content file areas
 *
 * @package mod_content
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function content_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
   
    return null;
}

/**
 * Serves the files from the content file areas
 *
 * @package mod_content
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the content's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function content_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
	
	$itemid = 0;
	switch ($filearea) {
		case 'page':
		case 'bgpage':
			$pageid = (int) array_shift($args);
			$itemid = $pageid;
			
			if (!$page = $DB->get_record('content_pages', array('id'=>$pageid))) {
		        return false;
		    }
			break;
		case 'content':
			$itemid = 0;
			break;
		default:
			return false;
			break;
	}

	if (!$content = $DB->get_record('content', array('id'=>$cm->instance))) {
    	return false;
	}

    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_content/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

	 // Nasty hack because we do not have fSile revisions in content yet.
    $lifetime = $CFG->filelifetime;
    if ($lifetime > 60*10) {
        $lifetime = 60*10;
    }
	
	send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
    // finally send the file

    return false;
}

/**
 * Delete files 
 *
 * @param 	stdClass $content 
 * TODO:	IMPORTANTE: IMPLEMENTAR Metodo para remover arquivos quando a instancia do plugin form removida.
 */
function content_delete_files(stdClass $content){
	
	$fs = get_file_storage();
	$files = $fs->get_area_files($context->id, 'mod_content', 'filearea', 'itemid', 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
	
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding content nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the content module instance
 * @param stdClass $course current course record
 * @param stdClass $module current content instance record
 * @param cm_info $cm course module information
 */
function content_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the content settings
 *
 * This function is called when the context for the page is a content module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $contentnode content administration node
 */
function content_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $contentnode=null) {
    // TODO Delete this function and its docblock, or implement it.
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to ajax
////////////////////////////////////////////////


/**
 * Recupera a pagina de conteudo de acordo com os parametros pagenum e contentid.
 * @param int $pagenum
 * @param stdClass $content
 * @return array $pagecontent
 */
function content_ajax_getpage($pagenum, $content, $context){
	
	require_once(dirname(__FILE__).'/locallib.php');
	
	$objpage = content_get_fullpagecontent($pagenum, $content, $context);
	
	return $objpage;
}

/**
 * Salva um novo registro de {note} e retorna lista anotacoes da pagina.
 * @param int $pageid
 * @param stdClass $note
 * @return array $pagenotes
 */
function content_ajax_savereturnnotes($pageid, $note){
	
	// source here
	$pagenotes = false;
	
	return $pagenotes;
}
