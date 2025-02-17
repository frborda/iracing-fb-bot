<?php
namespace Src;

class IracingAPI {
    private $cookieFile;
    private $username;
    private $password;
    private $loginUrl;

    public function __construct($username, $password, $loginUrl, Database $database, $cookieFile = 'cookies.txt') {
        $this->username = $username;
        $this->password = $password;
        $this->loginUrl = $loginUrl;
        $this->database = $database;
        $this->cookieFile = __DIR__ . "/../cache/{$cookieFile}";
    }

    private function encodePassword() {
        return base64_encode(hash('sha256', $this->password . strtolower($this->username), true));
    }

    public function authenticate() {
        $payload = json_encode([
            "email" => $this->username,
            "password" => $this->encodePassword()
        ]);

        echo $this->encodePassword();

        $ch = curl_init($this->loginUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);

        if ($httpCode == 200 && array_key_exists('message',$json) && $json['message'] == 'recaptcha verification required and not supplied') {
            echo "❌ Error en la autenticación: Requiere verificación reCAPTCHA\n";
            sleep(600);
            return false;
        }


        if ($httpCode == 200 && isset($json['authcode'])) {
            echo "✅ Autenticación exitosa. Cookie guardada.\n";
            return true;
        } else {
            echo "❌ Error en la autenticación: " . ($json['message'] ?? 'Desconocido') . "\n";
            return false;
        }
    }

    private function getData($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$response, $httpCode];
    }

    private function getContentLink($url) {
        $content = file_get_contents($url);
        return json_decode($content, true);
    }

    public function getMemberStats($ids, $relogin = true) {
        $dataUrl = "https://members-ng.iracing.com/data/member/get?cust_ids={$ids}&include_licenses=true";
        [$response, $httpCode] = $this->getData($dataUrl);
        $json = json_decode($response, true);
        
        if ($httpCode == 200 && isset($json['link'])) {
            return $this->getContentLink($json['link']);
        } elseif ($httpCode == 401 && $relogin) {
            echo "🔄 Autenticación requerida. Reintentando...\n";
            if ($this->authenticate()) {
                return $this->getMemberStats($ids, false);
            }
        } else {
            echo "❌ Error al obtener datos: {$response}\n";
        }
        return false;
    }

    public function getMemberRecentRaces($id, $relogin = true) {
        $dataUrl = "https://members-ng.iracing.com/data/stats/member_recent_races?cust_id={$id}";
        [$response, $httpCode] = $this->getData($dataUrl);
        $json = json_decode($response, true);
        if ($httpCode == 200 && isset($json['link'])) {
            $link = $json['link'];
            echo "{$link}\n";
            return $this->getContentLink($link);
        } elseif ($httpCode == 401 && $relogin) {
            echo "🔄 Autenticación requerida. Reintentando...\n";
            if ($this->authenticate()) {
                return $this->getMemberRecentRaces($id, false);
            }
        } else {
            echo "❌ Error al obtener datos: {$response}\n";
        }
        return false;
    }
}
