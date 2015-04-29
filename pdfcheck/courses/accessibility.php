<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once ("$CFG->dirroot/mod/resource/lib.php");
require_once ("$CFG->libdir/resourcelib.php");
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once('lib.php');

require_login();

$courseid = $_GET["courseid"];
require_course_login($courseid, true); // this works only for courses where this user is registered
// Set up page.
$PAGE->set_context(context_system::instance());

$PAGE->set_context(context_course::instance($courseid));
$title = get_string('title', 'filter_pdfcheck');
$bedingung = array(
    'id'=>$courseid
);
$course = $DB->get_record('course', $bedingung, '*', MUST_EXIST); // similar to $COURSE
$PAGE->set_heading($course->fullname);
$PAGE->set_title($course->shortname.': '.$title);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add($title);
$PAGE->set_url(new moodle_url($CFG->wwwroot.$SCRIPT, array('courseid'=>$course->id)));

print $OUTPUT->header();
echo $OUTPUT->box_start('generalbox sitetopic');
write_table_for_course_files();
echo $OUTPUT->box_end();
print $OUTPUT->footer();

/**
 * Seeks all files from this course (only if the files are on the start page) and shows the check results.
 **/
function write_table_for_course_files(){
    global $DB, $course, $USER;
    $courseid = $course->id;
    $conditions = array(
        'course'=>$courseid
    );

    $course_files_tmp = $DB-> get_records_menu('course_sections',$conditions,'id','id, sequence');
    foreach($course_files_tmp as $i =>$value){
        if( !empty($course_files_tmp[$i]) ){// is there at least one file in the course?
            $course_files[$i] = $course_files_tmp[$i];
            $conditions = array(
                'id'=>$i
            );
            $section_tmp[$i] = $DB-> get_records_menu('course_sections',$conditions,'id','id, section');
        }
    }

    foreach($section_tmp as $i =>$value){
        $section[$i] = $section_tmp[$i][$i];
    }
    
    foreach($course_files as $i => $value){
        //test if there is more thean one file in one "section" and save the relation file <-> section
        $files_tmp = preg_split('/,/',$course_files[$i]);
        $tmp= array();
        foreach($files_tmp as $j => $file){
            $tmp[$j] = $file;
            $bedingung = array(
                'instanceid' =>$file,
                'contextlevel' => '70'
                );
            $test = $DB-> get_records_menu('context', $bedingung,'id','instanceid, id');
            $course_files_contextid[$file] = $test[$file];
            // is the file visible for the user?
            $visible[$file] = file_visible($file);
        }
        $section_sequenz[$section[$i]] = $tmp;
    }
    
    foreach($section_sequenz as $section => $value){
        foreach($value as $i => $sequenz){
            $sequenz_section[$sequenz] = $section;
        }
    }

    foreach($course_files_contextid as $i => $value){
        $select = 'contextid = '.$course_files_contextid[$i].' AND filearea = "content" AND filename != "."'; 
        $test = $DB->get_records_select_menu('files', $select, null, 'contextid', 'contextid, contenthash');
        if(!empty($test[$course_files_contextid[$i]])){
            $course_pdf_filesTEST[$i] = $test[$course_files_contextid[$i]];
        }
    } 
    
    // checks if there is any pdf-file in the course
    if (!$course_pdf_filesTEST) {
        notice(get_string('thereareno', 'moodle', 'PDF-Dateien'), "$CFG->wwwroot/course/view.php?id=$courseid");
        exit;
    }
    
    $tablehead['name'] = get_string('name');
    $tablehead['section'] = get_string('sectionname', 'format_'.$course->format);
    $tablehead['ampel'] = get_string('traffic_light', 'filter_pdfcheck');
    $tablehead['criteria'] = get_string('criterion', 'filter_pdfcheck');
    
    foreach($course_pdf_filesTEST as $i => $value){
        $file_path = get_file_path($value, $i);
        $section = $sequenz_section[$i];
        // checks the mimetype of the file
        $bedingung = array(
                    'contextid' =>$course_files_contextid[$i],
                    'mimetype' =>'application/pdf',
                    'filearea'=>'content');
        if($DB->record_exists('files', $bedingung)){
            $pdfFile['name'] = $file_path['link'];
            $pdfFile['section'] = $printsection = get_section_name($courseid, $section);
            $tests = get_test_resulte($value, $file_path['file_path']);
            
            
            //#START: divide array for first light signal (yes/no) and second others (criteria with other values that only printed)
            $get = getCheckCriterionNamesAndType();
            $crit4light = array();
            $critN4light = array();
            foreach($get as $key => $value){
                if($value == "boolean")
                    $crit4light[$key] = $tests[$key];
                else
                    $critN4light[$key] = $tests[$key];
            }
            //#END
            
            $crit4light = delete_criteria($crit4light);
            $critN4light = delete_criteria($critN4light);
            $pdfFile['ampel'] = generate_light($crit4light);
            $critnames = getAllCheckCriteriaNames();
            $critsYN = array();
            foreach($crit4light as $key => $value){
                $critsYN[] = $critnames[$key].' '.getYesNoByCrit($value, $critnames[$key]);
            }
            $critsS = array();
            foreach($critN4light as $key => $value){
                if($value == "-1"){
                    $critsS[] = $critnames[$key].' <i>'.get_string('unknown','filter_pdfcheck').'</i>';
                }
                else{
                    $critsS[] = $critnames[$key].' <i>'.$value.'</i>';
                }
            }
            
            $crits = array_merge($critsYN, $critsS);
            $pdfFile['criteria'] = $crits;
            $pdfFiles[] = $pdfFile;
        }
    }
    $theuser = clone($USER);
    profile_load_data($theuser);

    if (!isguestuser()){
        echo '<div>';
        echo html_writer::link("../courses/accessibility_edit.php?courseid=$courseid", get_string('settings', 'filter_pdfcheck'));
        echo '</div>';
        echo '<div>';
        echo html_writer::link("../courses/accessibility_show_criteria.php?courseid=$courseid", get_string('criterion_information', 'filter_pdfcheck'));
        echo '</div>';
    }

    if($theuser->profile_field_pdflistortable == 1){
        foreach($pdfFiles as $key => $value){
            foreach($value['criteria'] as $key2 => $value2){
                if($key2 == 0){
                    $critsstring .= $value2;
                }
                else{
                    $critsstring .= ', '.$value2;
                }
            }
            $list[] = $value['name'] . ' (' . $value['section'] . '): '.$value['ampel'].' '.$critsstring;
            $critsstring = '';
        }
        echo html_writer::alist($list, null, 'ol');
    }
    else{
        $table = new html_table();
        $table->attributes['class'] = 'generaltable mod_index';
        $table->head = $tablehead;
        foreach($pdfFiles as $key => $value){
            $pdfFiles[$key]['criteria'] = html_writer::alist($value['criteria'], null, 'ol');
        }
        $table->data = $pdfFiles;  
        echo html_writer::table($table);
    }
}


