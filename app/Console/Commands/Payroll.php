<?php

namespace App\Console\Commands;

use App\Exports\PayrollExport;
use App\Models\Attendance;
use App\Models\Employees;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Command\Command as CommandAlias;

class Payroll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create payroll for employees';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
//        $endPayroll = Carbon::now('America/New_York')->endOfDay();
        $endPayroll = Carbon::create(2023,06,14,10,00,00,'America/New_York')->endOfDay();
        $startPayroll = $endPayroll->copy()->subDays(15)->startOfDay();
        $employees = Employees::all();
        $relation = (array)[];
        $relation[] = [
            'Employee'=> 'Employee',
            'Days Worked'=> 'Days Worked',
            'Worked Hours'=> 'Worked Hours',
            'Hourly Cost'=> 'Hourly Cost',
            'Pay Cut'=> 'Pay Cut',
            'Total Pay'=> 'Total Pay',
        ];
        foreach ($employees as $employee){
            if ($employee->attendance->whereBetween('check_in',[$startPayroll,$endPayroll])->count() != 0) {
                $attendances = $employee->attendance->whereBetween('check_in', [$startPayroll, $endPayroll]);
                $daysWorked = count($attendances) + count($employee->absence);
                $payCut = $employee->salary->salary ? $employee->salary->salary / 2 : 0;
                $hourlyCost = round(($payCut / $daysWorked) / 8, 2);
                $workedHoursR = '1-1-1 00:00:00';
                $absencesHoursR = '1-1-1 00:00:00';
                $breaks = $employee->breakTime->whereBetween('break_in', [$startPayroll, $endPayroll]);
                if($employee->roles->eSchedule->first()->special_schedule == 1){
                    $breaksAbsences = '00:00:00';
                   foreach ($breaks as $break){
                       $breakTime = Carbon::parse($break->time_break);
                       $breaksAbsences = Carbon::parse($breaksAbsences)->addHours($breakTime->format('H'))->addMinutes($breakTime->format('i'))->addSeconds($breakTime->format('s'));
                   }
                }else{
                    $breaksAbsences = '00:00:00';
                    foreach ($breaks as $break){
                        $breakTime = Carbon::parse($break->break_fault);
                        $breaksAbsences = Carbon::parse($breaksAbsences)->addHours($breakTime->format('H'))->addMinutes($breakTime->format('i'))->addSeconds($breakTime->format('s'));
                    }
                }

                foreach ($attendances as $attendance) {
                    $checkIn = Carbon::parse($attendance->check_in);
                    $checkOut = Carbon::parse($attendance->check_out);
                    $lunchIn = Carbon::parse($attendance->lunch_in);
                    $lunchOut = Carbon::parse($attendance->lunch_out);
                    $lunchFault = Carbon::parse($attendance->lunch_fault);

                    $workedHours = $checkIn->diff($checkOut);
                    $absencesHours = $lunchIn->diff($lunchOut);
                    $absencesHoursR = Carbon::parse($absencesHours->format('%H:%I:%S'))
                        ->addHours($lunchFault->format('H'))
                        ->addMinutes($lunchFault->format('i'))
                        ->addSeconds($lunchFault->format('s'));

                    $workedHours = $absencesHoursR->diff(Carbon::parse($workedHours->format('%H:%I:%S')));

                    $workedHoursR = Carbon::parse($workedHoursR)->addHours($workedHours->h)->addMinutes($workedHours->i)->addSeconds($workedHours->s);
                }
                $workedHoursR = $workedHoursR->addHours($breaksAbsences->format('H'))->addMinutes($breaksAbsences->format('i'))->addSeconds($breaksAbsences->format('s'));
                $workedHoursR = $workedHoursR->diff(Carbon::parse('1-1-1 00:00:00'));
                $wh = round(($workedHoursR->d * 24) + $workedHoursR->h + ($workedHoursR->i / 60), 2);

                $relation[] = [
                    'employee' => $employee->name,
                    'daysWorked' => $daysWorked,
                    'workedHours' => $wh,
                    'hourlyCost' => $hourlyCost,
                    'payCut' => $payCut,
                    'totalPay' => round($wh * $hourlyCost, 2)
                ];
            }else{
                $payCut = round($employee->salary->salary/2,2);
                $relation[] = [
                    'employee' => $employee->name,
                    'daysWorked' => 0,
                    'workedHours' => 0,
                    'hourlyCost' => 0,
                    'payCut' => $payCut,
                    'totalPay' => 0
                ];
            }
        }
        $export = new PayrollExport($relation);

        $fileContents = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $filePath = storage_path('app/public/payroll_'.$endPayroll->format('Y-m-d').'_'.Carbon::now()->timestamp.'.xlsx');
        file_put_contents($filePath, $fileContents);

        $email = 'anthmon19@gmail.com';
        $subject = 'Archivo de nómina';
        $body = 'Adjuntamos el archivo de nómina.';

        Mail::raw($body, function ($message) use ($email, $subject, $filePath) {
            $message->to($email)
                ->subject($subject)
                ->attach($filePath);
        });

        $this->info('Payroll created successfully');
        return CommandAlias::SUCCESS;

    }
}
