<?php
// ui.php
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Minecraft Upload & Restore</title>
<style>
body{background:#0f172a;color:#e5e7eb;font-family:system-ui, sans-serif;display:flex;justify-content:center;align-items:center;padding:10px;min-height:100vh;}
.card{background:#020617;padding:20px;border-radius:12px;width:100%;max-width:500px;box-shadow:0 0 30px #000;box-sizing:border-box;}
h2{text-align:center;margin-top:0;font-size:1.5rem;}
#dropZone{border:2px dashed #4ade80;padding:30px;text-align:center;border-radius:8px;cursor:pointer;margin-bottom:10px;transition:border-color 0.3s;}
#dropZone:hover{border-color:#22c55e;}
#selectedFile{margin-top:8px;text-align:center;color:#22c55e;font-weight:bold;font-size:1rem;word-break:break-word;}
.progress{width:100%;height:22px;background:#111827;border-radius:6px;overflow:hidden;margin-top:10px;}
.bar{height:100%;width:0%;background:linear-gradient(90deg,#22c55e,#4ade80);text-align:center;font-size:12px;line-height:22px;color:black;transition:width 0.3s;}
.log{margin-top:10px;background:black;color:#4ade80;height:160px;overflow:auto;padding:10px;font-family:monospace;font-size:0.85rem;border-radius:6px;}
#uploadBtn{display:none;width:100%;padding:12px;border:none;border-radius:8px;background:#22c55e;color:black;font-size:1rem;cursor:pointer;margin-top:10px;}
@media (max-width:600px){
  h2{font-size:1.3rem;}
  #dropZone{padding:25px;}
  .log{height:120px;}
  #uploadBtn{display:block;}
}
</style>
</head>
<body>
<div class="card">
<h2>Minecraft World Upload & Restore</h2>
<div id="dropZone">Drag & Drop Backup ZIP Here<br>or Tap to Select</div>
<input type="file" id="file" style="display:none;">
<div id="selectedFile"></div>
<button id="uploadBtn" onclick="upload()">?? Upload & Restore</button>
<div class="progress"><div class="bar" id="bar">0%</div></div>
<div class="log" id="log"></div>
</div>

<script>
let fileInput=document.getElementById("file");
let dropZone=document.getElementById("dropZone");
let selectedFile=document.getElementById("selectedFile");
let bar=document.getElementById("bar");
let logBox=document.getElementById("log");
let uploadBtn=document.getElementById("uploadBtn");

dropZone.addEventListener("click",()=>fileInput.click());
dropZone.addEventListener("dragover",e=>{e.preventDefault();dropZone.style.borderColor="#22c55e";});
dropZone.addEventListener("dragleave",e=>{dropZone.style.borderColor="#4ade80";});
dropZone.addEventListener("drop",e=>{
  e.preventDefault();
  fileInput.files=e.dataTransfer.files;
  dropZone.style.borderColor="#4ade80";
  showSelectedFile();
  if(window.innerWidth>600) upload(); // auto-upload on desktop
});

fileInput.addEventListener("change",()=>{
  showSelectedFile();
  if(window.innerWidth>600) upload();
});

function showSelectedFile(){
  let f=fileInput.files[0];
  if(f) selectedFile.textContent = "Selected File: " + f.name;
}

function log(t){logBox.textContent+=t+"\n";logBox.scrollTop=logBox.scrollHeight;}

async function upload(){
  let f=fileInput.files[0];
  if(!f) return;
  const chunkSize=5*1024*1024; // 5MB
  const totalChunks=Math.ceil(f.size/chunkSize);

  // Upload chunks
  for(let i=0;i<totalChunks;i++){
    const chunk=f.slice(i*chunkSize,(i+1)*chunkSize);
    const fd=new FormData();
    fd.append("chunk",chunk);
    fd.append("name",f.name);
    fd.append("index",i);
    fd.append("total",totalChunks);

    await fetch("upload.php",{method:"POST",body:fd})
      .then(r=>r.text())
      .then(t=>log(t))
      .catch(e=>log("? "+e));

    let percent=Math.floor(((i+1)/totalChunks)*50); // 50% for upload
    bar.style.width=percent+"%";
    bar.textContent=percent+"%";
  }

  // Merge chunks
  await fetch("merge.php?name="+encodeURIComponent(f.name))
    .then(r=>r.text())
    .then(t=>log(t));

  log("Upload complete, starting restore...");

  // Call restore.php
  await fetch("restore.php")
    .then(r=>r.text())
    .then(t=>log(t));

  // Show 100% at the end
  bar.style.width="100%";
  bar.textContent="100%";
  log("Restore finished!");
}
</script>
</body>
</html>
