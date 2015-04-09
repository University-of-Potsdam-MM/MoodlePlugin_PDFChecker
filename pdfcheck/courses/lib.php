<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once ("$CFG->dirroot/mod/resource/lib.php");
require_once ("$CFG->libdir/resourcelib.php");
require_once($CFG->dirroot.'/user/profile/lib.php');

require_login();

$courseid = $_GET["courseid"];
require_course_login($courseid, true);

/**
 * Entfernt Kriterien, die in den Einstellungen deakiviert wurden
 * @param $criteria - als Array, in dem alle Kriterien stehen
 * @return Gibt ein Array mit Kriterien zurück, die auch angezeigt werden sollen
 */
function delete_criteria($criteria){
    GLOBAL $USER;
    $theuser = clone($USER);
    profile_load_data($theuser);
    $finde = 'profile_field_pdfcheck';
    $theuserarray = (array) $theuser;
    $userprofile = array();
    foreach($theuserarray as $key => $val){
        if(strpos($key, $finde) !== false)
            $userprofile[str_replace((array)$finde,(array)'',$key)] =  $theuserarray[$key];
    }

    foreach($criteria as $key => $value){
        if($userprofile[str_replace((array)'_',(array)'',$key)] == '0'){
            unset($criteria[$key]);
        }
    }

    return $criteria;
}

/**
 * Returns the file name
 * @param $course_module_id the instanceid of the context from database table
 * @return name of the file
 **/
function file_name($course_module_id){
    global $DB;
    $conditions = array(
        'id'=>$course_module_id
    );
    $resource_id = $DB-> get_records_menu('course_modules',$conditions,'id','id, instance');
    $tmp_id = $resource_id[$course_module_id];
    $conditions = array(
        'id'=>$tmp_id
    );
    $file_name = $DB-> get_records_menu('resource',$conditions,'id','id, name');
    return $file_name[$tmp_id];
}

/**
 * Checks if a section of a course is visible for this user
 * @param $section: from DB-Table (in database -> course_modules)
 * @return 1, if the file is visible; 0, otherwise
 *
 **/
function file_visible($section){
    // mdl_course_moduls[id] == mdl_course_sections[sequence]; 
    global $DB;
    $conditions = array(
        'id'=>$section,
        'visible' => 1
    );
    $visible = $DB->record_exists('course_modules', $conditions);
    if(empty($visible)){
        return 0;
    }
    return $visible;
}

/**
 * Checks if the user is a trainer for this course.
 * @return 1, if the user a trainer; 0 otherwise
 *
 **/
function file_trainer_visible(){
    global $USER, $course;
    $userid = $USER->id;
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $editingteachers = get_role_users(3, $context);
    foreach($editingteachers as $i => $value){
        if($value->id == $userid){
            return 1;
        }
    }
        $teacher = get_role_users(4, $context);
        foreach($teacher as $i => $value){
            if($value->id == $userid){
                return 1;
            }
        }
    return 0;
}

/**
 * Returns if a file is visible for this user.
 * @param $contenthash contenthash of the file
 * @return 1, if the file visible; 0, otherwise
 *
 **/
function file_user_visible($contenthash){
    global $USER;
    global $DB;
    $userid = $USER->id;
    $conditions = array(
        'contenthash'=>$contenthash,
        'userid' => $userid,
        'filearea'=>'content'
        );
    $is_user = $DB->record_exists('files', $conditions);

    if(empty($is_user)){
        return 0;
    }
    return $is_user;

}

/**
 * Returns the path of a file.
 * @param $contenthash contenthash of the file
 * @param $fileid the instanceid from the context from database table
 * @return path of a file
 *
 **/
