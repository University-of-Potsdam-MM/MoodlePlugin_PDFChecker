Introduction to add new check criteria: 
=========================================

1. step - adding the new criterion to the Moodle database
---------------------------------------------------------
- set read permissions for the install.xml of the filter (UNIX: in moodle/filter/pdfcheck "sudo chmod o+w db/install.xml")
- login in Moodle as administrator
- open the XMLDB-Editor (Administration --> Site administration --> Development --> XMLDB editor)
- click on "Load" near the filter "filter/pdfcheck/db" and following "Edit"
- click in the menu "Tables" on "Edit"
- click on "New field"
- edit the name, type etc. for the new criterion and confirm with "Change"

Now the new database field has to added to the PHP code.
- click on "View PHP code"
- choose the created criterion under "Select field/key/index"
- click on "view"
- copy the generated code and past it in to "moodle/filter/pdfcheck/db/upgrade.php" IMPORTANT: replace "XXXXXXXXXX" with the current date in the format "YYYYMMDDXX". That's the version number-- The last two X's are to differentiate two or more changes one the same day. It has to be replaced with scaling up numbers e.g. "00", "01", ....
- edit the version number in "moodle/filter/pdfcheck/version.php"
- change the permissions for the install.xml, writing should no longer be permitted (UNIX: sudo chmod o-w db/install.xml)
- (as administrator) reload the main Moodle page
- the plugin wants to update the database, confirm this

2. step - adding the new criterion to the user profile settings
---------------------------------------------------------------
An administrator goes to "Site administration"-> "Users"-> "Accounts"-> "user profile fields". In "create a new profile field:" "pdfcheckprofile" is selected.
The "short name" consists of "pdfcheck" and the identifier of the criterion. You can find the latter about the plugin page on "information about criteria". The names in brackets are the identifiers.
Example: 
Criteria: print document
Short name: pdfcheckprint_document
(don't be surprised: the underscore is removed by Moodle!)
The name can be free and should be chosen the target group according to.
"Save changes" specifies the profile field.

3. step - adding the new criterion to the "information about criteria"-site
---------------------------------------------------------------
The plugin language files must be edited. Finding in folder pdfcheck/lang/, then in each language folder each of the "filter_pdfcheck.php"
Expand the array "$string" to the desired entries. The texts have all the form such as
"$string ['criteria_info_CRITERIONNAME_PART'] = 'text';" 
(without quotation)
Replace CRITERIONNAME with the desired name of the criterion (with underscores!) and TEXT by
-"info": then text would be for the general description of the criterion
-"OO": then text would be for the description for OpenOffice and LibreOffice
-"PP10": then text would be for the description for MS PowerPoint 2010
-"W10": then text would be for the description for MS Word 2010
Insert these lines in each language file. A lack can lead to PHP errors in their language!
