<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use Carbon\Carbon;

use DB;

use App\User;
use App\Prodi;
use App\Fakultas;
use App\ChatLog;
use Telegram;

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

class LineBotController extends Controller
{
    public function updates(Request $request) {
      // get request body and line signature header
    	$body 	   = file_get_contents('php://input');
    	$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

    	// log body and signature
    	file_put_contents('php://stderr', 'Body: '.$body);

    	// is LINE_SIGNATURE exists in request header?
    	if (empty($signature)){
    		return $response->withStatus(400, 'Signature not set');
    	}

    	// is this request comes from LINE?
    	if(env('PASS_SIGNATURE') == false && ! SignatureValidator::validateSignature($body, env('CHANNEL_SECRET'), $signature)){
    		return $response->withStatus(400, 'Invalid signature');
    	}

    	// init bot
    	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(env('CHANNEL_ACCESS_TOKEN'));
    	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => env('CHANNEL_SECRET')]);

    	$data = json_decode($body, true);
    	foreach ($data['events'] as $event)
    	{
    		if ($event['type'] == 'message')
    		{
    			if($event['message']['type'] == 'text')
    			{
            if(strcasecmp($event['message']['text'], "halo")==0) {
      				// send same message as reply to user
      				// $result = $bot->replyText($event['replyToken'], $event['message']['text']);
              $opts = array(
                'http'=>array(
                  'method'=>"GET",
                  'header'=>"Authorization: Bearer ".env('CHANNEL_ACCESS_TOKEN')
                )
              );
              $context = stream_context_create($opts);

              $userID = $event['source']['userId'];
              $website = "https://api.line.me/v2/bot/profile/".$userID;
              $user = file_get_contents($website, false, $context);

              $user = json_decode($user, true);
              $userName = $user['displayName'];


              $text = "Hai juga, salam kenal ".$userName;

      				// or we can use pushMessage() instead to send reply message
      				$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);
      				$result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);

      				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
            } else {
              //pushMessage() instead to send reply message
              $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("Maaf perintah tidak ditemukan");
              $result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);

              return $result->getHTTPStatus() . ' ' . $result->getRawBody();
            }
    			}
    		}
    	}
    }
}