<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employees;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeesRepository;
use App\Services\Messages;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    protected array $commands = ['/in', '/out','/break','/back','/register'];
    protected Messages $messages;
    public array $update;
    protected EmployeesRepository $employeesRepository;
    protected AttendanceRepository $attendanceRepository;
    public function __construct(Messages $messages, Request $request, EmployeesRepository $employeesRepository, AttendanceRepository $attendanceRepository)
    {
        $this->messages = $messages;
        $this->update = json_decode($request->getContent(), true);
        $this->employeesRepository = $employeesRepository;
        $this->attendanceRepository = $attendanceRepository;
    }
    public function webhook(): void
    {
        $this->proceedCommand();
    }
    private function Register(): void
    {
        $this->employeesRepository->register();
    }
    private function attendanceProcess(): void
    {
        $this->attendanceRepository->register();
    }

    private function proceedCommand(): void
    {
        if (isset($this->update['message']['text'])) {
            $command = substr($this->update['message']['text'],1);
            if($command == 'register'){
                $this->Register();
            }else if(in_array('/'.$command, $this->commands) && $command != 'register'){
                $this->attendanceProcess();
            }else{
                $this->messages->send('Unknown command');
            }
        }
    }
}
