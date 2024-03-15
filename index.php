<?php
$servername = 'localhost';
$username = 'root';
$password = '';
$dbName = "ri7_project_sql";
$prompt = [];
try {
    $db = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->beginTransaction();
    if (isset($_POST["delete"])) {
        $deleteSQL = $db->prepare("DELETE FROM user WHERE id=:id");
        $deleteSQL->execute([":id" => $_POST["delete"]]);
        $prompt = ["success" => "User successfully deleted"];
    } elseif (isset($_POST["confirm"])) {
        $prompt = updateUser($db, $_POST);
    } elseif (isset($_POST["create"])) {
        $prompt = createUser($db);
    }

    $requeteSQL = $db->prepare("SELECT * FROM user");
    $requeteSQL->execute();
    $tableauRequete = $requeteSQL->fetchAll();
    $db->commit();
    $db = null;
} catch (PDOException $e) {
    if ($db !== null) {
        $db->rollback();
    }
    $prompts = ["error" => $e->getMessage()];
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Projet PHP SQL</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="./assets/styles/styles.css">
</head>

<body>
    <h1>Liste des participants</h1>
    <form method="post" id="ajouterUtilisateur">
        <input type="text" name="nom" placeholder="Votre nom" />
        <input type="text" name="prenom" placeholder="Votre prénom" />
        <input type="text" name="mail" placeholder="Votre mail" />
        <input type="text" name="codePostal" placeholder="Votre code postal" />
        <button type="submit" id="confirmer" name="create">Ajouter</button>
    </form>
    <main>
        <table>
            <tr id="enteteTableau">
                <th>Nom</th>
                <th>Prénom</th>
                <th>Mail</th>
                <th>Code Postal</th>
                <th>Actions</th>
            </tr>
            <?php
            $update = isset($_POST["update"]) ? $_POST["update"] : -1;
            $confirm = isset($_POST["confirm"]) && isset($prompt["error"]) ? $_POST["confirm"] : -1;
            if (isset($tableauRequete)) {
                foreach ($tableauRequete as $entry) { ?>
                    <tr class="<?= $confirm == $entry["id"] ? 'ligneErreur' : '' ?>">
                        <form method="post">
                            <?php
                            if ($update != $entry["id"] && $confirm != $entry["id"]) : ?>
                                <td><?php echo $entry["nom"]; ?></td>
                                <td><?php echo $entry["prenom"]; ?></td>
                                <td><?php echo $entry["mail"]; ?></td>
                                <td><?php echo $entry["codePostal"]; ?></td>
                                <td>
                                    <input type="hidden" name="user_id" value="<?= $entry["id"]; ?>">
                                    <button type="submit" name="update">Mettre à jour</button>
                            <?php else : ?>
                                <td><input type="text" name="nom" value="<?= $entry["nom"]; ?>" /></td>
                                <td><input type="text" name="prenom" value="<?= $entry["prenom"]; ?>" /></td>
                                <td><input type="text" name="mail" value="<?= $entry["mail"]; ?>" /></td>
                                <td><input type="text" name="codePostal" value="<?= $entry["codePostal"]; ?>" /></td>
                                <td>
                                    <button type="submit" id="confirmer" name="confirm" value="<?= $entry["id"] ?>">Confirmer</button>
                            <?php endif; ?>
                                <button type="submit" name="delete" value="<?= $entry["id"] ?>">X</button>
                                </td>
                        </form>
                    </tr>
            <?php }
            } else {
                echo "<tr><td colspan='5'>Aucun utilisateur trouvé</td></tr>";
            } ?>
        </table>
    </main>
    <?php
    if (count($prompt) > 0) {
        echo "<div class='wrapper'>";
        if (isset($prompt["success"])) echo "<p class='success toast'>" . $prompt["success"] . "</p>";
        else {
            foreach ($prompt["error"] as $error) {
                echo "<p class='error toast'>$error</p>";
            }
        }
        echo "</div>";
    }
    ?>
</body>

</html>

<?php
function updateUser($db, $postData)
{
    $fields = verifyFields($postData);
    if (isset($fields["error"])) return $fields;

    $updateSQL = $db->prepare("UPDATE user SET nom=:nom, prenom=:prenom, mail=:mail, codePostal=:codePostal WHERE id=:id");
    $updateSQL->execute([
        ":id" => $fields["user_id"],
        ":nom" => $fields["nom"],
        ":prenom" => $fields["prenom"],
        ":mail" => $fields["mail"],
        ":codePostal" => $fields["codePostal"]
    ]);

    return ["success" => "Utilisateur modifié"];
}

function createUser($db)
{
    $fields = verifyFields($_POST);
    if (isset($fields["error"])) return $fields;
    $createSQL = $db->prepare("INSERT INTO user(nom, prenom, mail, codePostal) VALUES(:nom, :prenom, :mail, :codePostal)");
    $createSQL->execute([
        ":nom" => $fields["nom"],
        ":prenom" => $fields["prenom"],
        ":mail" => $fields["mail"],
        ":codePostal" => $fields["codePostal"]
    ]);
    return ["success" => "Utilisateur bien créé"];
}

function verifyFields($fields)
{
    $goodFields = [];
    $prompts = ["error" => []];
    foreach ($fields as $field => $value) {
        switch ($field) {
            case "nom":
                $regex = "/^[a-z\-]+$/i";
                if (!preg_match($regex, $value)) array_push($prompts["error"], "Mauvais nom");
                break;
            case "prenom":
                $regex = "/^[a-z\-]+$/i";
                if (!preg_match($regex, $value)) array_push($prompts["error"], "Mauvais prénom");
                break;
            case "mail":
                $regex = "/^[A-zÀ-ÿ0-9]*@[a-z]*\.[a-z]{2,5}$/";
                if (!preg_match($regex, $value)) array_push($prompts["error"], "Veuillez rentrer un email valide");
                break;
            case "codePostal":
                $regex = "/^[0-9]{5}$/";
                if (!preg_match($regex, $value)) array_push($prompts["error"], "Mauvais code postal, veuillez rentrer 5 chiffres");
                break;
        }
        $goodFields[$field] = htmlspecialchars($value);
    }
    return count($prompts["error"]) > 0 ? $prompts : $goodFields;
}
?>