<?php
http_response_code(403);

function getUserIP() {

    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }

    return $_SERVER['REMOTE_ADDR'];
}

$user_ip = getUserIP();

$json = file_get_contents("http://ip-api.com/json/$user_ip");
$data = json_decode($json, true);

?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>403 Forbidden</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>

@import url("https://fonts.googleapis.com/css?family=Share+Tech+Mono|Montserrat:700");

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background-image: linear-gradient(120deg, #4f0088 0%, #000000 100%);
    min-height:100vh;
    color:white;
    overflow-x:hidden;
    font-family:"Share Tech Mono", monospace;
}

h1{
    font-size:35vw;
    position:fixed;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    color:rgba(255,255,255,0.05);
    z-index:0;
    font-family:"Montserrat", monospace;
}

.container{
    width:90%;
    max-width:900px;
    margin:40px auto;
    padding:30px;
    background:rgba(0,0,0,0.25);
    border:1px solid rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius:15px;
    position:relative;
    z-index:2;
}

p{
    margin-bottom:15px;
    line-height:1.5;
    font-size:15px;
}

span{
    color:#f0c674;
}

i{
    color:#8abeb7;
}

.red-ip{
    color:#ff4d4d;
}

b{
    color:#81a2be;
}

a{
    color:#8abeb7;
    text-decoration:none;
}

#map{
    height:300px;
    margin-top:25px;
    border-radius:10px;
    overflow:hidden;
    border:2px solid rgba(255,255,255,0.1);
}

</style>
</head>

<body>

<h1>403</h1>

<div class="container" id="typing-box">

<p>> <span>ERROR CODE</span>: "<i>HTTP 403 Forbidden</i>"</p>

<p>> <span>YOUR IP ADDRESS</span>: 
"<i class="red-ip"><?= htmlspecialchars($user_ip) ?></i>"</p>

<p>> <span>ISP</span>: "<i><?= htmlspecialchars($data['isp']) ?></i>"</p>

<p>> <span>COUNTRY</span>: "<i><?= htmlspecialchars($data['country']) ?></i>"</p>

<p>> <span>REGION</span>: "<i><?= htmlspecialchars($data['regionName']) ?></i>"</p>

<p>> <span>CITY</span>: "<i><?= htmlspecialchars($data['city']) ?></i>"</p>

<p>> <span>ERROR DESCRIPTION</span>: 
"<i>Access Denied. You Do Not Have The Permission To Access This Page On This Server</i>"</p>

<p>> <span>ERROR POSSIBLY CAUSED BY</span>: 
[<b>ip address rejected (<?= htmlspecialchars($user_ip) ?>), access forbidden, invalid configuration, too many requests...</b>]</p>

<p>> <span>ALLOWED PAGES</span>: 
[
<a href="/">Home</a>, 
<a href="/profil">Setting</a>, 
<a href="/register">Register</a>, 
<a href="/login">Login</a>
]</p>

<div id="map"></div>

</div>

<script>

// typing effect
const box = document.getElementById("typing-box");

const original = box.innerHTML;

box.innerHTML = "";

let i = 0;

const typing = setInterval(() => {

    box.innerHTML = original.slice(0, i) + "|";

    i++;

    if(i > original.length){
        clearInterval(typing);
        box.innerHTML = original;
        initMap();
    }

}, 5);


// init map
function initMap(){

    var map = L.map('map').setView(
        [<?= $data['lat'] ?>, <?= $data['lon'] ?>],
        10
    );

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    L.marker([<?= $data['lat'] ?>, <?= $data['lon'] ?>])
        .addTo(map)
        .bindPopup("<?= htmlspecialchars($data['city']) ?>")
        .openPopup();
}

</script>

</body>
</html>