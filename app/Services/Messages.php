<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

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

    }

    public function send($tlg_message): void
    {
        try {
            $this->client->request('GET',$this->url .'/'. $this->method . '?chat_id=' . $this->tlg_chat_id . '&text=' . $tlg_message,['verify' => false]);
        }catch (GuzzleException $e){
            Log::error('error send message' . $e->getMessage());
        }

    }


}
