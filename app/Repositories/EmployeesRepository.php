<?php

namespace App\Repositories;

use App\Interfaces\BaseRepository;
use App\Models\Employees;
use App\Services\Messages;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class EmployeesRepository implements BaseRepository
{
    public Request $request;
    protected Employees $employe;
    protected Messages $messages;
    protected Carbon $date;
    public function __construct(Request $request, Employees $employe, Messages $messages, Carbon $carbon)
    {
        $this->date = $carbon->createFromTimestamp($request['message']['date'],'America/New_York');
        $this->request = $request;
        $this->employe = $employe;
        $this->messages = $messages;
    }

    public function search(): void
    {
        $employe = $this->employe->getEmploye($this->request['message']['from']['id']);
        if ($employe)
            $this->employe = $employe;
    }

    public function create(): void
    {
        $this->employe->tlg_id = $this->request['message']['from']['id'];
        $this->employe->name = $this->request['message']['from']['first_name'];
        $this->employe->username = $this->request['message']['from']['username'] ??  $this->request['message']['from']['first_name'];
        try {
            $this->employe->save();
        }catch (\Exception $e){
            Log::channel('attendance')->error('error create ' . $e->getMessage());
        }
    }

    public function register(): void
    {
        $this->search();
        if(empty($this->employe->id)){
            try {
                $this->create();
                $this->messages->send('Welcome ' . $this->employe->username);
            }catch (\Exception $e){
                Log::channel('attendance')->error('error register' . $e->getMessage());
            }
        }else{
            $this->messages->send('You are already registered');
        }
    }
}
