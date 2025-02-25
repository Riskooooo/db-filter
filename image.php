<?php
require_once 'vendor/autoload.php';
require_once 'header.php';
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxClient;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
// Récupérer l'URL d'autorisation/de connexion - Fetch the Authorization/Login URL
$authUrl = $authHelper->getAuthUrl($callbackUrl);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image - Life'Scape</title>
    <link rel="stylesheet" href="image.css">
</head>
<body>
</body>
</html>

<?php if(isset($_SESSION['code'])): ?>
    <script>
        var code = "<?php echo $_SESSION['code']; ?>";
        var credentials = "APPKEY:APPSECRET"; // Remplacer par vos identifiants - Replace with your credentials !
        var encodedCredentials = btoa(credentials);
        
        var myHeaders = new Headers({
            "Authorization": `Basic ${encodedCredentials}`,
            "Content-Type": "application/x-www-form-urlencoded"
        });
        
        var urlencoded = new URLSearchParams({
            "code": code,
            "grant_type": "authorization_code",
            "redirect_uri": "https://test-gd-image.test/"
        });
        
        var requestOptions = {
            method: 'POST',
            headers: myHeaders,
            body: urlencoded,
            redirect: 'follow',
        };

        fetch("https://api.dropboxapi.com/oauth2/token", requestOptions)
        .then(response => response.json())  // Convertit la réponse en JSON
        .then(data => {
            if (data.refresh_token) {
                console.log("refresh_token", data.access_token);
                access_token = data.access_token;
            } else {
                console.error("No refresh_token in the response", data);
            }
        })
        .catch(error => console.log('error', error));
    </script>
<?php else: ?>
    <script>console.log("Erreur: code d'autorisation non défini.");</script>
<?php endif; ?>

<?php
function refreshToken() {
        $arr = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.dropbox.com/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=refresh_token&refresh_token=YOUR_REFRESHTOKEN"); // Remplacer par le refresh token - Replace with the refresh token
        curl_setopt($ch, CURLOPT_USERPWD, 'APPKEY'. ':' . 'APPSECRET'); // Remplacer par vos identifiants - Replace with your credentials
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $result_arr = json_decode($result,true);
        

        if (curl_errno($ch)) {
            $arr = ['status'=>'error','token'=>null];
        }elseif(isset($result_arr['access_token'])){
            $arr = ['status'=>'okay','token'=>$result_arr['access_token']];
        }
        curl_close($ch);
        return $arr;
}

$response = refreshToken();

if ($response['status'] === 'okay') {
    $accessToken = $response['token'];
} else {
    echo "Une erreur est survenue avec le 'refresh token' !";
}

$folderPath = '/Photos/A TRAITER'; // Remplacer par le chemin vers le dossier contenant les images - Replace with the path to the folder containing the images
$localDestinationFolder = 'images';

processFolder($accessToken, $folderPath, $localDestinationFolder);

function processFolder($accessToken, $folderPath, $localDestinationFolder) {
    $imageCount = countImagesInFolder($accessToken, $folderPath);

    if ($imageCount < 10) {
        if ($imageCount <= 0) {
            echo "<br><p><font style='color: red;'><b>Erreur : Le dossier est vide ... (ajouter des images - 10 maximum)</b></font></p><br>";
            echo "<a href='loading.php' class='start'>Relancer la conversion des images</a>";
        } else {
            $files = listFolderContents($accessToken, $folderPath);
    
            if (!file_exists($localDestinationFolder)) {
                mkdir($localDestinationFolder, 0777, true);
            }
    
            foreach ($files as $file) {
                if ($file['.tag'] === 'file') {
                    $nameImg = basename($file['path_display']);
                    $localFilePath = rtrim($localDestinationFolder, '/') . '/' . basename($file['path_display']);
                    downloadFile($accessToken, $file['path_display'], $localFilePath, $nameImg);
                }
            }
            downloadAllImages($accessToken, $folderPath, $localDestinationFolder, $nameImg);
            deleteDirectory($localDestinationFolder);
        }
    } else {
        echo "<br><p><font style='color: red;'><b>Erreur : Le dossier contient trop d'images (10 maximum)</b></font></p><br>";
        echo "<a href='loading.php' class='start'>Relancer la conversion des images</a>";
    }
}

