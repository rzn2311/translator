
<?php

header("Content-Type: application/json; charset=UTF-8");

function sendJson($statusCode, $data) {
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

// ==========================================
// 1. REQUEST METHOD
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendJson(405, [
        "success" => false,
        "error" => "Gunakan metode POST."
    ]);
}

// ==========================================
// 2. READ JSON
// ==========================================

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    sendJson(400, [
        "success" => false,
        "error" => "Data JSON tidak valid."
    ]);
}

// ==========================================
// 3. INPUT VALIDATION
// ==========================================

$text = trim($data["text"] ?? "");
$mode = $data["mode"] ?? "";

if ($text === "") {
    sendJson(400, [
        "success" => false,
        "error" => "Teks tidak boleh kosong."
    ]);
}

if (!function_exists("mb_strlen")) {
    sendJson(500, [
        "success" => false,
        "error" => "Aktifkan ekstensi PHP mbstring."
    ]);
}

if (mb_strlen($text, "UTF-8") > 450) {
    sendJson(400, [
        "success" => false,
        "error" => "Teks maksimal 450 karakter."
    ]);
}

// ==========================================
// 4. LANGUAGE MODE
// ==========================================

$modes = [
    "id|en" => ["id", "en"],
    "en|id" => ["en", "id"]
];

if (!isset($modes[$mode])) {
    sendJson(400, [
        "success" => false,
        "error" => "Mode bahasa tidak valid."
    ]);
}

[$source, $target] = $modes[$mode];

// ==========================================
// 5. NORMALIZE TEXT
// ==========================================

$normalizedText = mb_strtolower(
    trim($text),
    "UTF-8"
);

$normalizedText = preg_replace(
    '/\s+/u',
    ' ',
    $normalizedText
);

// ==========================================
// 6. MANUAL TRANSLATION DICTIONARY
// ==========================================

$dictionary = [

    "id|en" => [

        "sayang" => "love",
        "aku sayang kamu" => "I love you",
        "saya sayang kamu" => "I love you",
        "terima kasih" => "thank you",
        "selamat pagi" => "good morning",
        "selamat siang" => "good afternoon",
        "selamat malam" => "good evening",
        "sampai jumpa" => "goodbye",
        "apa kabar?" => "how are you?",
        "apa kabar" => "how are you?",
        "di mana toilet?" => "where is the toilet?",
        "di mana toilet" => "where is the toilet?",
        "berapa harganya?" => "how much is it?",
        "berapa harganya" => "how much is it?",
        "tolong bantu saya" => "please help me",
        "saya tidak mengerti" => "I don't understand",
        "permisi" => "excuse me",
        "saya lapar" => "I am hungry",
        "saya haus" => "I am thirsty",
        "aku cinta kamu" => "I love you",
        "aku rindu kamu" => "I miss you",
        "hati hati" => "take care",
        "tidak apa apa" => "it's okay",
        "maaf" => "sorry",
        "iya" => "yes",
        "tidak" => "no",
        "madu" => "honey",
        "kucing" => "cat",
        "anjing" => "dog",
        "rumah" => "house",
        "makan" => "eat",
        "minum" => "drink",
        "air" => "water",
        "buku" => "book",
        "sekolah" => "school",
        "teman" => "friend",
        "keluarga" => "family",
        "bahagia" => "happy",
        "sedih" => "sad",
        "cantik" => "beautiful",
        "tampan" => "handsome",
        "baik" => "good",
        "buruk" => "bad",
        "besar" => "big",
        "kecil" => "small",
        "cepat" => "fast",
        "lambat" => "slow"

    ],

    "en|id" => [

        "love" => "cinta",
        "i love you" => "aku cinta kamu",
        "thank you" => "terima kasih",
        "good morning" => "selamat pagi",
        "good afternoon" => "selamat siang",
        "good evening" => "selamat malam",
        "goodbye" => "sampai jumpa",
        "how are you?" => "apa kabar?",
        "how are you" => "apa kabar?",
        "where is the toilet?" => "di mana toilet?",
        "where is the toilet" => "di mana toilet?",
        "how much is it?" => "berapa harganya?",
        "how much is it" => "berapa harganya?",
        "please help me" => "tolong bantu saya",
        "i don't understand" => "saya tidak mengerti",
        "excuse me" => "permisi",
        "i am hungry" => "saya lapar",
        "i am thirsty" => "saya haus",
        "i miss you" => "aku merindukanmu",
        "take care" => "hati-hati",
        "it's okay" => "tidak apa-apa",
        "sorry" => "maaf",
        "yes" => "iya",
        "no" => "tidak",
        "honey" => "sayang",
        "cat" => "kucing",
        "dog" => "anjing",
        "house" => "rumah",
        "eat" => "makan",
        "drink" => "minum",
        "water" => "air",
        "book" => "buku",
        "school" => "sekolah",
        "friend" => "teman",
        "family" => "keluarga",
        "happy" => "bahagia",
        "sad" => "sedih",
        "beautiful" => "cantik",
        "handsome" => "tampan",
        "good" => "baik",
        "bad" => "buruk",
        "big" => "besar",
        "small" => "kecil",
        "fast" => "cepat",
        "slow" => "lambat"

    ]

];

