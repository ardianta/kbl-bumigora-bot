<?php

// kode sumber: https://gist.github.com/banghasan/67f365e6df3e3b8dc1cfa8208b52a305

// session diperlukan untuk simpan step
session_start();

$TOKEN      = "TOKEN_BOT";
$usernamebot= "@kbl_bumigora_bot";




// aktifkan ini jika lagi debugging
$debug = true;
 

// fungsi untuk mengirim/meminta/memerintahkan sesuatu ke bot 
function request_url($method){
    global $TOKEN;
    return "https://api.telegram.org/bot" . $TOKEN . "/". $method;
}
 
// fungsi untuk meminta pesan 
// bagian ebook di sesi Meminta Pesan, polling: getUpdates
function get_updates($offset){
    $url = request_url("getUpdates")."?offset=".$offset;
        $resp = file_get_contents($url);
        $result = json_decode($resp, true);
        if ($result["ok"]==1)
            return $result["result"];
        return array();
}


// fungsi untuk mebalas pesan, 
function send_reply($chatid, $msgid, $text){
    global $debug;
    $data = array(
        'chat_id' => $chatid,
        'text'  => $text
        //'reply_to_message_id' => $msgid   // <---- biar ada reply nya balasannya, opsional, bisa dihapus baris ini
    );
    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );
    $context  = stream_context_create($options); 
    $result = file_get_contents(request_url('sendMessage'), false, $context);

    if ($debug) 
        print_r($result);
}

 
// fungsi mengolahan pesan, menyiapkan pesan untuk dikirimkan
function create_response($text, $message)
{
    global $usernamebot;
    // inisiasi variable hasil yang mana merupakan hasil olahan pesan
    $hasil = '';  

    $fromid = $message["from"]["id"]; // variable penampung id user
    $chatid = $message["chat"]["id"]; // variable penampung id chat
    $pesanid= $message['message_id']; // variable penampung id message


    // variable penampung username nya user
    isset($message["from"]["username"])
        ? $chatuser = $message["from"]["username"]
        : $chatuser = '';
    

    // variable penampung nama user

    isset($message["from"]["last_name"]) 
        ? $namakedua = $message["from"]["last_name"] 
        : $namakedua = '';   
    $namauser = $message["from"]["first_name"]. ' ' .$namakedua;

    // ini saya pergunakan untuk menghapus kelebihan pesan spasi yang dikirim ke bot.
    $textur = preg_replace('/\s\s+/', ' ', $text); 

    // memecah pesan dalam 2 blok array, kita ambil yang array pertama saja
    $command = explode(' ',$textur,2); //

	// identifikasi perintah (yakni kata pertama, atau array pertamanya)
    switch ($command[0]) {

		case '/daftar':
			$hasil = "Ok! Sekarang tuliskan nama lengkapmu!";
			$_SESSION['chat_id'] = $chatid;
			$_SESSION['step'] = 'nama';
			break;

        // balasan default jika pesan tidak di definisikan
        default:
			//if(isset($_SESSION['chatid'])){
				if(isset($_SESSION['step'])){
					switch($_SESSION['step']){
						case 'nama':
							//tampung dulu namanya ke SESSION
							$_SESSION['nama'] = $text;
							$hasil = "Ok, {$text}. Kirimkan nomer telepon yang bisa dihubungi!";
							$_SESSION['chat_id'] = $chatid;
							$_SESSION['step'] = 'telepon';
							break;
						case 'telepon':
							$_SESSION['telepon'] = $text;
							$hasil = "Terima kasih, sekarang kirimkan alamat emailmu!";
							$_SESSION['chat_id'] = $chatid;
							$_SESSION['step'] = 'email';
							break;
						case 'email':
							$_SESSION['email'] = $text;
							$hasil = "Nama {$_SESSION['nama']}, telp: {$_SESSION['telepon']}, email: {$_SESSION['email']}. Apakah sudah benar? (ya/tidak)";
							$_SESSION['chat_id'] = $chatid;
							$_SESSION['step'] = 'verifikasi';
							break;
						case 'verifikasi':
							if($text == 'ya'){
								$hasil = "Terima kasih sudah bergabung di Klub Belajar Linux (KBL) Bumigora STMIK Bumigora Mataram. Silahkan bergabung ke grup https://telegram.me/kbl_bumigora";
								$_SESSION['step'] = 'selesai';
							} else {
								$hasil = "Silahkan ulangi dengan perintah /daftar";
								session_destroy();
							}
							
							break;
						case 'selesai':
							// simpan ke database
							
							session_destroy();
							break;
						
					}
					
					
				} else {
					$hasil = 'Hi, Saya Bot yang akan membantumu mendaftar KBL Bumigora.';
				}
			//}
            break;
			
    }
	
	print_r($_SESSION);

    return $hasil;
}
 
// jebakan token, klo ga diisi akan mati
if (strlen($TOKEN)<20) 
    die("Token mohon diisi dengan benar!\n");

// fungsi pesan yang sekaligus mengupdate offset 
// biar tidak berulang-ulang pesan yang di dapat 
function process_message($message)
{
    $updateid = $message["update_id"];
    $message_data = $message["message"];
    if (isset($message_data["text"])) {
    $chatid = $message_data["chat"]["id"];
        $message_id = $message_data["message_id"];
        $text = $message_data["text"];
        $response = create_response($text, $message_data);
        if (!empty($response))
          send_reply($chatid, $message_id, $response);
    }
    return $updateid;
}
 
// hapus baris dibawah ini, jika tidak dihapus berarti kamu kurang teliti!
//die("Mohon diteliti ulang codingnya..\nERROR: Hapus baris atau beri komen line ini yak!\n");
 
// hanya untuk metode poll
// fungsi untuk meminta pesan 
function process_one()
{
    global $debug;
    $update_id  = 0;
    echo "-";
 
    if (file_exists("last_update_id")) 
        $update_id = (int)file_get_contents("last_update_id");
 
    $updates = get_updates($update_id);

    // jika debug=0 atau debug=false, pesan ini tidak akan dimunculkan
    if ((!empty($updates)) and ($debug) )  {
        echo "\r\n===== isi diterima \r\n";
        print_r($updates);
    }
 
    foreach ($updates as $message)
    {
        echo '+';
        $update_id = process_message($message);
	}
    
	// @TODO nanti ganti agar update_id disimpan ke database
    // update file id, biar pesan yang diterima tidak berulang
    file_put_contents("last_update_id", $update_id + 1);
}

// metode poll
// proses berulang-ulang
// sampai di break secara paksa
// tekan CTRL+C jika ingin berhenti 
while (true) {
    process_one();
    sleep(1);
}

// metode webhook
// secara normal, hanya bisa digunakan secara bergantian dengan polling
// aktifkan ini jika menggunakan metode webhook
/*
$entityBody = file_get_contents('php://input');
$pesanditerima = json_decode($entityBody, true);
process_message($pesanditerima);
*/
  
?>