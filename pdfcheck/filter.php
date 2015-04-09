<?php
require_once ("$CFG->dirroot/config.php");
class filter_pdfcheck extends moodle_text_filter{

    public function filter($text, array $options = array()){
        global $COURSE, $CFG;
	if(empty($CFG->filter_pdfcheck_serverurl)){
		return str_replace('accessibility_check_for_pdfs',  get_string('noserverurl', 'filter_pdfcheck'), $text);
	}else{
		$courseid = $COURSE->id;
		$link = html_writer::link("../filter/pdfcheck/courses/accessibility.php?courseid=$courseid", get_string('linkname', 'filter_pdfcheck'));
		return str_replace('accessibility_check_for_pdfs', $link, $text);
	}
    }
}

?>