function get_file_path($contenthash, $fileid){
    global $CFG;
    global $DB;
    global $OUTPUT;
    $mooledata_path = $CFG->dataroot;
    $mooledata_path = $mooledata_path.DIRECTORY_SEPARATOR.'filedir'.DIRECTORY_SEPARATOR;
    $file_path = $mooledata_path.substr($contenthash, 0,2).DIRECTORY_SEPARATOR.substr($contenthash,2,2).DIRECTORY_SEPARATOR.$contenthash;

    $class = true; // $class = $cm->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $conditions = array(
        'contenthash'=>$contenthash,
        'filearea' => 'content'
    );
    $mimetype = $DB-> get_records_menu('files',$conditions,'id','contenthash, mimetype');
    $icon_mimetype = mimetype_to_icon($mimetype[$contenthash]);
    $icon = '<img src="'.$OUTPUT->pix_url($icon_mimetype).'" class="activityicon" alt="'.get_string('modulename', 'resource').'" /> ';
    $file_name= file_name($fileid);
    $file_link = ("<a $class href=\"$CFG->wwwroot/mod/resource/view.php?id=$fileid\">".$icon.format_string($file_name)."</a>");

    return array(
        'file_path' => $file_path,
        'link'=> $file_link
    );
}

/**
 * Tests whether the file have to be checked on server or if the result from the database can be used.
 * @param $contenthash contenthash of the file
 * @param $filename path of the file
 * @return the check result of a file
 **/
function get_test_resulte($contenthash, $filename){
    global $DB;
    $conditions = array(
        'contenthash' => $contenthash
    );
    $exist = $DB->record_exists('filter_pdfcheck', $conditions);
    if(empty($exist)){ // the file has to been checked
        //call check
	$jsonstring = check_pdf_file_at_server($filename);
        write_testresult_on_db($contenthash, $jsonstring);    
    }else{
        //if it is a new version of the file --> check again
        // filter_pdfcheck(time_checked)>= resources(timemodified) [or files(timemodified)]
        $bedingungFile = array(
            'contenthash' =>$contenthash,
            'mimetype' =>'application/pdf',
            'filearea'=>'content'
        );
        // $bedingungPdfCheck = array('contenthash' =>$contenthash);
        $timeFile = $DB-> get_records_menu('files', $bedingungFile,'id','contenthash, timemodified');
        $timeCheck = $DB-> get_records_menu('filter_pdfcheck', $conditions,'id','contenthash, time_checked');

        if($timeCheck[$contenthash] <= $timeFile[$contenthash]){
            // call check
	    $jsonstring = check_pdf_file_at_server($filename);
            write_testresult_on_db($contenthash, $jsonstring);  
        }
    }
    // the file is now checked --> return result
    $criterias = getAllCheckCriteria();
    $crits = array();
    foreach($criterias as $key => $value){
        $crit = $DB->get_records_menu('filter_pdfcheck',$conditions,'id', 'contenthash, '.$value);
        $crits[$value] = $crit[$contenthash];
    }
    return $crits;
}

/**
 * converts the result of a check criterion to a string
 * @param $number the result of a check criterion
 * @return 'nicht ermittelt', if NULL; 'nein', if 0; 'ja', if 1; 'nicht zutreffend', if -1; 'unbekannt' otherwise
 **/
function int_to_out_string($number){
    if(!is_numeric($number)){
        return "nicht ermittelt";
    }
    switch ($number){
        case 0:
            return 'nein';
        case 1:
            return 'ja';
        case -1: 
            return 'nicht zutreffend';
        case -2:
            return '-';
        default:
            return 'unbekannt';        
    }
}

/**
 * Returns the icon name of a mimetype
 * @param $mimetype mimetyp of a file
 * @return Icon name
 **/
