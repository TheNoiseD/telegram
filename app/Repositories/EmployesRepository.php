<?php

namespace App\Repositories;

use App\Models\Employees;
use App\Services\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class EmployesRepository
{
    public Request $request;
    private Employees $employe;
    private Messages $messages;
    public function __construct(Request $request, Employees $employe, Messages $messages)
    {
        $this->request = $request;
        $this->employe = $employe;
        $this->messages = $messages;
    }

    public function search(): void
    {
        if ($this->employe->where('tlg_id', $this->request['message']['from']['id'])->first())
            $this->employe = $this->employe->where('tlg_id', $this->request['message']['from']['id'])->first();
    }

    public function create(): void
    {
        $this->employe->tlg_id = $this->request['message']['from']['id'];
        $this->employe->name = $this->request['message']['from']['first_name'];
        $this->employe->username = $this->request['message']['from']['username'] ??  $this->request['message']['from']['first_name'];
        $this->employe->save();
    }

    public function register(): void
    {
        $this->search();
        if(empty($this->employe->id)){
            try {
                $this->create();
                $this->messages->send('Welcome ' . $this->employe->username);
            }catch (\Exception $e){
                Log::error('error register' . $e->getMessage());
            }
        }else{
            $this->messages->send('You are already registered');
        }
    }

    public function in() :void
    {
        $this->search();
        if (empty($this->employe->id)) {
            Log::warning('Employe not found');
        }else{
            Log::warning('Employe found');
        }

    }



}
