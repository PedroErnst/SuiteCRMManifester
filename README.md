# SuiteCRMManifester

A command line tool for automatically generating a manifest file and copying
the files into a folder, ready to zip.

# Installation

Clone this repository and run `composer update`.

# Usage

Make changes in your SuiteCRM instance, commit them to git.

Create an empty folder where you want to build your installable module.

Create a rump manifest with the $manifest array and its variables: author, module
name etc.

Run the `manifester update` command to update the manifest with the changed files.

Run the `manifester fulfill` command to copy all the requested files into the new 
module folder.

Zip the folder!

### update

The update command does a git diff and parses the list of files into our manifest
file.

`php manifester update <manifest.php directory> <SCRM instance directory> <commit>`

The git diff will return a list of changed files since `commit` and incorporate
this list into the manifest.php

### fulfill

The fulfill command reads the contents of a manifest file, and tries to copy all the 
required files from the specified instance to the same folder structure relative to
the manifest.php location.

`php manifester fulfill <manifest.php directory> <SCRM instance directory>`

If your manifest is in 
/var/www/MyModuleToExport
and your SCRM instance in
/var/www/SuiteCRM

Then your command would look like

`php manifester fulfill /var/www/MyModuleToExport /var/www/SuiteCRM`

Leaving you with a folder ready to compress and export the module.
