<?php
@mkdir("uploads/chunks",0777,true);

if(!isset($_FILES["chunk"])) die("No chunk received");

$name=$_POST['name'];
$index=intval($_POST['index']);
$target="uploads/chunks/{$name}.part{$index}";

if(move_uploaded_file($_FILES['chunk']['tmp_name'],$target)){
    echo "Chunk $index uploaded";
}else{
    echo "Failed chunk $index";
}
