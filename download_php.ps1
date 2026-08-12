[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$html = Invoke-WebRequest -Uri "https://windows.php.net/downloads/releases/" -UseBasicParsing
$match = [regex]::Match($html.Content, 'php-8\.3\.\d+-Win32-vs16-x64\.zip')
if ($match.Success) {
    $url = "https://windows.php.net/downloads/releases/" + $match.Value
    Write-Host "Downloading $url..."
    Invoke-WebRequest -Uri $url -OutFile "C:\php-8.3.zip" -UseBasicParsing
    Write-Host "Downloaded successfully."
} else {
    Write-Host "Not found"
}