function mimetype_to_icon($mimetype){
    switch ($mimetype){
        case 'application/pdf':
            return 'f/pdf-24';
        case 'text/plain':
            // it seems that it isn't only .txt but also e.g. .php
            return 'f/text-24';
        case 'application/vnd.oasis.opendocument.presentation':
            return 'f/impress-24';
        case 'application/x-tar':
            return 'f/archive-24';
        case 'application/zip':
            return 'f/archive-24';
        case 'audio/ogg':
            return 'f/audio-24';
        case 'application/x-troff-msvideo':
            return 'f/avi-24';
        #case '':
        #    return 'f/base-24';
        case 'image/bmp':
            return 'f/bmp-24';
        case 'application/vnd.oasis.opendocument.spreadsheet':
            return 'f/calc-24';
        case 'application/vnd.oasis.opendocument.chart':
            return 'f/chart-24';
        case 'application/vnd.oasis.opendocument.database':
            return 'f/database-24';
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            return 'f/document-24';
        case 'application/msword':
            return 'f/document-24';
        case 'application/vnd.oasis.opendocument.graphics':
            return 'f/draw-24';
        case 'application/postscript':
            return 'f/eps-24';
        case 'application/epub+zip':
            return 'f/epub';
        case 'application/x-shockwave-flash':
            return 'f/flash-24';
        #case '':
        #    return 'f/folder-24';
        case '     image/gif':
            return 'f/gif-24';
        case 'text/html':
            return 'f/html-24';
        case 'application/octet-stream ':
            return 'f/isf-24';
        case 'image/jpeg':
            return 'f/jepg-24';
        #case '':
        #    return 'f/markup-24';
        case 'application/vnd.sun.xml.math':
            return 'f/math-24';
        case 'audio/mp3':
            return 'f/mp3-24';
        case 'video/mpeg':
            return 'f/mpeg-24';
        case 'application/vnd.oasis.opendocument.text-web':
            return 'f/oth-24';
        case 'image/png':
            return 'f/png-24';
        case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
            return 'f/powerpoint-24';
        case 'image/photoshop':
            return 'f/psd-24';
        case 'application/photoshop':
            return 'f/psd-24';
        case 'video/quicktime':
            return 'f/quicktime-24';
        #case '':
        #    return 'f/sourcecode-24'; // not tested because it's the same mimetype like text/plain (e.g. .java; .php)
        case 'text/csv':
            return 'f/spreadsheet-24';
        case '     image/tiff':
            return 'f/tiff-24';
        #case '':
        #    return 'f/video-24';
        case 'audio/wav':
            return 'f/wav-24';
        case 'video/x-ms-wmv':
            return 'f/wmv-24';
        case 'application/vnd.oasis.opendocument.text':
            return 'f/writer-24';
        case 'document/unknown':
            return 'f/unknown-24';
        default:
            return 'f/unknown-24';
    }
}

/**
 * Calls all check criterions from server, compare them with the ones from database and delete all differences.
 * @return all check criterions of database (similar to install.xml)
 */
function getAllCheckCriteria(){
	global $CFG;
	$filename = $CFG->dirroot;
	$filename = $filename.DIRECTORY_SEPARATOR.'filter'.DIRECTORY_SEPARATOR.'pdfcheck'.DIRECTORY_SEPARATOR.'db'.DIRECTORY_SEPARATOR.'install.xml';
        $xml = file_get_contents($filename);
	$pattern = array();
	$pattern[0] = "/.*<FIELDS>/sm";
	$pattern[1] = "/<\/FIELDS>.*XMLDB>/sm";
	$replace = array();
	$fields = preg_replace($pattern, $replace, $xml);
	$fieldArray = preg_split ("/\r\n|\n|\r/", $fields);
	foreach ($fieldArray as $id => $line) {
		$fieldArray[$id] = preg_replace("/.*NAME=\"(.*?)\".*/","\${1}",$line);
	}
	$fieldArray = array_filter($fieldArray, "is_not_empty");
	foreach ($fieldArray as $id => $line) {
		if($line == "id" or $line == "contenthash" or $line == "time_checked"){
			unset($fieldArray[$id]);
		}
	}
	$fieldArrayTmp = $fieldArray;
	$fieldArray = array_values($fieldArray);
	return $fieldArray;
}

/**
 * Tests if a string is not empty.
 * @param $string string to compare
 * @return true, if the string not empty; false, otherwise
 **/
function is_not_empty($string) {
	return preg_match("/\w/",$string);
}


