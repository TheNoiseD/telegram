<?php

namespace App\Repositories;

use App\Interfaces\BaseRepository;
use App\Models\Absences;
use App\Models\Employees;
use App\Models\JustifyAbsences;
use App\Services\Messages;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class AbsencesRepository implements BaseRepository
{

    private Messages $messages;
    private Carbon $carbon;
    private Request $request;
    private Employees $employe;
    private JustifyAbsences $justifyAbsences;
    private JustifyAbsences $absence;
    public array $params;
    public function __construct(Request $request, Employees $employe, Messages $messages, Carbon $carbon, JustifyAbsences $justifyAbsences)
    {
        $this->messages = $messages;
        $this->carbon = $carbon;
        $this->request = $request;
        $this->employe = $employe;
        $this->justifyAbsences = $justifyAbsences;
    }

    public function search(): void
    {
        $employe = $this->employe->getEmployeByUser(substr($this->params[0], 1));
        Log::channel('test')->info($employe);
        if ($employe)
            $this->employe = $employe;
        $absence = $this->justifyAbsences->getAbsenceToDay($this->employe);
        if ($absence)
            $this->absence = $absence;
    }

    public function create(): void
    {
        $this->justifyAbsences->tlg_id = $this->employe->tlg_id;
        $this->justifyAbsences->date = $this->carbon->now('America/New_York');
        $this->justifyAbsences->comment = $this->params[1];
        try {
            $this->justifyAbsences->save();
        }catch (\Exception $e){
            Log::channel('absences')->error('error create justify' . $e->getMessage());
        }
    }

    public function register($command,$param): void
    {
        if ($param){
            $this->params = explode('|', $param);
        }else{
            $this->messages->send('You must enter the username and the reason for the absence');
            exit();
        }

        $this->search();
        if (empty($this->employe->id)){
            $this->messages->send('User not found');
            return;
        }
        if (!empty($this->absence->id)){
            $this->messages->send('An absence has already been registered for @'.$this->employe->username.' today');
        }else{
            try {
                $this->create();
                $this->messages->send('An absence has been registered for @'.$this->employe->username);
            }catch (\Exception $e){
                Log::channel('absences')->error('error register justify' . $e->getMessage());
            }
        }
    }
}
