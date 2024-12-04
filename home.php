<?php
if (!isset($_COOKIE["user"])) {
    header("Location: login.php"); // Redirect to login if not logged in
}
// Include the file that retrieves cards from the database
require_once("../pdo_connect.php");

try {
    $nameFilter = $_GET['name'] ?? '';
    $colorsFilter = $_GET['colors'] ?? [];
    $setFilter = $_GET['set'] ?? '';
    $sql = "SELECT * FROM Card WHERE 1=1";
    $params = [];

    if (!empty($nameFilter)) {
        $sql .= " AND CardName LIKE :name";
        $params[':name'] = "%" . $nameFilter . "%";
    }

    if (!empty($setFilter)) {
        $sql .= " AND CardSet LIKE :set";
        $params[':set'] = "%" . $setFilter . "%";
    }

    $stmt = $dbc->prepare($sql);
    $stmt->execute($params);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching cards: " . $e->getMessage());
}

if (!empty($colorsFilter)) {
    $cards = array_filter($cards, function ($card) use ($colorsFilter) {
        $cardColors = explode(',', strtolower($card['colors']));
        return !empty(array_intersect($colorsFilter, $cardColors));
    });
}

// Fetch available decks for selection
try {
    $deckQuery = $dbc->query("SELECT * FROM Deck");
    $decks = $deckQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching decks: " . $e->getMessage());
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
    <h1>Magic: The Gathering Collection</h1>

    <!-- Filter Form -->
    <form method="GET">
        <label for="name">Card Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($nameFilter); ?>">

        <fieldset>
            <legend>Colors</legend>
            <?php
            $colorOptions = ['Red', 'Green', 'Blue', 'White', 'Black', 'Colorless'];
            foreach ($colorOptions as $color) {
                $isChecked = in_array(strtolower($color), array_map('strtolower', $colorsFilter)) ? 'checked' : '';
                echo "<label><input type='checkbox' name='colors[]' value='$color' $isChecked> $color</label>";
            }
            ?>
        </fieldset>

        <label for="set">Card Set:</label>
        <input type="text" id="set" name="set" value="<?php echo htmlspecialchars($setFilter); ?>">

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
                    <input type="hidden" name="card_id" value="<?php echo htmlspecialchars($card['CardInd']); ?>">
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
</body>
</html>
