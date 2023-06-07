<?php

namespace App\Console\Commands;

use App\Models\Absences;
use App\Models\Attendance;
use App\Models\Employees;
use App\Models\JustifyAbsences;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckAbsences extends Command
{
    protected $signature = 'check:absences';
    protected $description = 'Check daily absences ';

    public function handle():int
    {
        $attendance = Attendance::getAllCheckIn();
        $tlg_ids = [];
        foreach ($attendance as $item){
            $tlg_ids[] = $item->tlg_id;
        }
//      add to absences table where tlg_id not in $attendance
        $absences = Employees::whereNotIn('tlg_id',$tlg_ids)->get();
        $justifyAbsences = JustifyAbsences::whereDate('date', now('America/New_York'))->get();
        //insert into absences table
        foreach ($absences as $absence){
            $modelAbsences = new Absences();
            foreach ($justifyAbsences as $justifyAbsence){
                if ($justifyAbsence->tlg_id == $absence->tlg_id){
                    $modelAbsences->justify_id = $justifyAbsence->id;
                }
            }
            $modelAbsences->tlg_id = $absence->tlg_id;
            $modelAbsences->date = now('America/New_York');
            try {
                $modelAbsences->save();
            }catch (\Exception $exception){
                Log::channel('abcences')->error('error save ' . $exception->getMessage());
            }
        }
        return Command::SUCCESS;
    }
}
