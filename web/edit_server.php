<?php
$serverProperties = '/home/minecraft/Server/server.properties';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newContent = $_POST['properties'] ?? '';
    if (file_put_contents($serverProperties, $newContent) !== false) {
        $message = "? server.properties saved successfully!";
    } else {
        $message = "? Failed to save server.properties. Check permissions.";
    }
}

// Load current content
$propertiesContent = '';
if (file_exists($serverProperties)) {
    $propertiesContent = file_get_contents($serverProperties);
} else {
    $propertiesContent = "# server.properties not found\n";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit server.properties</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #1e1e1e;
    color: #cfcfcf;
    margin: 0;
    padding: 20px;
}
.container {
    max-width: 900px;
    margin: 0 auto;
}
h2 {
    text-align: center;
    margin-bottom: 20px;
}
textarea {
    width: 100%;
    height: 60vh;
    background: #2e2e2e;
    color: #cfcfcf;
    border: none;
    padding: 15px;
    font-family: monospace;
    font-size: 14px;
    resize: vertical;
    box-sizing: border-box;
}
button {
    padding: 12px 25px;
    margin-top: 15px;
    background: #00aa00;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 16px;
    border-radius: 8px;
    width: 100%;
}
button:hover {
    background: #00cc00;
}
.message {
    margin: 10px 0;
    font-weight: bold;
    text-align: center;
}
@media (max-width: 600px) {
    textarea {
        height: 50vh;
        font-size: 13px;
        padding: 10px;
    }
    button {
        font-size: 14px;
        padding: 10px 20px;
    }
}
</style>
</head>
<body>
<div class="container">
    <h2>Edit server.properties</h2>
    <?php if(!empty($message)) echo "<div class='message'>$message</div>"; ?>
    <form method="post">
        <textarea name="properties"><?php echo htmlspecialchars($propertiesContent); ?></textarea>
        <br>
        <button type="submit">Save server.properties</button>
    </form>
</div>
</body>
</html>
