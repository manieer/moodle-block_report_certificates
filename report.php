<?php
/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Report certificates block
 * --------------------------
 * Displays all issued certificates for users with unique codes. 
 * The certificates will also be issued for courses that have been archived since issuing of the certificates 
 *
 * @author  Manieer Chhettri | Marie Curie, UK | <manieer@gmail.com> | 2015
 * @package    blocks
 * @subpackage block_report_certificates
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once($CFG->dirroot.'../../config.php');
require_once($CFG->dirroot.'/mod/certificate/locallib.php');

require_login();
global $USER, $DB, $CFG, $OUTPUT;

	$userid = optional_param('userid', $USER->id, PARAM_INT); // User ID

	$url = new moodle_url('/blocks/report_certificates/complete_report.php', array('id'=>$userid));
	$PAGE->set_url($url);


	// Requires a course login
	require_login();

	// Check capabilities
	$context = context_system::instance();
	require_capability('mod/certificate:view', $context);
	$PAGE->set_context($context);

	$table = new html_table();	
	$table->head = array(
						get_string('report_certificates_tblheader_coursename', 'block_report_certificates'),
        			   	get_string('report_certificates_tblheader_grade', 'block_report_certificates'),
        			   	get_string('report_certificates_tblheader_code', 'block_report_certificates'), 
					   	get_string('report_certificates_tblheader_issuedate', 'block_report_certificates'),
						get_string('report_certificates_tblheader_download', 'block_report_certificates'));	
	$table->align = array ("left", "center", "center", "center", "center");
		
		if (has_capability('mod/certificate:view', context_system::instance())) {
    	// Show all user certificates.
    		$where = '';
		} else {
			
		}
		
	$sql = "SELECT DISTINCT 
				ci.id AS certificateid, ci.userid, ci.code AS code, ci.timecreated AS citimecreated,
				crt.name AS certificatename, crt.*,
				cm.id AS coursemoduleid, cm.course, cm.module,
				c.id AS courseid, c.fullname AS fullname,
				ctx.id AS contextid, ctx.instanceid AS instanceid,
				f.itemid AS itemid, f.filename AS filename

			FROM {certificate_issues} ci

			INNER JOIN {certificate} crt 
				ON crt.id = ci.certificateid
			INNER JOIN {course_modules} cm 
				ON cm.course = crt.course
			INNER JOIN {course} c 
				ON c.id = cm.course
			INNER JOIN {context} ctx 
				ON ctx.instanceid = cm.id
			INNER JOIN {files} f 
				ON f.contextid = ctx.id
				
			WHERE	cm.module = 4 AND
					ctx.contextlevel = 70 AND
					f.mimetype = 'application/pdf' AND
					ci.userid = f.userid AND
					ci.userid = :userid
				
			GROUP BY ci.code		
			ORDER BY ci.timecreated ASC";
			// cm.module = 4 -> certificate module
			// ctx.contextlevel = 70 -> CONTEXT_MODULE
			// f.mimetype = 'application/pdf' -> pdf files only
		$certificates = $DB->get_records_sql($sql, array('userid' => $USER->id));

	if (empty($certificates)) {
    	$this->content->text = get_string('noreportcertificates', 'block_report_certificates');
        return $this->content;
    } else {
    	foreach ($certificates as $certdata) {
        $course = $DB->get_record('course', array('id' => $certdata->courseid));	
			
		// Modify printdate so that date is always printed.
        $certdata->printdate = 1;
        $certrecord = new stdClass();
		$certrecord->certificateid = $certdata->certificateid;
		$certrecord->userid = $certdata->userid;
		$certrecord->code = $certdata->code;
        $certrecord->timecreated = $certdata->citimecreated;
		$certrecord->coursemoduleid = $certdata->coursemoduleid;
		$certrecord->courseid = $certdata->courseid;
		$certrecord->fullname = $certdata->fullname;
		$certrecord->contextid = $certdata->contextid;
		$certrecord->itemid = $certdata->itemid;
		$certrecord->filename = $certdata->filename;
			
		$contextid = $certdata->contextid;
		$itemid = $certdata->itemid;
		$filename = $certdata->filename;
		$class = $course->fullname;
			
		$fullname = $certdata->fullname;
		$grade = certificate_get_grade($certdata, $course, $certdata->userid);
		$code = $certdata->code;
		$date = certificate_get_date($certdata, $certrecord, $course, $certdata->userid);
			
		//Direct course link	
		$link = html_writer::link(new moodle_url('/course/view.php', array('id' => $certdata->courseid)), $class, array('fullname' => $class));
			
		//Direct certificate download link
		$filelink = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$contextid.'/mod_certificate/issue/'.$itemid.'/'.$filename);			
		//$outputlink = '<img src="'.$OUTPUT->pix_url(file_mimetype_icon('application/pdf')).'" height="16" width="16" alt="'.'application/pdf'.'" />&nbsp;'.'<a href="'.$filelink.'" >'.'Download'.'</a>';
		$outputlink = '<a href="'.$filelink.'" >'.'<img src="../report_certificates/pix/download.png" alt="Please download" width="100px" height="29px">'.'</a>';						
			
		$table->data[] = array ($link, $grade, $code, $date, $outputlink);
	}
		
	echo $OUTPUT->header();
	echo $OUTPUT->heading("Complete List of Previously Issued Certificates");
	echo '<br />';
	echo html_writer::table($table);
	echo $OUTPUT->footer($course);
}
