<?php
require_once("../pdo_connect.php");

// Fetch all decks
$sql = "SELECT * FROM Deck";
$stmt = $dbc->query($sql);
$decks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Decks</title>
    <style>
        table {width: 80%; margin: 20px auto; border-collapse: collapse;}
        th, td {border: 1px solid #ddd; padding: 8px; text-align: center;}
        th {background-color: #f4f4f4;}
        h1 {text-align: center;}
    </style>
</head>
<body>
    <h1>All Decks</h1>

    <?php if (empty($decks)): ?>
        <p style="text-align: center;">No decks found in the database.</p>
    <?php else: ?>
        <table>
            <tr>
                <?php foreach (array_keys($decks[0]) as $column): ?>
                    <th><?php echo htmlspecialchars($column); ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($decks as $deck): ?>
                <tr>
                    <?php foreach ($deck as $value): ?>
                        <td><?php echo htmlspecialchars($value); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
