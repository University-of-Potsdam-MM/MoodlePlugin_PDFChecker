# MoodlePlugin_PDFChecker

This plugin checks local pdf files for its accessibility by using the PDF-Accessibility-Check-Server.
This plugin was developed for Moodle 2.5.

Important for this filter is the local available webservice for accessibility checking of pdf files (PDF-Accessibility-Check-Server). More information about this can be found here: https://github.com/University-of-Potsdam-MM/PDF-Accessibility-Check

The folder "pdfcheck" have to be copied in the moodle filter folder. After this, a Moodle administrator has to login in Moodle. Now the database will ask for an update. This has  to be confirmed. The plugin has to be activated. To achieve this follow these steps:
In the menu  "Administration" go to "Site administration". Here you have to go to "Plugins" -> "Filters" -> "Manage filters". Now you see all available filters. Activate the filter "PDF-accessebility-check".
After that tell moodle where the webservice is. To do this go in Moodle to "Administration" -> "Site administration" -> "Plugins" -> "Plugins overview". Find "PDF-accessebility-check" and click on "Settings". Fill in the url to the webservice e.g. "http://localhost:8080/PDF-Accessibility-Check/rest/".

To use the filter as lecturer you have to write "accessibility_check_for_pdfs" on the welcome page of a course. The filter translates the string into a link to the accessibility check.

The "pdfcheckprofile" folder must be copied into moodle user folder: /user/profile/field/. An administrator installs the plugin after logging in to Moodle.
Now all profile fields must be created manually. To do this, as an administrator, go to "Site administration"-> "Users"-> "Accounts"-> "user profile fields". Make sure that in "create a new profile field:" "pdfcheckprofile" is selected.
The "short name" consists of "pdfcheck" and the identifier of the criterion. You can find the latter about the plugin page on "information about criteria". The names in brackets are the identifiers.
Example: 
Criteria: print document
Short name: pdfcheckprint_document
(don't be surprised: the underscore is removed by Moodle!)
The name can be free and should be chosen according to the target group.
"Save changes" specifies the profile field.
This procedure must be repeated for all the criteria.
Most recently, a check box to select the list view must be added. To do this proceed as the criteria, select as "short name" but "pdflistortable". "name" can be chosen freely again. Important: Here for "Checked by default" "No" must be selected when you create it!

Also see "README_Adding_Criteria.txt" for adding new criterions.