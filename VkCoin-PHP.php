<?php 
if (!isset($_REQUEST)) { return; } 

include_once('API/lib.php');
$vkcoin = new VKCoinClient('id', 'ключ');

$confirmation_token = ''; 
$token 				= ''; 

$data = json_decode(file_get_contents('php://input')); 
switch ($data->type) { 
	case 'confirmation': echo $confirmation_token; break; 
	case 'message_new': 
		$user_id 	= $data->object->from_id;
		$peer_id	= $data->object->peer_id;
		$body		= $data->object->text;

		if(preg_match("/^пополнить\s?(\d+)?$/ui", $body, $out)) {
			if(!$out[1]) { sendMessage($peer_id, "Не указали сумму для пополнения"); return; }
			sendMessage($peer_id, "Ссылка: ". $vkcoin->generatePayLink(($out[1] * 1000), $rand = rand(1, 2000000000), true, true));
			// Присваему юзеру payload ($rand), для дальнейшей проверки
		}

		elseif(preg_match("/^вывод\s?(\d+)?$/ui", $body, $out)) {
			if(!$out[1]) { sendMessage($peer_id, "Не указали сумму для вывода"); return; }
			$vkcoin->sendTransfer($user_id, ($out[1] * 1000));
			sendMessage($peer_id, "Вк коины отправлены");
		}

		elseif(preg_match("/^проверить$/ui", $body)) {
			$link = new mysqli('хост', 'юзер', 'пароль', 'бд');
			$result = $vkcoin->getTransactions();
			for($i = 0; $i < 100; $i++) {
				if($result['response']['response'][$i]['from_id'] == $user_id && $result['response']['response'][$i]['payload'] == $stats['payload']) {
					mysqli_query($link, "UPDATE `accounts` SET `coins` = (`coins` + '".($result['response']['response'][$i]['amount'] / 1000)."'), `payload`='0' WHERE `uid` = '".$user_id."'");
					sendMessage($peer_id, "Пополнено: ". ($result['response']['response'][$i]['amount'] / 1000));
					break;
				} elseif(empty($result['response']['response'][$i]['from_id'])) { sendMessage($peer_id, "[ ❌ ] ".$stats['name'].", ваш перевод не был обнаружен!"); break; }
			}
		}

		echo('ok'); 
	break; 
} 

function sendMessage($user_id, $message) {
	global $token;
	file_get_contents('https://api.vk.com/method/messages.send?'. http_build_query(array('message' => $message, 'peer_id' => $user_id, 'access_token' => $token, 'v' => '5.80'))); 
}

?> 