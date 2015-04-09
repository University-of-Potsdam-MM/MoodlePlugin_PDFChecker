<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once("$CFG->dirroot/user/profile/lib.php");
global $USER;
$courseid = $_GET["courseid"];

if(isset($_POST) && !empty($_POST)){
    $theuser = clone($USER);
    profile_load_data($theuser);
    $theuserarray = (array) $theuser;
    
    $finde = 'profile_field_pdfcheck';
    foreach($theuserarray as $key => $val){
        if(strpos($key, $finde) !== false)
            if($_POST[$key]){
                $theuserarray[$key] =  '1';
            }
            else{
                $theuserarray[$key] = '0';
            }
    }
    
    if($_POST['pdflistortable']){
        $theuserarray['profile_field_pdflistortable'] = 1;
    }
    else{
        $theuserarray['profile_field_pdflistortable'] = 0;
    }
   
    $theuser = (object) $theuserarray;
    
    profile_save_data($theuser);
    header("Location: accessibility.php?courseid=$courseid");
}
?>