function countImagesInFolder($accessToken, $folderPath) {
    $files = listFolderContents($accessToken, $folderPath);
    $imageCount = 0;

    foreach ($files as $file) {
        if ($file['.tag'] === 'file') {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $imageCount++;
            }
        }
    }
    return $imageCount;
}

function listFolderContents($accessToken, $folderPath) {
    $ch = curl_init('https://api.dropboxapi.com/2/files/list_folder');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'path' => $folderPath,
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    
    if (isset($data['entries'])) {
        return $data['entries'];
    } else {
        echo "<br><p><font style='color: red;'>Erreur: Impossible de lister les fichiers du dossier.<br>Vérifier le Token de l'application sur DropBox !</font></p><br>";
        print_r($data);
        return [];
    }
}

function downloadFile($accessToken, $folderPath, $localFilePath, $nameImg) {
    $ch = curl_init('https://content.dropboxapi.com/2/files/download');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Dropbox-API-Arg: ' . json_encode(['path' => $folderPath]),
    ]);

    $fileContent = curl_exec($ch);
    curl_close($ch);

    if ($fileContent) {
        file_put_contents($localFilePath, $fileContent);
        generateImageWithFilter($localFilePath, $accessToken, $nameImg);
    } else {
        echo "<br><p><font style='color: red;'>Erreur lors du téléchargement de '$folderPath'.</font></p><br>";
    }
}

function downloadAllImages($accessToken, $folderPath, $localFolder, $nameImg) {
    $files = listFolderContents($accessToken, $folderPath);
    foreach ($files as $file) {
        if ($file['.tag'] === 'file') {
            $fileName = $file['name'];
            $filePath = $file['path_lower'];
            $localFilePath = $localFolder . '/' . $fileName;

            downloadFile($accessToken, $filePath, $localFilePath, $nameImg);
        }
    }
}

function deleteDropboxFolder($accessToken, $imagePath) {
    $app = new DropboxApp("", "", $accessToken);
    $dropbox = new Dropbox($app);

    try {
        $dropbox->delete($imagePath);
    } catch (DropboxClientException $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
    }
}

function deleteDirectory($localDestinationFolder) {
    if (!is_dir($localDestinationFolder)) {
        echo "<br><p>Le chemin spécifié n'est pas un dossier (erreur suppression local)</p><br>";
        return;
    }

    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($localDestinationFolder, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getRealPath());
        } else {
            unlink($item->getRealPath());
        }
    }

    rmdir($localDestinationFolder);
    echo "<a href='loading.php' class='start'>Relancer la conversion des images</a>";
}

