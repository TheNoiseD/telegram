<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class Messages
{
    private int $tlg_chat_id;
    private string $url;
    private string $method;
    private Client $client;
    public Request $request;

    public function __construct(Client $client, Request $request)
    {
        $this->tlg_chat_id = $request['message']['chat']['id'];
        $this->url = env('TELEGRAM_BOT_URL');
        $this->method = 'sendMessage';
        $this->client = $client;
        $this->request = $request;

    }

    public function send($tlg_message): void
    {
        $tlg_message_id = $this->request['message']['message_id'];
        $url = $this->url .'/'. $this->method . '?chat_id=' . $this->tlg_chat_id . '&text=' . $tlg_message . '&reply_to_message_id=' . $tlg_message_id;
        try {
            $this->client->request('GET',$url,['verify' => false]);
        }catch (GuzzleException $e){
            Log::error('error send message' . $e->getMessage());
        }

    }


}
