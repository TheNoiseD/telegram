<?php

namespace App\Repositories;

use App\Interfaces\BaseRepository;
use App\Models\AbsencesTime;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Employees;
use App\Models\Schedules;
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
    protected AbsencesTime $absencesTime;
    protected string $command;
    protected mixed $param;
    protected BreakTime $break;

    public function __construct(Request $request, Employees $employe, Messages $messages, Carbon $carbon, Attendance $attendance,AbsencesTime $absencesTime, BreakTime $break)
    {
        $this->date = $carbon->createFromTimestamp($request['message']['date'],'America/New_York');
        $this->request = $request;
        $this->employe = $employe;
        $this->messages = $messages;
        $this->attendance = $attendance;
        $this->absencesTime = $absencesTime;
        $this->break = $break;
    }

    public function search():void
    {
        $employe = $this->employe->getEmploye($this->request['message']['from']['id']);
        if ($employe) {
            $this->employe = $employe;
            $attendance = $this->attendance->getCheckIn($this->employe->tlg_id);
            if ($attendance)
                $this->attendance = $attendance;
            $break = $this->break->getBreak($this->employe->tlg_id);
            if ($break)
                $this->break = $break;
        }
    }

    public function create():void
    {
        switch ($this->command){
            case 'in':
                if (strlen($this->param) > 0){
                    $this->attendance->check_in = Carbon::create($this->date->format('Y-m-d') . ' ' . $this->param,'America/New_York');
                }else{
                    $this->attendance->check_in = $this->date;
                }
                $this->attendance->tlg_id = $this->employe->tlg_id;
                break;
            case 'lunch':
                $this->attendance->lunch_in = $this->date;
                break;
            case 'back':
                if ($this->attendance->lunch_in != null  && $this->attendance->lunch_out == null) {
                    $diff = Carbon::createFromFormat('Y-m-d H:i:s', $this->attendance->lunch_in, 'America/New_York');
                    $diff = $diff->diff($this->date);
                    if ($diff->h >= 1) {
                        $diff = Carbon::createFromFormat('H:i:s', $diff->format('%H:%I:%S'), 'America/New_York');
                        $diff->subHour();
                        $diff = $diff->format('%H:%I:%S');
                    }else{
                        $diff = $diff->format('%H:%I:%S');
                    }
                    $this->attendance->lunch_out = $this->date;
                    $this->attendance->lunch_fault = $diff;
                    $this->attendance->save();
                    return;
                }
                if (!empty($this->break) && $this->break->break_out == null){
                    $diffBreak = Carbon::createFromFormat('Y-m-d H:i:s',$this->break->break_in,'America/New_York');
                    $diffBreak = $diffBreak->diff($this->date);
                    if ($diffBreak->h > 0 && $diffBreak->i >= 15 ){
                        $diffBreak = Carbon::createFromFormat('H:i:s',$diffBreak->format('%H:%I:%S'),'America/New_York');
                        $diffBreak->subMinutes(15);
                        $diffBreak = $diffBreak->format('%H:%I:%S');
                        $this->break->time_fault = $diffBreak;
                    }
                    $this->break->time_break = $diffBreak->format('%H:%I:%S');
                    $this->break->break_out = $this->date;
                    $this->break->save();
                    $this->attendance->break_count = $this->attendance->break_count + 1;
                    $this->attendance->save();
                    return;
                }
                break;
            case 'out':
                $this->attendance->check_out = $this->date;
                break;
            case 'break':
                $this->break->tlg_id = $this->employe->tlg_id;
                $this->break->break_in = $this->date;
                $this->break->save();
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

    public function register($command,$param):void
    {
        $this->command = $command;
        $this->param = $param;

        $this->search();
        if(empty($this->employe->id)){
            $this->messages->send('Try /register first');
        }else{
            switch ($this->command){
                default:
                    $this->messages->send('Invalid command');
                    break;
                case 'in':
                    $schedule = Schedules::getSchedule($this->employe, $this->date->dayOfWeekIso);
                    if ($schedule) {
                        if (!empty($this->attendance->id)) {
                            $this->messages->send('You are already checked in');
                        } else {
                            if ($this->attendance->getCurrentDate($this->employe->tlg_id, $this->date->format('Y-m-d', 'America/New_York'))) {
                                $this->messages->send('You are already checked in today');
                                return;
                            }
                            if (strlen($this->param) > 0) {
                                $arrParam = str_split($param);
                                $arrParam = array_filter($arrParam, function ($value) {
                                    return $value != '(' && $value != ')';
                                });
                                $this->param = implode('', $arrParam);
                                $newDate = Carbon::create($this->date->format('Y-m-d') . ' ' . $this->param, 'America/New_York');
                            }
                            $this->create();

                            if (strlen($this->param) > 0 && $newDate->format('H:i:s', 'America/New_York') > $schedule->start && $schedule->special_schedule == 0) {
                                $diff = $newDate->diff($schedule->start);
                                if ($diff->h >= 8) {
                                    $diff = Carbon::createFromFormat('H:i:s', '08:00:00', 'America/New_York');
                                }
                                $this->messages->send('You are late');
                            } else if ($this->date->format('H:i:s', 'America/New_York') > $schedule->start && $schedule->special_schedule == 0) {
                                $diff = $this->date->diff($schedule->start);
                                if ($diff->h >= 8) {
                                    $diff = Carbon::createFromFormat('H:i:s', '08:00:00', 'America/New_York');
                                }
                                $this->messages->send('You are late');
                            }
                            if ($schedule->special_schedule == 0){
                                try {
                                    $this->absencesTime->create($this->employe, $diff->format('%H:%I:%S'), 'late', $this->date);
                                } catch (\Exception $e) {
                                    Log::channel('attendance')->error('error create ' . $e->getMessage());
                                }
                            }

                            $this->messages->send('Check in at ' . $this->date->format('H:i:s', 'America/New_York'));
                        }
                    } else {
                        $this->messages->send('You dont have schedule today');
                    }
                break;
                case 'lunch':
                    if (empty($this->attendance->check_in)){
                        $this->messages->send('You are not checked in');
                    }else{
                        if (!empty($this->attendance->lunch_in)){
                            $this->messages->send('You are already on lunch');
                        }else{
                            $this->create();
                            $this->messages->send('Lunch at ' . $this->date->format('H:i:s', 'America/New_York'));
                        }
                    }
                break;
                case 'back':
                    if (empty($this->attendance->check_in)){
                        $this->messages->send('You are not checked in');
                    }else{
                        if(empty($this->break->break_in) && empty($this->attendance->lunch_in)){
                            $this->messages->send('You are not on break');
                        }else if (empty($this->attendance->lunch_in) && empty($this->break->break_in)){
                            $this->messages->send('You are not on lunch');
                        }else{
                            if ($this->attendance->lunch_out != null && $this->break->break_out != null){
                                $this->messages->send('You are already back');
                            }else{
                                $this->create();
                                $this->messages->send('Back at ' . $this->date->format('H:i:s', 'America/New_York'));
                            }
                        }
                    }
                break;
                case 'break':
                    $schedule = Schedules::getSchedule($this->employe, $this->date->dayOfWeekIso);

                    if($this->attendance->break_count >= 2 && $schedule->special_schedule == 0){
                        $this->messages->send('You have reached the maximum number of breaks');
                    }else {
                        if ($this->break->break_in && !$this->break->break_out){
                            $this->messages->send('You are already on break');
                        }else {
                            $this->create();
                            $this->messages->send('Break at ' . $this->date->format('H:i:s', 'America/New_York'));
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
                            if (isset($this->break) && $this->break->break_out == null && $this->break->break_in != null){
                                $this->messages->send('You need out from break first');
                            }else{
                                $this->create();
                                $schedule = Schedules::getSchedule($this->employe, $this->date->dayOfWeek);
                                if ($this->date->format('H:i:s', 'America/New_York') < $schedule->end && $schedule->special_schedule == 0){
                                    $diff = $this->date->diff($schedule->end)->format('%H:%I:%S');
                                    try {
                                        $this->absencesTime->create($this->employe,$diff,'early',$this->date);
                                    }catch (\Exception $e){
                                        Log::channel('attendance')->error('error create ' . $e->getMessage());
                                    }
                                    $this->messages->send('You are leaving early');
                                }
                                $this->messages->send('Check out at ' . $this->date->format('H:i:s', 'America/New_York'));
                            }
                        }
                    }
                break;
            }
        }
    }
}
