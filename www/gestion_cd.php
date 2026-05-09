<?php
// Connexion 
$bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret');

$message = "";
$selected_cd = $_GET['cd_id'] ?? null; //selection

// suppression, ajout, modif
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_song'])) {
        $stmt = $bdd->prepare("INSERT INTO TRACK (CD_NUMBER, TITLE, DURATION) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['cd_id'], $_POST['title'], $_POST['duration']]);
    } 
    elseif (isset($_POST['update_song'])) {
        $stmt = $bdd->prepare("UPDATE TRACK SET TITLE = ?, DURATION = ? WHERE ID = ?");
        $stmt->execute([$_POST['title'], $_POST['duration'], $_POST['song_id']]);
    }
    elseif (isset($_POST['delete_song'])) {
        // supprimer de la playlist 
        $bdd->prepare("DELETE FROM CONTAINS WHERE TRACK_ID = ?")->execute([$_POST['song_id']]);
        //supprimer de la chanson
        $bdd->prepare("DELETE FROM TRACK WHERE ID = ?")->execute([$_POST['song_id']]);
        $message = "Chanson supprimee de l'album et des playlists";
    }
}

// data
$cds = $bdd->query("SELECT CD_NUMBER, TITLE FROM CD ORDER BY TITLE")->fetchAll();
$songs = $selected_cd ? $bdd->prepare("SELECT * FROM TRACK WHERE CD_NUMBER = ?") : null;
if($songs) $songs->execute([$selected_cd]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CD & Track Manager</title>
</head>
<body>
    <a href="index.php">← Back to Home</a>
    <h1>CD & Track Manager</h1>

    <?= $message ?>

    <div style="background: #f4f4f4; padding: 15px; border-radius: 5px;">
        <h3>1. Select an Album</h3>
        <form method="get" action="gestion_cd.php">
            <select name="cd_id" onchange="this.form.submit()">
                <option value="">-- Choose a CD --</option>
                <?php foreach ($cds as $cd): ?>
                    <option value="<?= $cd['CD_NUMBER'] ?>" <?= $selected_cd == $cd['CD_NUMBER'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cd['TITLE']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <hr>

    <?php if ($selected_cd): ?>
        <h2>Manage Tracks for Selected CD</h2>
        <form method="post" action="gestion_cd.php?cd_id=<?= $selected_cd ?>">
            <input type="hidden" name="cd_id" value="<?= $selected_cd ?>">
            <p><strong>Add a new track:</strong><br>
            <input type="text" name="title" placeholder="Song Title" required>
            <input type="text" name="duration" placeholder="Duration (e.g. 02:56)" size="15">
            <input type="submit" name="add_song" value="Add Track">
            </p>
        </form>

        <hr>

        <h3>Existing Tracks</h3>
        <?php if (empty($songs)): ?>
            <p>No tracks found for this CD.</p>
        <?php else: ?>
            <table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr style="background: #eee;">
                        <th>Track Title</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($songs as $song): ?>
                        <tr>
                            <form method="post" action="gestion_cd.php?cd_id=<?= $selected_cd ?>">
                                <input type="hidden" name="song_id" value="<?= $song['ID'] ?>">
                                <input type="hidden" name="cd_id" value="<?= $selected_cd ?>">
                                <td>
                                    <input type="text" name="title" value="<?= htmlspecialchars($song['TITLE']) ?>" style="width: 90%;">
                                </td>
                                <td>
                                    <input type="text" name="duration" value="<?= htmlspecialchars($song['DURATION']) ?>" size="8">
                                </td>
                                <td>
                                    <input type="submit" name="update_song" value="Update">
                                    <input type="submit" name="delete_song" value="Delete" 
                                           style="color: red;" 
                                           onclick="return confirm('Are you sure you want to delete the song? This track will be removed from all playlists :(')">
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <p>Please select a CD above to manage its songs.</p>
    <?php endif; ?>

</body>
</html>
