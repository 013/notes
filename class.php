<?php

/* SQL

CREATE TABLE notes (
        id              INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        note            TEXT,
        created         TIMESTAMP DEFAULT NOW(),
        owner           VARCHAR(64), 
        last_updated    TIMESTAMP,
	deleted		INT
);

*/

class notes {
	public $db;
	public $action;
	public $user;
	
	function __construct() {
		// Initialise DB connection
		try {
			$this->db = new PDO('mysql:host=localhost;dbname=notes', 'root', 'linnit');
		} catch (PDOException $exception) {
			die("Error: $exception");
		}

		// An action could be viewing a note, creating a new note, editing an existing note..
		if (!isset($_GET['action'])) {
			$this->action = false;
		} else {
			$this->action = $_GET['action'];
		}

		$this->user = "Ryan";
	}

	function header() {
		// All the HTML etc that will be on every viewable page
		$header = <<<HTML
<!DOCTYPE html>
<html lang="en">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- include libries(jQuery, bootstrap, fontawesome) -->
<script src="//code.jquery.com/jquery-1.9.1.min.js"></script> 
<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.1/css/bootstrap.min.css" rel="stylesheet"> 
<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.1/js/bootstrap.min.js"></script> 
<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">

<!-- include summernote css/js-->
<link href="summernote.css" rel="stylesheet" />
<script src="summernote.min.js"></script>
<script>
$(document).ready(function() {
	$("#input").summernote({
		height: 150,                 // set editor height
		minHeight: null,             // set minimum height of editor
		maxHeight: null,             // set maximum height of editor
		focus: true,                 // set focus to editable area after initializing summernote
	});

	$(".btn-add").click(function() {
		console.log( $("#input").code() );
		//$.post( "index.php", { new_note: true, note: $("#input").code() }).done(function( data ) {
		$.post( "index.php", { action: 'newNote', note: $("#input").code() }).done(function( data ) {
			//alert( "Data Loaded: " + data );
		});
	});

	$(".btn-new").click(function() {
		$("#hidden_input").toggle();
		$(".btn-new").toggle();
	});

	$(".btn-hide").click(function() {
		$("#hidden_input").toggle();
		$(".btn-new").toggle();
	});

	$(".btn-search").click(function() {
		$.post( "index.php", { action: 'search', term: $("#searchterm").val() }).done(function( data ) {
			console.log(data);
		});
	});

	$(".viewNote").click(function() {
		//alert( $(this).attr('id') );
		$.post( "index.php", { action: 'getNote', note: $(this).attr('id') }).done(function( data ) {
			$('#viewport').html(data);
			$('html, body').animate({ scrollTop: 0 }, 0);
		});
		//alert( window.location.hash.substr(1) );
	});

	// .deletes are dynamically added
	$(document).on('click','.delete',function(){
		$.post( "index.php", { action: 'delete', note: $(this).attr('id') }).done(function( data ) {
			alert( data );
			//$('#viewport').html(data);
		});
		//alert("Delete: " + $(this).attr('id'));
	});
	
	// When first loading page, check what the hash is saying
	var hash = window.location.hash.substr(1);
	//if (hash.length > 0) {
	//	$.post( "index.php", { action: 'getNote', note: hash) }).done(function( data ) {
	//		$('#viewport').html(data);
	//	}
	//	//alert(hash);
	//}
});
</script>
<style>
.header {
	width: 100%;
	display: inline-block;
}
.btn-wide {
	width: 100%;
}
.btn-widish {
	width: 90%;
}
.btn-hide {
	width: 9%;
}
</style>
<div class="header">
<!--<form class="navbar-form navbar-left" role="search">-->
<div class="form-group">
<input type="text" class="form-control" placeholder="Search" id="searchterm">
</div>
<button class="btn btn-default btn-search">Submit</button>
<!--</form>-->
</div>

<div id="viewport"></div>

HTML;
		echo $header;
	}

	function footer() {
		// HTML for bottom of viewable pages
		echo "</html>";
	}

