<?php
 
function xmldb_filter_pdfcheck_upgrade($oldversion = 0) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
 
    $result = TRUE;
 
// Insert PHP code from XMLDB Editor here

    if ($oldversion < 2014012600) {

        // Define table filter_pdfcheck to be created.
        $table = new xmldb_table('filter_pdfcheck');

        // Adding fields to table filter_pdfcheck.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, null, null, null);
        $table->add_field('marked', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('print_document', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('print_document_hq', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('copy', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('annotation', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('accessibility_copy', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('outlines', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('time_checked', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('image_count', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('show_document_title', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('consistent_headlines', XMLDB_TYPE_INTEGER, '10', null, null, null, '-1');

        // Adding keys to table filter_pdfcheck.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for filter_pdfcheck.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pdfcheck savepoint reached.
        upgrade_plugin_savepoint(true, 2014012600, 'filter', 'pdfcheck');
    }

    if ($oldversion < 2015012602) {
        // Define field accessible_fonts to be added to filter_pdfcheck.
        $table = new xmldb_table('filter_pdfcheck');
        $field = new xmldb_field('accessible_fonts', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
	$field = new xmldb_field('page_number', XMLDB_TYPE_INTEGER, '10', null, null, null, -1);
        // Conditionally launch add field accessible_fonts.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfcheck savepoint reached.
        upgrade_plugin_savepoint(true, 2015012602, 'filter', 'pdfcheck');
    }

    if ($oldversion < 2015012603) {
        // Define field accessible_fonts to be added to filter_pdfcheck.
        $table = new xmldb_table('filter_pdfcheck');
        $field = new xmldb_field('document_language_set', XMLDB_TYPE_INTEGER, '1', null, null, null, null);

        // Conditionally launch add field accessible_fonts.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfcheck savepoint reached.
        upgrade_plugin_savepoint(true, 2015012603, 'filter', 'pdfcheck');
    }

    return $result;
}
?>
