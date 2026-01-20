<?php
$name=$_GET['name'] ?? '';
if(!$name) die("No file specified");

$final="uploads/".$name;
@unlink($final);

$totalChunks=0;
while(file_exists("uploads/chunks/{$name}.part{$totalChunks}")) $totalChunks++;

if($totalChunks==0) die("No chunks found");

$fp=fopen($final,"ab");
for($i=0;$i<$totalChunks;$i++){
    $chunkFile="uploads/chunks/{$name}.part{$i}";
    $data=file_get_contents($chunkFile);
    fwrite($fp,$data);
    unlink($chunkFile);
}
fclose($fp);

echo "? File merged: $final";
