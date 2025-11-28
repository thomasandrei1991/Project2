<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Document</title>
</head>
<body>
    <div class="form-container">
        <form action="index.php" method="post">
            <div class="text-container">
                <label for="firstname">Firstname : </label>
                <input type="text" id="firstname" name="firstname">
                <label for="lastname">Lastname : </label>
                <input type="text" id="lastname" name="lastname">
                <input type="submit" value="Submit">
            </div>
        </form>
    </div>
</body>
</html>
<?php
    echo $_POST["firstname"];
    echo $_POST["lastname"];
?>
