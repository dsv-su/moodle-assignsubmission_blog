<?php

$handlers = array(
	'blog_entry_added' => array(
		'handlerfile' 		=> '/mod/assign/submission/blog/lib.php',
		'handlerfunction' 	=> 'entry_added_handler',
		'schedule'			=> 'instant'
	),
	
	'blog_entry_edited' => array(
		'handlerfile' 		=> '/mod/assign/submission/blog/lib.php',
		'handlerfunction' 	=> 'entry_edited_handler',
		'schedule'			=> 'instant'
	),
	
	'blog_entry_deleted' => array(
		'handlerfile' 		=> '/mod/assign/submission/blog/lib.php',
		'handlerfunction' 	=> 'entry_deleted_handler',
		'schedule'			=> 'instant'
	)
);
