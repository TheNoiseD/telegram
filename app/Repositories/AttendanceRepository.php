<?php

namespace App\Repositories;

use App\Interfaces\BaseRepository;
use App\Models\Attendance;
use App\Models\Employees;
use App\Services\Messages;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceRepository implements BaseRepository
{

    protected Carbon $date;
    public Request $request;
    protected Employees $employe;
    protected Messages $messages;
    protected Attendance $attendance;
    protected string $command;

    public function __construct(Request $request, Employees $employe, Messages $messages, Carbon $carbon, Attendance $attendance)
    {
        $this->date = $carbon->createFromTimestamp($request['message']['date'],'America/New_York');
        $this->request = $request;
        $this->employe = $employe;
        $this->messages = $messages;
        $this->attendance = $attendance;
        $this->command = substr($this->request['message']['text'], 1);
    }

    public function search():void
    {
        $employe = $this->employe->getEmploye($this->request['message']['from']['id']);
        if ($employe) {
            $this->employe = $employe;
            $attendance = $this->attendance->getCheckIn($this->employe->tlg_id);
            if ($attendance)
                $this->attendance = $attendance;
        }
    }

    public function create():void
    {
        switch ($this->command){
            case 'in':
                $this->attendance->tlg_id = $this->employe->tlg_id;
                $this->attendance->check_in = $this->date;
                break;
            case 'break':
                $this->attendance->break_in = $this->date;
                break;
            case 'back':
                $this->attendance->break_out = $this->date;
                break;
            case 'out':
                $this->attendance->check_out = $this->date;
                break;
            default:
                $this->messages->send('Invalid command');
        }

        try {
            $this->attendance->save();
        }catch (\Exception $e){
            Log::channel('attendance')->error('error create ' . $e->getMessage());
        }
    }

    public function register():void
    {
        $this->search();
        if(empty($this->employe->id)){
            $this->messages->send('Try /register first');
        }else{
            switch ($this->command){
                default:
                case 'in':
                    if (!empty($this->attendance->id)){
                        $this->messages->send('You are already checked in');
                    }else{
                        if ($this->attendance->getCurrentDate($this->employe->tlg_id,$this->date->format('Y-m-d', 'America/New_York'))){
                            $this->messages->send('You are already checked in today');
                            return;
                        }
                        $this->create();
                        $this->messages->send('Check in at ' . $this->date->format('H:i:s', 'America/New_York'));
                    }
                    break;
                case 'break':
                    if (empty($this->attendance->check_in)){
                        $this->messages->send('You are not checked in');
                    }else{
                        if (!empty($this->attendance->break_in)){
                            $this->messages->send('You are already on break');
                        }else{
                            $this->create();
                            $this->messages->send('Break at ' . $this->date->format('H:i:s', 'America/New_York'));
                        }
                    }
                    break;
                case 'back':
                    if (empty($this->attendance->check_in)){
                        $this->messages->send('You are not checked in');
                    }else{
                        if (empty($this->attendance->break_in)){
                            $this->messages->send('You are not on break');
                        }else{
                            if (!empty($this->attendance->break_out)){
                                $this->messages->send('You are already back');
                            }else{
                                $this->create();
                                $this->messages->send('Back at ' . $this->date->format('H:i:s', 'America/New_York'));
                            }
                        }
                    }
                    break;
                case 'out':
                    if (empty($this->attendance->check_in)){
                        $this->messages->send('You are not checked in');
                    }else{
                        if (!empty($this->attendance->check_out)){
                            $this->messages->send('You are already checked out');
                        }else{
                            $this->create();
                            $this->messages->send('Check out at ' . $this->date->format('H:i:s', 'America/New_York'));
                        }
                    }
                    break;

            }
        }
    }
}
