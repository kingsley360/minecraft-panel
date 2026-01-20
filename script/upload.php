<?php
@mkdir("uploads");

if (!isset($_FILES["backup"])) die("No file");

$target = "uploads/world.zip";

if (move_uploaded_file($_FILES["backup"]["tmp_name"], $target)) {
    echo "? Upload complete: world.zip";
} else {
    echo "? Upload failed";
}