function generateImageWithFilter($destinationPath, $accessToken, $nameImg) {
    $destination = imagecreatefromjpeg($destinationPath);
    if (!$destination) {
        echo "<p>Erreur: Impossible de créer l'image à partir de $destinationPath</p><br>";
        return null;
    }
    imagefilter($destination, IMG_FILTER_BRIGHTNESS, -30);
    imagefilter($destination, IMG_FILTER_COLORIZE, 60, 40, 0, 30);
    // Saturation
    $width  = imagesx($destination);
    $height = imagesy($destination);   
    $im2  = imagecreatetruecolor($width, $height);
    imagecopy($im2, $destination, 0, 0, 0, 0, $width, $height);
    imagefilter($im2, IMG_FILTER_GRAYSCALE);
    imagecopymerge($destination, $im2, 0, 0, 0, 0, $width, $height, 100-60); // intval(100 - 60)
    imagedestroy($im2);

    imagefilter($destination, IMG_FILTER_COLORIZE, 50, 20, 0, 30);
    $source = imagecreatefrompng("ombredecontour.png");
    if (!$source) {
        echo "<p>Erreur: Impossible de charger le masque ombredecontour.png</p><br>";
        imagedestroy($destination);
        return null;
    }
    // Superposition de l'image avec le filtre - Overlaying the image with the filter
    $largeur_source = imagesx($source);
    $hauteur_source = imagesy($source);
    imagealphablending($source, true);
    imagesavealpha($source, true);
    $destination_x = ($width - $largeur_source)/2;
    $destination_y =  ($height - $hauteur_source)/2;  
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $width, $height, $largeur_source, $hauteur_source);

    $tempFilePath = tempnam(sys_get_temp_dir(), 'image') . '.jpg';
    if (!imagejpeg($destination, $tempFilePath)) {
        echo "<p>Erreur lors de l'écriture du fichier '$tempFilePath'.</p><br>";
        imagedestroy($destination);
        imagedestroy($source);
        return null;
    }

    imagedestroy($source);
    $randomNumber = rand(1, 9999); // Nombre aléatoire - Random number
    $fileName = 'gd-image-filtre-'. $randomNumber .'.jpg';
    $fileName2 = 'gd-image-'. $randomNumber .'.jpg';
    $destinationFolder = '/Photos/TRAITEES/';
    uploadBackup($destinationPath, $fileName2, '/Photos/BACKUP/', $accessToken);
    uploadToDropbox($tempFilePath, $fileName, $destinationFolder, $accessToken);
    deleteDropboxFolder($accessToken, '/Photos/A TRAITER/' . $nameImg);
    unlink($tempFilePath);
    return $tempFilePath;
}

function uploadBackup($filePath, $fileName, $destinationFolder, $accessToken) {

    $dropboxToken = $accessToken;
    $dropboxPath = $destinationFolder . $fileName;

    // Préparation de la requête - Preparing the request
    $url = 'https://content.dropboxapi.com/2/files/upload';
    $headers = [
        'Authorization: Bearer ' . $dropboxToken,
        'Content-Type: application/octet-stream',
        'Dropbox-API-Arg: ' . json_encode([
            'path' => $dropboxPath,
            'mode' => 'add',
            'autorename' => true,
            'mute' => false
        ])
    ];

    $fileContent = file_get_contents($filePath);
    // Effectue la requête - Perform the query
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Erreur cURL: ' . curl_error($ch);
        echo 'Réponse Dropbox: ' . $response;
        echo '<p><font style="color: red; font-weight: bold; font-size: 22px;">Problème lors de la synchronisation backup des images sources avec DropBox !</font></p><br><br>';
    }
    curl_close($ch);
}

function uploadToDropbox($filePath, $fileName, $destinationFolder, $accessToken) {

    $dropboxToken = $accessToken;
    $dropboxPath = $destinationFolder . $fileName;

    // Préparation de la requête - Preparing the request
    $url = 'https://content.dropboxapi.com/2/files/upload';
    $headers = [
        'Authorization: Bearer ' . $dropboxToken,
        'Content-Type: application/octet-stream',
        'Dropbox-API-Arg: ' . json_encode([
            'path' => $dropboxPath,
            'mode' => 'add',
            'autorename' => true,
            'mute' => false
        ])
    ];

    $fileContent = file_get_contents($filePath);
    // Effectue la requête - Perform the query
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Erreur cURL: ' . curl_error($ch);
        echo 'Réponse Dropbox: ' . $response;
        echo '<p><font style="color: red; font-weight: bold; font-size: 22px;">Problème lors de la synchronisation avec DropBox !</font></p><br><br>';
    } else {
        echo '<p><font style="color: green; font-weight: bold; font-size: 30px;">✅ Image synchronisée avec DropBox !</font></p>';
    }
    curl_close($ch);
}
?>