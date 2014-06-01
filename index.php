<?php

require 'class.php';

$notes = new notes();

if (isset($_POST['action'])) {
	$action = $_POST['action'];
	switch ($action) {
		case 'newNote':
			$notes->newNote($_POST);
			break;
		case 'getNote':
			$notes->getNote($_POST);
			break;
		case 'delete':
			echo "DELETE";
			$notes->deleteNote($_POST);
			break;
		case 'search':
			$notes->search($_POST);
			break;
		default:
			break;
	}
	die();
}

$notes->header();

if (!$notes->action) {
	$notes->newInput();
}

$notes->listNotes();

$notes->footer();

?>

