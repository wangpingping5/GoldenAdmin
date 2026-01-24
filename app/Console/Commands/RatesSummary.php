<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use \App\Models\User;
use \App\Models\CategorySummary;

class RatesSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rate:summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    protected $reason_f = '[%s] 요율차이(%.2f)에 의한 자동정산';
    public function handle()
    {
        $this->info('Rates summary');
        $coMaster = User::where('username', User::APIGROUP)->first();
        if (!$coMaster)
        {
            $this->error('coMaster does not exist');
        }
        $admin = User::where('role_id', User::ROLE_ADMIN)->first();
        $this->calcRateDiff($admin, $coMaster);
        $this->info('Rates end');
    }
    public function calcRateDiff($parent, $op)
    {
        $rates = $op->rates();
        if (($rates['slot']>0 && $rates['live']>0) && ($rates['slot'] != $rates['live']))
        {
            $fixType = ($rates['slot'] > $rates['live'])?'slot':'live';
            $fixRate = abs($rates['slot'] - $rates['live']);
            $catSumms = CategorySummary::where('user_id', $op->id)->where('date', date('Y-m-d', strtotime('-1 days')))->get();
            $fixTotal = 0;
            foreach ($catSumms as $cs)
            {
                if ($cs->category->type == $fixType)
                {
                    $fixTotal = $fixTotal + ($cs->totalbet - $cs->totalwin);
                }
            }
            if ($fixTotal != 0)
            {
                $outbalance = intval($fixTotal * $fixRate / 100);
                if ($outbalance > 0 && $op->balance < $outbalance )
                {
                    $outbalance = $op->balance;
                }
                if ($outbalance > 0)
                {
                    $reason_str = sprintf($this->reason_f, __($fixType), $fixRate);
                    $op->addBalance('out', $outbalance, $parent, null, $reason_str);
                }
                else
                {
                    $reason_str = sprintf($this->reason_f, __($fixType), $fixRate);
                    $op->addBalance('add', abs($outbalance), $parent, null, $reason_str);
                }
            }

        }
        if ($op->role_id > User::ROLE_OPERATOR)
        {
            if ($op->role_id < User::ROLE_GROUP)
            {
                $parent = $op;
            }
            $childs = $op->childs;
            foreach ($childs as $c)
            {
                $this->calcRateDiff($parent, $c);
            }
        }
    }
}
