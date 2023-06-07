<?php

namespace App\Http\Controllers;

use App\Repositories\AbsencesRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeesRepository;
use App\Services\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    protected array $DefCommands = ['in', 'out','break','back','register','absence'];
    protected string $command;
    protected Messages $messages;
    public array $update;
    protected EmployeesRepository $employeesRepository;
    protected AttendanceRepository $attendanceRepository;
    protected mixed $params;
    protected AbsencesRepository $absenceRepository;

    public function __construct(Messages $messages, Request $request, EmployeesRepository $employeesRepository, AttendanceRepository $attendanceRepository, AbsencesRepository $absencesRepository)
    {
        $this->messages = $messages;
        $this->update = json_decode($request->getContent(), true);
        $this->employeesRepository = $employeesRepository;
        $this->attendanceRepository = $attendanceRepository;
        $this->absenceRepository = $absencesRepository;
    }
    public function webhook(): void
    {
        $this->proceedCommand();
    }
    private function Register(): void
    {
        $this->employeesRepository->register(null,null);
    }

    private function absenceProcess(): void
    {
        $this->absenceRepository->register(null,$this->params);
    }
    private function attendanceProcess(): void
    {
        $this->attendanceRepository->register($this->command, $this->params);
    }

    private function proceedCommand(): void
    {
        $this->getCommand();
        if (trim($this->command) == 'absence'){
            $this->absenceProcess();
        }else if($this->command == 'register'){
            $this->Register();
        }else if(in_array($this->command, $this->DefCommands)){
            $this->attendanceProcess();
        }else{
            if ($this->update['message']['chat']['type'] == 'private' )
                $this->messages->send('Unknown command');
        }
    }

    private function getCommand(): void
    {
        $arrWords = explode(' ', $this->update['message']['text']);
        if (count($arrWords) > 1){
            $this->params = implode(' ', array_slice($arrWords, 1));
        }else{
            $this->params = null;
        }

        if (isset($this->update['message']['text'])) {
            if (isset($this->update['message']['entities']) && $this->update['message']['entities'][0]['type'] != 'mention'){
                if (str_contains($this->update['message']['text'], '@')){
                    $this->command = trim(substr($this->update['message']['text'],1,strpos($this->update['message']['text'],'@')-1));
                }else{
                    $this->command = (substr($this->update['message']['text'],1));
                }
            }else if(in_array($this->update['message']['text'], $this->DefCommands)){
                $this->command = $this->update['message']['text'];
            }else if (count($arrWords) > 1 && in_array($arrWords[0], $this->DefCommands)){
                $this->command = $arrWords[0];
                $this->params = implode(' ', array_slice($arrWords, 1));
            }
        }
    }
}
