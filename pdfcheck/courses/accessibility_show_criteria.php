<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once ("$CFG->dirroot/mod/resource/lib.php");
require_once ("$CFG->libdir/resourcelib.php");
#require_login();
require_once("$CFG->dirroot/user/profile/lib.php");
require_once('lib.php');
require_once('../lang/en/filter_pdfcheck.php');
global $USER;
$userid = $USER->id;

if (isguestuser()) {
    die();
}

$courseid = $_GET["courseid"];
require_course_login($courseid, true);
// Set up page.
$PAGE->set_context(context_system::instance());

$PAGE->set_context(context_course::instance($courseid));
$title = get_string('criterion_information', 'filter_pdfcheck');
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

$criterea = getAllCheckCriteriaNames();

foreach($criterea as $key => $value){
    $criterea[$key] = $value . ' ['.$key.']';
}

// print information to all criteria 

//table of content
echo '<h1><a name="contenttable">' . get_string('content', 'filter_pdfcheck') . '</a></h1>';
$criteria_out = array();
foreach($criterea as $key => $value){
    $criteria_out[$key] = '<a href="#' . $key . '">' . $value . '</a><br/>';
}
array_unshift($criteria_out, '<a href="#' . get_string('criteria_info_intro_title', 'filter_pdfcheck') . '">' . get_string('criteria_info_intro_title', 'filter_pdfcheck') . '</a><br/>');
echo html_writer::alist($criteria_out, null, 'ol');

//intro
echo '<h1><a name="'. get_string('criteria_info_intro_title', 'filter_pdfcheck') .'">' . get_string('criteria_info_intro_title', 'filter_pdfcheck') . '</a></h1>';
echo '<a href="#contenttable">'. get_string('on_top', 'filter_pdfcheck') .'</a><br/><br/>';
echo get_string('criteria_info_intro', 'filter_pdfcheck');
echo '<br/><br/><br/>';

//content
$programs = array();
$check = false;
$finde = 'criteria_info_name_';
foreach($string as $key => $val){
    if(strpos($key, $finde) !== false)
            $programs[$key] = $val;
}

foreach($criterea as $key => $value){
    echo '<h1><a name="'. $key .'">' . $value . '</a></h1>';
    echo '<a href="#contenttable">'. get_string('on_top', 'filter_pdfcheck') .'</a><br/><br/>';
    foreach($string as $key_in => $val_in){
        if(strpos($key_in, $key)){
            $check = true;
        }
    }
    if($check){
        echo '<h2>'.get_string('criteria_info_name_info', 'filter_pdfcheck').'</h2>';
        echo get_string('criteria_info_'.$key.'_info', 'filter_pdfcheck');
        echo '<h2>'.get_string('criteria_info_name_OO', 'filter_pdfcheck').'</h2>';
        echo get_string('criteria_info_'.$key.'_OO', 'filter_pdfcheck');
        echo '<h2>'.get_string('criteria_info_name_PP10', 'filter_pdfcheck').'</h2>';
        echo get_string('criteria_info_'.$key.'_PP10', 'filter_pdfcheck');
        echo '<h2>'.get_string('criteria_info_name_W10', 'filter_pdfcheck').'</h2>';
        echo get_string('criteria_info_'.$key.'_W10', 'filter_pdfcheck');
        echo '<br/><br/><br/>';
    }
    else{
        echo get_string('not_available', 'filter_pdfcheck');
        echo '<br/><br/><br/>';
    }
    $check = false;
}

echo $OUTPUT->box_end();
print $OUTPUT->footer();

