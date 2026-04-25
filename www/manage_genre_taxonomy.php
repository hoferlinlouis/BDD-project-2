<!DOCTYPE html>
<?php 
$bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret');

$message = '';
$parsed_pairs = [];

if (isset($_POST['import_data'])) {
    if (!empty($_POST['taxonomy_pairs'])) {
        try {
            $bdd->beginTransaction();

            $stmt_genre = $bdd->prepare("INSERT IGNORE INTO GENRE (NAME) VALUES (?)");
            $stmt_spec = $bdd->prepare("INSERT INTO SPECIALIZES (SUBGENRE, GENRE) VALUES (?, ?)");

            foreach ($_POST['taxonomy_pairs'] as $pair) {
                list($child, $parent) = explode('|', $pair);
                
                $stmt_genre->execute([$parent]);
                $stmt_genre->execute([$child]);
                
                $stmt_spec->execute([$child, $parent]);
            }

            $bdd->commit();
            $message = "<h3 style='color:green;'>Taxonomy successfully updated!</h3>";

        } catch (PDOException $e) {
            $bdd->rollBack();
            $message = "<h3 style='color:red;'>Transaction Failed! Database rolled back. Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
        }
    } else {
        $message = "<h3 style='color:orange;'>No genres were selected for import.</h3>";
    }
}

if (isset($_POST['preview_data'])) {
    $raw_text = $_POST['taxonomy_text'];
    $lines = explode("\n", $raw_text);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) 
            continue; 
        
        $parts = explode(",", $line);
        
        if (count($parts) >= 2) {
            $child = trim($parts[0]);
            $parent = trim($parts[1]);
            
            if (strcasecmp($child, $parent) !== 0) {
                $parsed_pairs[] = ['child' => $child, 'parent' => $parent];
            }
        }
    }
}
?>
<html lang="en">
<head><meta charset="UTF-8"><title>Genre Taxonomy Manager</title></head>
<body>
    <a href="index.php">← Back to Home</a>
    <h1>Genre Taxonomy Manager</h1>
    
    <?= $message ?>

    <form method="post" action="manage_genre_taxonomy.php">
        <p>Enter your taxonomy data here (Format: <code>Child Genre, Parent Genre</code> per line) :</p>
        <textarea name="taxonomy_text" rows="8" cols="50" placeholder="POP ROCK, POP&#10;POP ROCK, ROCK&#10;GLAM ROCK, ROCK" required></textarea>
        <br><br>
        <input type="submit" name="preview_data" value="Parse & Preview">
    </form>

    <hr>

    <?php if (!empty($parsed_pairs)): ?>
        <h2>Preview & Select Lines to Import</h2>
        <form method="post" action="manage_genre_taxonomy.php">
            <ul style="list-style-type: none;">
                <?php foreach ($parsed_pairs as $index => $pair): 
                    $value = htmlspecialchars($pair['child'] . '|' . $pair['parent']);
                    $display = htmlspecialchars($pair['child'] . " ➔ " . $pair['parent']);
                ?>
                    <li>
                        <label>
                            <input type="checkbox" name="taxonomy_pairs[]" value="<?= $value ?>" checked>
                            <?= $display ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <br>
            <input type="submit" name="import_data" value="Confirm & Execute Transaction">
        </form>
    <?php elseif (isset($_POST['preview_data'])): ?>
        <p style="color:red;">No valid pairs found. Please check your formatting.</p>
    <?php endif; ?>

</body>
</html>