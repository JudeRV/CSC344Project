<?php
if (!isset($_COOKIE["user"])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

// Include the database connection file
require_once("../pdo_connect.php");

try {
    // Initialize filters
    $nameFilter = $_GET['name'] ?? '';
    $colorsFilter = isset($_GET['colors']) ? array_map('strtolower', $_GET['colors']) : [];
    $manaFilter = isset($_GET['mana']) ? array_map('strtolower', $_GET['mana']) : [];
    $setFilter = $_GET['set'] ?? '';

    // Base query for fetching cards with a join to filter by mana colors
    $sql = "SELECT DISTINCT C.* FROM Card C 
            LEFT JOIN CardColor CC ON C.CardSetID = CC.CardSetID AND C.CardIndex = CC.CardIndex
            WHERE Deleted = 0 AND UserID = :userid";
    $params = [];
    $params[':userid'] = $_COOKIE["user"];

    if (!empty($nameFilter)) {
        $sql .= " AND C.CardName LIKE :name";
        $params[':name'] = "%" . $nameFilter . "%";
    }

    if (!empty($setFilter)) {
        $sql .= " AND C.CardSet LIKE :set";
        $params[':set'] = "%" . $setFilter . "%";
    }
    // If mana color filters are applied, add the conditions for color filtering (requiring all selected colors)
    if (!empty($colorsFilter)) {
        // Add a subquery that counts how many of the selected colors are associated with the card
        $sql .= " AND (
                    SELECT COUNT(DISTINCT CC.CardColor) 
                    FROM CardColor CC 
                    WHERE C.CardSetID = CC.CardSetID 
                    AND C.CardIndex = CC.CardIndex 
                    AND LOWER(CC.CardColor) IN (" . implode(',', array_fill(0, count($colorsFilter), '?')) . ")
                ) = " . count($colorsFilter);
        
        foreach ($colorsFilter as $index => $color) {
            $params[] = $color; // Bind color parameters
        }
    }

    $stmt = $dbc->prepare($sql);
    $stmt->execute($params);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching cards: " . $e->getMessage());
}

// Fetch available decks
try {
    $deckQuery = $dbc->query("SELECT * FROM Deck");
    $decks = $deckQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching decks: " . $e->getMessage());
}
// Fetch the highest priced card for the user (if needed)
try {
    $sqlHighestPriceCard = "
        SELECT c.CardName, c.CardCurrentPrice
        FROM Card c
        WHERE UserID = :userid
          AND c.CardCurrentPrice = (
              SELECT MAX(h.CardCurrentPrice)
              FROM Card h
              WHERE h.UserID = c.UserID
          )
    ";
    $stmtHighestPrice = $dbc->prepare($sqlHighestPriceCard);
    $stmtHighestPrice->bindParam(':userid', $_COOKIE["user"], PDO::PARAM_INT);
    $stmtHighestPrice->execute();
    $highestPriceCard = $stmtHighestPrice->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching highest priced card: " . $e->getMessage());
}
// Fetch duplicate cards
try {
    $sqlDuplicates = "
        SELECT 
            C1.CardName,
            C1.CardSetID,
            COUNT(*) AS Quantity
        FROM 
            Card C1
        WHERE
            C1.UserID = :UserID
        GROUP BY 
            C1.CardName, C1.CardSetID, C1.UserID
        HAVING 
            COUNT(*) > 1;
    ";
    $stmtDuplicates = $dbc->prepare($sqlDuplicates);
    $stmtDuplicates->bindParam(':UserID', $_COOKIE["user"], PDO::PARAM_INT);
    $stmtDuplicates->execute();
    $duplicates = $stmtDuplicates->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching duplicate cards: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic: The Gathering Collection</title>
    <link rel="stylesheet" href="style.css">
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
    <h1>Magic: The Gathering Collection</h1>

    <!-- Filter Form -->
    <form method="GET">
        <label for="name">Card Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($nameFilter); ?>">

        <fieldset>
            <legend>Colors</legend>
            <?php
            $colorOptions = ['R', 'G', 'U', 'W', 'B', 'C'];
            foreach ($colorOptions as $color) {
                $isChecked = in_array(strtolower($color), $colorsFilter) ? 'checked' : '';
                echo "<label><input type='checkbox' name='colors[]' value='$color' $isChecked> $color</label>";
            }
            ?>
        </fieldset>

        <button type="submit">Filter</button>
    </form>

    <div class="card-container">
        <?php
        if (!empty($cards)) {
            foreach ($cards as $card) {
                echo '<div class="card">';
                echo '<h2>' . htmlspecialchars($card['CardName']) . '</h2>';
                echo '<p>Mana Value: ' . htmlspecialchars($card['CardManaValue']) . '</p>';
                echo '<p>Rarity: ' . htmlspecialchars($card['CardRarity']) . '</p>';
                echo '<p>Current Price: $' . htmlspecialchars($card['CardCurrentPrice']) . '</p>';
                ?>
                <form method="POST" action="deck.php">
                    <input type="hidden" name="card_id" value="<?php echo htmlspecialchars($card['CardIndex']); ?>">
                    <input type="hidden" name="card_set_id" value="<?php echo htmlspecialchars($card['CardSetID']); ?>">
                    <label for="deck_id">Add to Deck:</label>
                    <select name="deck_id" id="deck_id">
                        <?php
                        foreach ($decks as $deck) {
                            echo '<option value="' . htmlspecialchars($deck['DeckID']) . '">' . htmlspecialchars($deck['DeckName']) . '</option>';
                        }
                        ?>
                    </select>
                    <button type="submit">Add Card</button>
                </form>
                <?php
                echo '</div>';
            }
        } else {
            echo '<p>No cards match the filters.</p>';
        }
        ?>
    </div>

    <!-- Display highest cost card -->
    <?php if ($highestPriceCard): ?>
        <h2>Highest Priced Card</h2>
        <div class="highest-price-card">
            <p><strong>Card Name:</strong> <?= htmlspecialchars($highestPriceCard['CardName']); ?></p>
            <p><strong>Current Price:</strong> $<?= htmlspecialchars($highestPriceCard['CardCurrentPrice']); ?></p>
        </div>
    <?php else: ?>
        <p>No cards found for UserID = 2.</p>
    <?php endif; ?>
    <!-- Display duplicate cards -->
    <h2>Duplicate Cards</h2>
    <?php if (!empty($duplicates)): ?>
        <ul>
            <?php foreach ($duplicates as $duplicate): ?>
                <li>
                    <?php echo htmlspecialchars($duplicate['CardName']); ?>
                    (<?php echo strtoupper(htmlspecialchars($duplicate['CardSetID'])); ?>)
                    x<?php echo htmlspecialchars($duplicate['Quantity']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No duplicate cards found.</p>
    <?php endif; ?>

    <footer>
        <div class="grid"></div>
    </footer>
</body>
</html>
