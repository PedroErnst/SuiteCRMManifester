# SuiteCRMManifester

A command line tool for automatically generating a manifest file and copying
the files into a folder, ready to zip.

# Installation

Clone this repository and run `composer update`.

# Usage

### update

The update command does a git diff and parses the list of files into our manifest
file.

`php manifester update <Path to manifest.php> <Path to SCRM instance> <commit>`

The git diff will return a list of changed files since `commit` and incorporate
this list into the manifest.php

### fulfill

The fulfill command reads the contents of a manifest file, and tries to copy all the 
required files from the specified instance to the same folder structure relative to
the manifest.php location.

`php manifester fulfill <Path to manifest.php> <Path to SCRM instance>`

If your manifest is in 
/var/www/MyModuleToExport
and your SCRM instance in
/var/www/SuiteCRM

Then your command would look like

`php manifester fulfill /var/www/MyModuleToExport /var/www/SuiteCRM`

Leaving you with a folder ready to compress and export the module.