// ==========================================
// 7. CHECK MANUAL DICTIONARY
// ==========================================

$dictionaryMode = $source . "|" . $target;

if (
    isset($dictionary[$dictionaryMode]) &&
    isset($dictionary[$dictionaryMode][$normalizedText])
) {

    $translation = $dictionary[$dictionaryMode][$normalizedText];

    sendJson(200, [
        "success" => true,
        "original" => $text,
        "translation" => $translation,
        "source" => $source,
        "target" => $target,
        "provider" => "Local Dictionary"
    ]);

}

// ==========================================
// 8. CHECK CURL
// ==========================================

if (!function_exists("curl_init")) {
    sendJson(500, [
        "success" => false,
        "error" => "Aktifkan ekstensi PHP cURL di Laragon."
    ]);
}

// ==========================================
// 9. MYMEMORY TRANSLATION API
// ==========================================

$url = "https://api.mymemory.translated.net/get?" .
http_build_query([
    "q" => $text,
    "langpair" => $source . "|" . $target
]);

$curl = curl_init($url);

curl_setopt_array($curl, [

    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 25,

    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,

    CURLOPT_USERAGENT => "IoT-Universal-Translator/2.0",

    CURLOPT_HTTPHEADER => [
        "Accept: application/json"
    ]

]);

$response = curl_exec($curl);

$curlError = curl_error($curl);

$httpCode = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);

curl_close($curl);

// ==========================================
// 10. CHECK API RESPONSE
// ==========================================

if ($response === false || $httpCode !== 200) {

    sendJson(502, [
        "success" => false,
        "error" => "Gagal mengakses layanan penerjemah.",
        "detail" => $curlError,
        "http_code" => $httpCode
    ]);

}

$json = json_decode($response, true);

if (!is_array($json)) {

    sendJson(502, [
        "success" => false,
        "error" => "Respons penerjemah tidak valid."
    ]);

}

if (($json["responseStatus"] ?? 0) != 200) {

    sendJson(502, [
        "success" => false,
        "error" => "Penerjemah gagal memproses teks.",
        "detail" => $json["responseDetails"] ?? "Tidak ada detail."
    ]);

}

// ==========================================
// 11. GET TRANSLATION
// ==========================================

$translation =
    $json["responseData"]["translatedText"] ?? "";

$translation = html_entity_decode(
    $translation,
    ENT_QUOTES | ENT_HTML5,
    "UTF-8"
);

$translation = trim($translation);

if ($translation === "") {

    sendJson(502, [
        "success" => false,
        "error" => "Hasil terjemahan kosong."
    ]);

}

// ==========================================
// 12. SEND RESULT
// ==========================================

sendJson(200, [

    "success" => true,

    "original" => $text,

    "translation" => $translation,

    "source" => $source,

    "target" => $target,

    "provider" => "MyMemory API"

]);

?>
