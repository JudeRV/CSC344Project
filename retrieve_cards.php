<?php
	ini_set('display_errors', 1);
//Name: 
/*This code assumes user input is valid and correct only for demo purposes - it does NOT validate form data.*/
	if(isset($_GET['submit'])) { //Form was submitted
		$title = $_GET["title"];
		$title = '%'.$title.'%'; // . is the concatenation operator for PHP strings
		try{
			require_once('../pdo_connect.php'); //adjust the relative path as necessary to find your connect file
			$sql = 'SELECT BookTitle, BookYear FROM FACT_BOOK WHERE BookTitle LIKE ? ';
			$stmt = $dbc->prepare($sql);
			$stmt->bindParam(1, $title);
			$stmt->execute();	
		} catch (PDOException $e){
			echo $e->getMessage();
		}	
		$affected = $stmt->RowCount();
		if ($affected == 0){
			echo "We could not find a book matching that description. Please try again.";
			exit;
		}	
		else {
			$result = $stmt->fetchAll();
		}
	} //end isset
	else {
		echo "<h2>You have reached this page in error</h2>";
		exit;
	}
	
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>FACT Library</title>
	<meta charset ="utf-8"> 
</head>
<body>
	<h2> Books Found: </h2>
	<table>
		<tr>
			<th>Book Title</th>
			<th>Book Year</th>
		</tr>
	<?php foreach($result as $row) {
		echo "<tr>";
		echo "<td>".$row['BookTitle']."</td>";
		echo "<td>".$row['BookYear']."</td>";
		echo "</tr>";
	}?> 
	</table>
</body>
</html>