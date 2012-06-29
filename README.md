BlogAssignment
==============

## Description
This is a assignment submission type for Moodle[1]. It lets students hand in submission as entries in the blogsystem. Note that this is work in progress, and not intended for production environments.

## Requirements
This submission type requires Moodle >= 2.3. To work it also requires triggered events when blog entries are added, edited or removed. This is not a part of Moodle yet, and will need to be added manually for the time beeing.

## Installation
	First; clone the submission type into the correct directory.
	
	$ cd \[moodle installation\]/mod/assign/submission/
	$ git clone git://github.com/eriklundberg/BlogAssignment.git
	
	Then; go to Home > Site administration > Notifications to install the submission typ into Moodle.

## Licence

BlogAssignment is licenced under GNU GPL v3. 

Copyright: Department of Computer and System Sciences, Stockholm University.

[1]: http://moodle.org "moodle.org"
