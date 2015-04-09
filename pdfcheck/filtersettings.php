<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext('filter_pdfcheck_serverurl', get_string('serverurl', 'filter_pdfcheck'), get_string('explain_setting_url', 'filter_pdfcheck'), 'http://localhost:8080/PDF-Accessibility-Check/rest/'));
}
