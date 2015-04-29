<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once ("$CFG->dirroot/mod/resource/lib.php");
require_once ("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/user/profile/lib.php");
require_once('lib.php');
global $USER;
$userid = $USER->id;

if (isguestuser()) {
    die();
}

$courseid = $_GET["courseid"];
require_course_login($courseid, true);
$PAGE->set_context(context_system::instance());
$PAGE->set_context(context_course::instance($courseid));
$title = get_string('settings', 'filter_pdfcheck');
$bedingung = array(
    'id'=>$courseid
);
$course = $DB->get_record('course', $bedingung, '*', MUST_EXIST);
$PAGE->set_heading($course->fullname);
$PAGE->set_title($course->shortname.': '.$title);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add($title);

print $OUTPUT->header();
echo $OUTPUT->box_start('generalbox sitetopic');

echo '<legend>'.get_string('setting_info_text', 'filter_pdfcheck').'</legend>';
echo "<form action=\"accessibility_save_to_profile.php?courseid=$courseid\" method=\"post\">";

$finde = 'profile_field_pdfcheck';
$theuser = clone($USER);
profile_load_data($theuser);
$theuserarray = (array) $theuser;
$userprofile = array();
foreach($theuserarray as $key => $val){
   if(strpos($key, $finde) !== false)
       $userprofile[str_replace((array)$finde,(array)'',$key)] =  $theuserarray[$key];
}

// prints checkboxes with all information form profile
$criterea = getAllCheckCriteriaNames();
foreach($criterea as $key => $value){
    if($userprofile[str_replace((array)'_',(array)'',$key)] == "1"){
        $criterea[$key] = '<input type="checkbox" name="profile_field_pdfcheck'.str_replace((array)'_',(array)'',$key).'" value='.$key.'" checked/>'.$value;
    }
    else{
        $criterea[$key] = '<input type="checkbox" name="profile_field_pdfcheck'.str_replace((array)'_',(array)'',$key).'" value='.$key.'"/>'.$value;
    }
}

echo html_writer::alist($criterea, null, 'ol');

echo '<br/>';
echo get_string('list_view_text', 'filter_pdfcheck');
if($theuserarray['profile_field_pdflistortable'] == 0){
    $listview[] = '<input type="checkbox" name="pdflistortable" value="'.get_string('list_or_table_checkbox', 'filter_pdfcheck').'"/>'.get_string('list_or_table_checkbox', 'filter_pdfcheck');
}
else{
    $listview[] = '<input type="checkbox" name="pdflistortable" value="'.get_string('list_or_table_checkbox', 'filter_pdfcheck').'" checked/>'.get_string('list_or_table_checkbox', 'filter_pdfcheck');
}
echo html_writer::alist($listview, null, 'ol');


echo "<input type=\"hidden\" name=\"courseid\" value=\"$courseid\">";
echo '<input type="submit" value="'.get_string('submit_button', 'filter_pdfcheck').'"></form>';

echo $OUTPUT->box_end();
print $OUTPUT->footer();