/**
 * Switch 1 or 0 to yes or no and gives link to no
 * @param $crit - criterion to switch
 * @param $critname - identifier of criterion ($crit)
 * @return String includes html
**/
function getYesNoByCrit($crit, $critname){
    global $course;
    $courseid = $course->id;
    if($crit == 0){
        $context2 = get_context_instance(CONTEXT_COURSE, $courseid);
        $rechte = has_capability('moodle/course:managefiles', $context2);
        if($rechte){
            $critOrgNames = getAllCheckCriteriaNames();
            $firstCrit = array_keys($critOrgNames, $critname);
            $firstCrit = $firstCrit[0];
            return '<span style="color:red"><strong>'.get_string('no', 'filter_pdfcheck').'</strong></span> '.html_writer::link("accessibility_show_criteria.php?courseid=$courseid#".$firstCrit, "Info");
        }
        return '<span style="color:red"><strong>'.get_string('no', 'filter_pdfcheck').'</strong></span>';
    }
    elseif($crit == 1){
        return '<span style="color:green">'.get_string('yes', 'filter_pdfcheck').'</span>';
    }
    else{
        return get_string('unknown', 'filter_pdfcheck');
    }
}

/**
 * get the light signal to a document
 * @param $testresult - result of criteria of a document
 * @return String includes html (light signal link)
 **/
function generate_light($testresult){
    $lightVal=50;
    if(is_array($testresult) && isset($testresult)){
        $parts = count($testresult);
        $yes = 0;
        $no = 0;
        $maybe = 0;
        foreach($testresult as $key => $val){
            switch($val){
                case 0:
                    $no++;
                    break;
                case 1:
                    $yes++;
                    break;
                case -2:
                    $maybe++;
                    break;
                default:
                    $maybe++;
                    break;
            }
        }
        if($yes == $parts){
            return '<img src="pic/ampel_gruen_quer.png" alt="'.get_string('signal_light_green','filter_pdfcheck').'">';
        }
        elseif((($yes*100)/$parts)<$lightVal){
//            var_dump($no);
            return '<img src="pic/ampel_rot_quer.png" alt="'.get_string('signal_light_red','filter_pdfcheck').'">';
        }
    }
    return '<img src="pic/ampel_gelb_quer.png" alt="'.get_string('signal_light_yellow','filter_pdfcheck').'">';
}


