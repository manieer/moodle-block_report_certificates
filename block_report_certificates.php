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

require_once($CFG->dirroot.'/config.php');
require_once($CFG->dirroot.'/mod/certificate/lib.php');
require_once($CFG->dirroot.'/mod/certificate/locallib.php');
require_once($CFG->dirroot.'/mod/certificate/archivelib.php');

require_login();
 
class block_report_certificates extends block_base {

    public function init() {
        $this->title   = get_string('report_certificates', 'block_report_certificates');
        $this->version = 2015041302;
    }

    public function get_content() {
        global $USER, $DB, $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $table = new html_table();
		$table->head = array(
						get_string('report_certificates_tblheader_coursename', 'block_report_certificates'),
        			   	get_string('report_certificates_tblheader_grade', 'block_report_certificates'),
        			   	get_string('report_certificates_tblheader_code', 'block_report_certificates'), 
					   	get_string('report_certificates_tblheader_issuedate', 'block_report_certificates'));		
		$table->align = array ("left", "center", "center", "center");
		
		if (has_capability('mod/certificate:view', context_system::instance())) {
    	// Show all user certificates.
    	$where = '';
		} else {
		}
		$sql = "SELECT DISTINCT cm.id AS coursemoduleid, cm.course, cm.module,
				c.name AS certificatename, ci.certificateid, 
				ci.timecreated AS citimecreated, 
				ci.certificateid, ci.userid, ci.code, c.*
				
				FROM {certificate_issues} ci

				INNER JOIN {user} u 
					ON u.id = ci.userid
				INNER JOIN {certificate} c 
					ON c.id = ci.certificateid
				INNER JOIN {course_modules} cm 
					ON cm.course = c.course
				
				WHERE ci.userid = :userid && cm.module = 4
				GROUP BY ci.code
				ORDER BY ci.timecreated ASC";
				// MODULE ID = 4 IS THE CERTIFICATE MODULE			
		$certificates = $DB->get_records_sql($sql, array('userid' => $USER->id));
		
        if (empty($certificates)) {
            $this->content->text = get_string('report_certificates_noreports', 'block_report_certificates');
            return $this->content;
        } else {
    		
			foreach ($certificates as $certdata) {
        	$course = $DB->get_record('course', array('id' => $certdata->course));		
       		
			// Modify printdate so that date is always printed.
        	$certdata->printdate = 1;

        	$certrecord = new stdClass();
        	$certrecord->timecreated = $certdata->citimecreated;
        	$certrecord->code = $certdata->code;
        	$certrecord->userid = $certdata->userid;
			$certrecord->certificateid = $certdata->certificateid;
			$certrecord->coursemoduleid = $certdata->coursemoduleid;
			$certrecord->certificatename = $certdata->certificatename;
					
        	$date = certificate_get_date($certdata, $certrecord, $course, $certdata->userid);
        	$grade = certificate_get_grade($certdata, $course, $certdata->userid);			
        	$code = $certdata->code;
        	$class = $course->fullname;
			
			// link to course page
			$link = html_writer::link(new moodle_url('/course/view.php', array('id' => $certdata->course)), $class, array('fullname' => $class));
			
			// Certificate download link directly from the course page if required.
			// You just need to add a new column on the table and output $download_link. 
			// $download_link = html_writer::link(new moodle_url('/blocks/report_certificates/download.php', array('id' => $certdata->coursemoduleid)), 'Download', array('Download'));					
			
			$table->data[] = array ($link, $grade, $code, $date);
			
		}
		$this->content->footer = html_writer::link(new moodle_url('/blocks/report_certificates/report.php', array('userid' => $USER->id)), get_string('report_certificates_footermessage', 'block_report_certificates'));
		
      }
        $this->content->text = html_writer::table($table);
        return $this->content;
    }
}
