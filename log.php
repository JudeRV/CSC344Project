<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_COOKIE["user"])) {
    header("Location: login.php"); // Redirect to login if not logged in
}
require_once("../pdo_connect.php");

/*
function rollBackEntry($rollbackID) {
    $sql = "
        SELECT UserID, CardSetID, CardIndex, EntryType
        FROM LogEntry
        WHERE EntryID = :entryid
    ";

    $stmt = $dbc->prepare($sql);
    $stmt->bindParam(":entryid", $rollbackID, PDO::PARAM_INT);
    if ($stmt->execute() && $stmt->rowCount() = 1) {
        $entry = $stmt->fetch();
        switch ($entry["EntryType"]) {
            case "addition":
                $sql = "
                    UPDATE Card
                    SET Deleted = 1
                    WHERE UserID = :userid AND CardSetID = :csid AND CardIndex = :cind
                ";
                break;
        }

    }
    else {
        echo("Error attempting to roll back entry.");
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rollbackID = $_POST["rollbackid"];
    rollBackEntry($rollbackID);
}
*/
$sql = "
    SELECT 
        l.EntryID,
        l.CardSetID, 
        l.CardIndex, 
        l.EntryDate, 
        l.EntryTime, 
        l.EntryType, 
        l.RollsBackID, 
        l.CardImageURI, 
        c.CardName
    FROM LogEntry l
    LEFT JOIN Card c
    ON l.CardSetID = c.CardSetID AND l.CardIndex = c.CardIndex
    WHERE l.UserID = :userid
    ORDER BY l.EntryDate ASC, l.EntryTime ASC
";
$stmt = $dbc->prepare($sql);

$stmt->bindParam(":userid", $_COOKIE["user"]);
if ($stmt->execute() && $stmt->rowCount() > 0) {
    $logEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    echo "<p>Failed to look up collection log.</p>";
}

$sql = "
    SELECT 
        log1.EntryID AS OriginalEntryID,
        log2.EntryID AS RollbackEntryID
    FROM LogEntry AS log1
    JOIN LogEntry AS log2
        ON log1.EntryID = log2.RollsBackID;
";
$stmt = $dbc->prepare($sql);
if ($stmt->execute()) {
    if ($stmt->rowCount() > 0) {
        $rollBackEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    else {
        $rollBackEntries = [];
    }
} else {
    echo "<p>Failed to get rollback pairs.</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Log Entries</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        img {
            max-width: 100px;
        }
    </style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="add_card.php">Add Card</a></li>
            <li><a href="deck.php">Deck</a></li>
            <li><a href="log.php">Changelog</a></li>
            <li><a href="login.php?logout=true">Logout</a></li>
        </ul>
    </nav>
    <h1>User Log Entries</h1>
    <table>
        <thead>
            <tr>
                <th>Card Image</th>
                <th>Card Name</th>
                <th>Entry Date/Time</th>
                <th>Entry Type</th>
                <th>Rolls Back</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($logEntries) > 0): ?>
                <?php foreach ($logEntries as $entry): ?>
                    <tr id='<?= $entry['EntryID'] ?>'>
                        <td>
                            <?php if (!empty($entry['CardImageURI'])): ?>
                                <img src="<?= htmlspecialchars($entry['CardImageURI']) ?>" alt="Card Image for <?= htmlspecialchars($entry['CardName']) ?>">
                            <?php else: ?>
                                <img src="/images/default.webp" alt="Default Card Image">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($entry['CardName'] ?? 'Unknown') ?></td>
                        <td>
                            <p>Date: <?= htmlspecialchars($entry['EntryDate']) ?></p>
                            <p>Time: <?= htmlspecialchars($entry['EntryTime']) ?></p>
                        </td>
                        <td><?= htmlspecialchars(ucfirst($entry['EntryType'])) ?></td>
                        <td>
                            <?php if (!is_null($entry['RollsBackID'])): ?>
                                <a href="#<?= $entry['RollsBackID'] ?>">
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="rollbackid" value="<?= $entry["EntryID"] ?>">
                                    <button type="submit">Roll Back</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No log entries found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <h2>Rollback Entries:</h2>
    <?php if (count($rollBackEntries) > 0): ?>
        <ol>
            <?php foreach ($rollBackEntries as $entry): ?>
                <li>Entry #<?= $entry["RollbackEntryID"] ?> rolls back entry #<?= $entry["OriginalEntryID"] ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</body>
</html>