/**
 * Gets all check criterion names from server and compares them whith the fields from the database.
 * @return Array with all check criterion from database; key = database name; value = name in local language of moodle user
 **/
function getAllCheckCriteriaNames(){
	global $USER, $CFG;
	$serverurl = $CFG->filter_pdfcheck_serverurl;
	if (!isguestuser()){
		$lang_user = $USER->lang;
	}else{
		$lang_user = "en";
	}
	$serverurl = $serverurl."getCheckCriterionNames?locale=".$lang_user;
	$ch = curl_init($serverurl);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$resp = curl_exec($ch);
	curl_close($ch);
	$jsonArray = json_decode($resp, true);
	$jsonArray = $jsonArray["Criterion"];
	$criteriaDb = getAllCheckCriteria();
	for($i = 0; $i < count($criteriaDb); $i++){
		foreach ($jsonArray as $id => $value) {
			if($criteriaDb[$i] == $value["dbName"]){
				unset($jsonArray[$id]);
				$checkCriteria[$value["dbName"]] = $value["name"];
			}
		}
	}
	return $checkCriteria;		
}

/**
 * Checks a pdf-file at server.
 * @param $path path to the local file
 * @return JSON-String whith the check result
 **/
function check_pdf_file_at_server($path){
	global $CFG;
	$serverurl = $CFG->filter_pdfcheck_serverurl;
	$serverurl = $serverurl."checkPdfFile?path=".$path;
	$ch = curl_init($serverurl);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$resp = curl_exec($ch);
	curl_close($ch);
	$jsonstring = json_decode($resp, true);
	$jsonstring = delete_result_if_not_in_db($jsonstring);
	return $jsonstring;
}

/**
 * Deletes all criterions which aren't in the database.
 * @param $jsonstring JSON-String whith the check result from server
 * @return check criterion results to write on database
 **/
function delete_result_if_not_in_db($jsonstring){
	$checkCriteria = getAllCheckCriteria();
	$dbToAdd = array();
	foreach($checkCriteria as $id2 => $criteria){
		foreach ($jsonstring["testResults"] as $id => $content) {
			if($content["dbName"] == $criteria){
				$dbToAdd[$criteria] = $content;
			}
		}
  	}
	return $dbToAdd;
}

/**
 * Write check criterion results to the database.
 * @param $contenthash contenthash of checked file
 * @param $jsonstring JSON-String whith the check result
 **/
function write_testresult_on_db($contenthash, $jsonstring){
    global $DB;
    // check whether the file was already checked
    $conditions = array(
        'contenthash' => $contenthash
    );
    $exist = $DB->record_exists('filter_pdfcheck', $conditions);
    if(!empty($exist)){
        // delete old input
        $DB->delete_records('filter_pdfcheck', $conditions);
    }
    $record = new stdClass();
    $record->contenthash = $contenthash;
    $record->time_checked = time();
    foreach ($jsonstring as $id => $content) {
	if($content["useCount"]){
		$record->$content["dbName"] = $content["count"];    
	}else{
		$record->$content["dbName"] = $content["success"];
	}
    }
    $DB->insert_record('filter_pdfcheck', $record, false);
}

/**
 *
 * Returns database name and type of check criterions (result taken from server).
 * @return database name and type
 **/
function getCheckCriterionNamesAndType(){
	global $CFG;
	$serverurl = $CFG->filter_pdfcheck_serverurl;
	$serverurl = $serverurl."getCheckCriterionNamesAndType";
	$ch = curl_init($serverurl);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$resp = curl_exec($ch);
	curl_close($ch);
	$jsonArray = json_decode($resp, true);
	$jsonArray = $jsonArray["Criterion"];
	$criteriaDb = getAllCheckCriteria();
	for($i = 0; $i < count($criteriaDb); $i++){
		foreach ($jsonArray as $id => $value) {
			if($criteriaDb[$i] == $value["dbName"]){
				unset($jsonArray[$id]);
				$checkCriteria[$value["dbName"]] = $value["type"];
			}
		}
	}
	return $checkCriteria;		
}