	function newInput() {
		// HTML for input box
		echo '
		<div id="hidden_input" style="display: none;">
		<div id="input"></div>
		<button class="btn btn-default btn-widish btn-add">Add Note</button>
		<button class="btn btn-default btn-hide">Hide</button>
		</div>
		<button class="btn btn-default btn-wide btn-new">New Note</button>
		';
	}

	function listNotes() {
		// Get notes from the database and list them
		$tablehtml = "<table class='table table-hover table-border table-striped table-condensed' style=''>
				<colgroup>
				<col span='1' style='width: 5%;'>
				<col span='1' style='width: 10%;'>
				<col span='1' style='width: 50%;'>
				<col span='1' style='width: 15%;'>
				<col span='1' style='width: 15%;'>
				</colgroup>
				<thead>
					<tr>
						<th>ID</th>
						<th>Owner</th>
						<th>Note</th>
						<th>Created</th>
						<th>Last Updated</th>
					</tr>
				</thead>
				<tbody>";

		$sql = "SELECT * FROM notes ";
		$where = "WHERE deleted = 0 ";

		$sql .= $where . "limit :alimit, :blimit ";

		$statement = $this->db->prepare($sql);
		
		$statement->bindValue(':alimit', 0, PDO::PARAM_INT);
		$statement->bindValue(':blimit', 20, PDO::PARAM_INT);

		$statement->execute();

		while($row = $statement->fetch()) {
			if (strlen($row['note']) > 30) {
				$title = substr($row['note'], 27) . "...";
			} else { 
				$title = $row['note'];
			}
			$title = strip_tags($title);

			$tablehtml .= "<tr>
				<td>{$row['id']}</td>
				<td>{$row['owner']}</td>
				<td><a id='{$row['id']}' class='viewNote' href='#{$row['id']}'>{$title}</a></td>
				<td>{$row['created']}</td>
				<td>{$row['last_updated']}</td>
			</tr>";
		}
		$tablehtml .= "</tbody></table>";
		echo $tablehtml;
	}

	function search($data) {
		$sql = "SELECT * FROM notes where note REGEXP :term";
		$statement = $this->db->prepare($sql);
		$statement->bindValue(':term', $data['term']);
		
		$statement->execute();

		$json = array();

		while($row = $statement->fetch()) {
			//$x .= $row['id'] . "\n<br>";
			$json[$row['id']] = $row['note'];
		}

		echo json_encode($json);
	}

	function getNote($data) {
		$sql = "SELECT * FROM notes WHERE id = :note";
		$statement = $this->db->prepare($sql);
		$statement->bindParam(':note', $data['note']);
		$statement->execute();
		$row = $statement->fetch();
		
		$note = <<<NOTE
		<div class="panel panel-default">
		<div class="panel-heading">
		<h3 class="panel-title">ID: {$row['id']} - Owner: {$row['owner']} - Created: {$row['created']} - Last Updated: {$row['last_updated']}
		<span id="DEL_{$row['id']}" class="glyphicon glyphicon-trash delete"></span>
		</h3>
		</div>
		<div class="panel-body">
		{$row['note']}
		</div>
		</div>
NOTE;
		echo $note;
	}

	function newNote($data) {
		// id | note | created| owner | last_updated | deleted
		$sql = "INSERT INTO notes VALUES(NULL, :note, NULL, :owner, NULL, 0);";

		$statement = $this->db->prepare($sql);

		$statement->bindParam(':note', $data['note']);
		$statement->bindParam(':owner', $this->user);
	
		$statement->execute();

		echo $data['note'];
		echo $this->user;
	}

	function deleteNote($data) {
		// Not actually going to delete it, just hide
		$sql = "UPDATE notes SET deleted = 1 WHERE id = :note;";
		
		$statement = $this->db->prepare($sql);
		
		$id = (int) explode('_', $data['note'])[1];

		$statement->bindParam(':note', $id);

		$statement->execute();

		return 0;
	}

}
