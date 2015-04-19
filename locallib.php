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
  * Version details
  * 
  * Report certificates block
  * --------------------------
  * Displays all issued certificates for users with unique codes. 
  * The certificates will also be issued for courses that have been archived since issuing of the certificates 
  *
  * @copyright  2015 onwards Manieer Chhettri | Marie Curie, UK | <manieer@gmail.com>
  * @author     Manieer Chhettri | Marie Curie, UK | <manieer@gmail.com> | 2015
  * @package    block_report_certificates
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/lib.php');

/**
 * Returns the date to display for the certificate.
 *
 * @param stdClass $certificate
 * @param stdClass $certrecord
 * @param stdClass $courseid
 * @param int $userid
 * @return string the date
 */
function get_certificate_date($certificate, $certrecord, $courseid, $userid = null) {
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    // Set certificate date to current time, can be overwritten later.
    $date = $certrecord->timecreated;

    if ($certificate->printdate == '2') {
        // Get the enrolment end date.
        $sql = "SELECT MAX(c.timecompleted) AS timecompleted
                                          FROM {course_completions} c
                                         WHERE c.userid = :userid AND
                                               c.course = :courseid";
        if ($timecompleted = $DB->get_record_sql($sql, array('userid' => $userid, 'courseid' => $courseid))) {
            if (!empty($timecompleted->timecompleted)) {
                $date = $timecompleted->timecompleted;
            }
        }
    } else if ($certificate->printdate > 2) {
        if ($modinfo = certificate_get_mod_grade($course, $certificate->printdate, $userid)) {
            $date = $modinfo->dategraded;
        }
    }
    if ($certificate->printdate > 0) {
        if ($certificate->datefmt == 1) {
            $certificatedate = userdate($date, '%B %d, %Y');
        } else if ($certificate->datefmt == 2) {
            $suffix = certificate_get_ordinal_number_suffix(userdate($date, '%d'));
            $certificatedate = userdate($date, '%B %d' . $suffix . ', %Y');
        } else if ($certificate->datefmt == 3) {
            $certificatedate = userdate($date, '%d %B %Y');
        } else if ($certificate->datefmt == 4) {
            $certificatedate = userdate($date, '%B %Y');
        } else if ($certificate->datefmt == 5) {
            $certificatedate = userdate($date, get_string('strftimedate', 'langconfig'));
        }

        return $certificatedate;
    }

    return '';
}

/**
 * Returns the grade to display for the certificate.
 *
 * @param stdClass $certificate
 * @param stdClass $courseid
 * @param int $userid
 * @return string the grade result
 */
function get_certificate_grade($certificate, $courseid, $userid = null) {
    global $USER, $DB;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if ($certificate->printgrade > 0) {
        if ($certificate->printgrade == 1) {
            if ($course_item = grade_item::fetch_course_item($courseid)) {

                $grade = new grade_grade(array('itemid' => $course_item->id, 'userid' => $userid));
                $course_item->gradetype = GRADE_TYPE_VALUE;
                $coursegrade = new stdClass;
                $coursegrade->points = grade_format_gradevalue($grade->finalgrade,
                                       $course_item, true, GRADE_DISPLAY_TYPE_REAL, $decimals = 2);
                $coursegrade->percentage = grade_format_gradevalue($grade->finalgrade, $course_item,
                                           true, GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals = 2);
                $coursegrade->letter = grade_format_gradevalue($grade->finalgrade, $course_item,
                                       true, GRADE_DISPLAY_TYPE_LETTER, $decimals = 0);

                if ($certificate->gradefmt == 1) {
                    $grade = $coursegrade->percentage;
                } else if ($certificate->gradefmt == 2) {
                    $grade = $coursegrade->points;
                } else if ($certificate->gradefmt == 3) {
                    $grade = $coursegrade->letter;
                }

                return $grade;
            }
        } else { // Print the mod grade.
            if ($modinfo = certificate_get_mod_grade($course, $certificate->printgrade, $userid)) {
                if ($certificate->gradefmt == 1) {
                    $grade = $modinfo->name . $modinfo->percentage;
                } else if ($certificate->gradefmt == 2) {
                    $grade = $modinfo->name . $modinfo->points;
                } else if ($certificate->gradefmt == 3) {
                    $grade = $modinfo->name . $modinfo->letter;
                }

                return $grade;
            }
        }
    } else if ($certificate->printgrade < 0) { // Must be a category id.
        if ($category_item = grade_item::fetch(array('itemtype' => 'category', 'iteminstance' => -$certificate->printgrade))) {
            $category_item->gradetype = GRADE_TYPE_VALUE;

            $grade = new grade_grade(array('itemid' => $category_item->id, 'userid' => $userid));

            $category_grade = new stdClass;
            $category_grade->points = grade_format_gradevalue($grade->finalgrade, $category_item,
                                      true, GRADE_DISPLAY_TYPE_REAL, $decimals = 2);
            $category_grade->percentage = grade_format_gradevalue($grade->finalgrade, $category_item,
                                          true, GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals = 2);
            $category_grade->letter = grade_format_gradevalue($grade->finalgrade, $category_item,
                                      true, GRADE_DISPLAY_TYPE_LETTER, $decimals = 0);

            if ($certificate->gradefmt == 1) {
                $formattedgrade = $category_grade->percentage;
            } else if ($certificate->gradefmt == 2) {
                $formattedgrade = $category_grade->points;
            } else if ($certificate->gradefmt == 3) {
                $formattedgrade = $category_grade->letter;
            }

            return $formattedgrade;
        }
    }

    return '';
}
