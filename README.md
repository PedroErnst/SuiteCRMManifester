# SuiteCRM Manifester

`
php manifester build /var/www/myNewPackage /var/www/myModifiedInstance
`

A command line tool for automatically generating a manifest file and copying
the files into a folder, ready to zip.

# Installation

Clone this repository and run `composer update`.

# Usage

1. Make changes in your SuiteCRM instance, commit them to git.
2. Run the `manifester build` command.
3. Zip the folder!

### build

The build command runs the entire process of `new`, `update` and `fulfill`. This will
overwrite any existing manifest, so if you want to update a package you should use
the suitable commands below.

`php manifester build <target directory> <SCRM instance directory> <commit (optional)>`

The commit argument is optional and defaults to `master`

### new

The new command takes a folder path, creates it if it doesn't exist, and copies over
a boilerplate manifest and license.

`php manifester new <directory>`

### update

The update command does a git diff and parses the list of files into our manifest
file.

`php manifester update <manifest.php directory> <SCRM instance directory> <commit>`

The git diff will return a list of changed files since `commit` and incorporate
this list into the manifest.php

The commit argument is optional and defaults to `master`

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
