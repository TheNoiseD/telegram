<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    private $commands = ['/in', '/out','/break','/back'];
    public function webhook(Request $request): void
    {
        $update = json_decode($request->getContent(), true);
        if (isset($update['message']['text'])) {
            Log::info($update);
            $employe = Employes::search($update['message']['from']['id']);
            if ($employe and (in_array($update['message']['text'], $this->commands))){
                $this->proceedCommand($employe, $update);
            } else if(!$employe) {
                $this->addEmploye($update);
            }else{
                $this->sendMessage($employe->tlg_id, 'unknown command');
            }
        }else{
            Log::debug($update);
        }

    }

    private function sendMessage($tlg_id, string $string): void
    {
        $string = urlencode($string);
        file_get_contents('https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/sendMessage?chat_id=' . $tlg_id . '&text=' . $string);
    }

    private function addEmploye(mixed $update): void
    {
        Log::debug('enter addEmploye');
        $employe = new Employes();
        $employe->tlg_id = $update['message']['from']['id'];
        $employe->name = $update['message']['from']['first_name'];
        if (isset($update['message']['from']['username'])){
            $employe->username = $update['message']['from']['username'];
        }else{
            $employe->username = $update['message']['from']['first_name'];
        }
        $message = 'Hello ' . $employe->username;
        if ($employe->save()){
            $this->sendMessage($employe->tlg_id, $message);
        }
    }

    /**
     * check in attendance
     */
    private function In($employe,$update): void
    {
        $date = Carbon::createFromTimestamp($update['message']['date'],'America/New_York');
        $attendance = Attendance::where('tlg_id', $employe->tlg_id)
            ->where('check_out', null)->first();
        if ($attendance){
            $attendance->check_out = $date;
            $attendance->save();
        }
        $attendance = new Attendance();
        $attendance->tlg_id = $employe->tlg_id;
        $attendance->check_in = $date;
        $message = 'Hello ' . $employe->username ;
        Log::debug('enter In');
        try {
            $attendance->save();
            $this->sendMessage($update['message']['chat']['id'], $message);
        }catch (\Exception $e){
            Log::channel('attendance')->error($e->getMessage());
            Log::channel('attendance')->error($employe->tlg_id.' err check_in in date: '.$date);
        }
    }

    /**
     * check out attendance
     */
    private function Out($employe,$update): void
    {

        $date = Carbon::createFromTimestamp($update['message']['date'],'America/New_York');
        $attendance = Attendance::where('tlg_id', $employe->tlg_id)
            ->where('check_out', null)->first();
        $message = 'See you later ' . $employe->username ;
        if ($attendance){
            $attendance->check_out = $date;
            try {
                $attendance->save();
                $this->sendMessage($update['message']['chat']['id'], $message);
            }catch (\Exception $e){
                Log::channel('attendance')->error($e->getMessage());
                Log::channel('attendance')->error($employe->tlg_id.' err check_out in date: '.$date);
            }
        }else{
            $this->sendMessage($update['message']['chat']['id'], 'try /in before /out');
        }
    }

    /**
     * break attendance
     */
    private function Break($employe,$update): void
    {
        $date = Carbon::createFromTimestamp($update['message']['date'],'America/New_York');
        $attendance = Attendance::where('tlg_id', $employe->tlg_id)
            ->where('check_out', null)
            ->where('break_in',null)->first();
        if ($attendance){
            $attendance->break_in = $date;
            try {
                $attendance->save();
                $this->sendMessage($update['message']['chat']['id'], 'break');
            }catch (\Exception $e){
                Log::channel('attendance')->error($e->getMessage());
                Log::channel('attendance')->error($employe->tlg_id.' err break in date: '.$date);
            }
        }else{
            $this->sendMessage($update['message']['chat']['id'], 'try /in before /break');
        }
    }

    /**
     * back attendance
     */
    private function Back($employe,$update): void
    {
        $date = Carbon::createFromTimestamp($update['message']['date'],'America/New_York');
        $attendance = Attendance::where('tlg_id', $employe->tlg_id)
            ->where('check_out', null)
            ->where('break_in','!=',null)
            ->where('break_out',null)->first();
        if ($attendance){
            $attendance->break_out = $date;
            try {
                $attendance->save();
                $this->sendMessage($update['message']['chat']['id'], 'back');
            }catch (\Exception $e){
                Log::channel('attendance')->error($e->getMessage());
                Log::channel('attendance')->error($employe->tlg_id.' err back in date: '.$date);
            }
        }else{
            $this->sendMessage($update['message']['chat']['id'], 'try /break before /back');
        }
    }

    private function proceedCommand($employe, mixed $update): void
    {
        $command = substr($update['message']['text'],1);
        $this->$command($employe,$update);
    }
}
