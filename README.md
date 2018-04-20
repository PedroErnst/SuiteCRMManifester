# SuiteCRMManifester

A command line tool for creating module packages from a manifest file

# Installation

Clone this repository and run `composer update`.

# Usage

For now there is a single command available

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
