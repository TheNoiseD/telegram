<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employes;
use App\Services\Messages;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    private array $commands = ['/in', '/out','/break','/back','/register'];
    private Messages $messages;

    public function __construct(Messages $messages)
    {
        $this->messages = $messages;
    }

    public function webhook(Request $request): void
    {
        $update = json_decode($request->getContent(), true);
        if (isset($update['message']['text'])) {
            $employe = Employes::search($update['message']['from']['id']);
            if(!$employe and $update['message']['text'] == '/register'){
                $this->Register($update);
            }else if ($employe and (in_array($update['message']['text'], $this->commands))){
                $this->proceedCommand($employe, $update);
            } else if(!$employe and (in_array($update['message']['text'], $this->commands))){
                $this->messages->send( 'Try /register first');
            }else{
                $this->messages->send('Unknown command');
            }
        }else{
            Log::debug('data update not valid ' . $update);
        }
    }

    private function Register($update): void
    {
        $employe = new Employes();
        $employe->tlg_id = $update['message']['from']['id'];
        $employe->name = $update['message']['from']['first_name'];
        if (isset($update['message']['from']['username'])){
            $employe->username = $update['message']['from']['username'];
        }else{
            $employe->username = $update['message']['from']['first_name'];
        }
        $message = urlencode('Welcome ' . $employe->username);
        if ($employe->save()){
            $this->messages->send($message);
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
        $message = urlencode('Hello ' . $employe->username );
        try {
            $attendance->save();
            $this->messages->send($message);
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
                $this->messages->send($message);
            }catch (\Exception $e){
                Log::channel('attendance')->error($e->getMessage());
                Log::channel('attendance')->error($employe->tlg_id.' err check_out in date: '.$date);
            }
        }else{
            $this->messages->send('Try /in before /out');
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
                $this->messages->send('Break');
            }catch (\Exception $e){
                Log::channel('attendance')->error($e->getMessage());
                Log::channel('attendance')->error($employe->tlg_id.' err break in date: '.$date);
            }
        }else{
            $this->messages->send('Try /in before /break');
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
                $this->messages->send('Back');
            }catch (\Exception $e){
                Log::channel('attendance')->error($e->getMessage());
                Log::channel('attendance')->error($employe->tlg_id.' err back in date: '.$date);
            }
        }else{
            $this->messages->send('Try /break before /back');
        }
    }

    private function proceedCommand($employe, mixed $update): void
    {
        $command = substr($update['message']['text'],1);
        $this->$command($employe,$update);
    }
}
