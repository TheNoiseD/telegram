<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employees;
use App\Repositories\EmployesRepository;
use App\Services\Messages;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    private array $commands = ['/in', '/out','/break','/back','/register'];
    private Messages $messages;
    public array $update;
    Private EmployesRepository $employeesRepository;
    public function __construct(Messages $messages, Request $request, Employees $employe, EmployesRepository $employeesRepository)
    {
        $this->messages = $messages;
        $this->update = json_decode($request->getContent(), true);
        $this->employeesRepository = $employeesRepository;
    }
    public function webhook(): void
    {
        $this->proceedCommand();
    }
    private function Register(): void
    {
        $this->employeesRepository->register();
    }
    /**
     * check in attendance
     */
    private function In(): void
    {
        $this->employeesRepository->in();
        $employe = Employees::search($this->update['message']['from']['id']);
        if (!$employe) {
            $this->messages->send('Try /register first');
            return;
        }
        $date = Carbon::createFromTimestamp($this->update['message']['date'],'America/New_York');
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
    private function Out(): void
    {
        $date = Carbon::createFromTimestamp($this->update['message']['date'],'America/New_York');
        $employe = Employees::search($this->update['message']['from']['id']);
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
    private function Break(): void
    {
        $date = Carbon::createFromTimestamp($this->update['message']['date'],'America/New_York');
        $employe = Employees::search($this->update['message']['from']['id']);
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
    private function Back(): void
    {
        $date = Carbon::createFromTimestamp($this->update['message']['date'],'America/New_York');
        $employe = Employees::search($this->update['message']['from']['id']);
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

    private function proceedCommand(): void
    {
        $command = substr($this->update['message']['text'],1);
        if(in_array('/'.$command, $this->commands)){
            $this->$command();
        }else{
            $this->messages->send('Unknown command');
        }
    }
}
