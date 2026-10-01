<?php

namespace App\Console\Commands;

use App\Services\FreeLabInstaller;
use Illuminate\Console\Command;

class InstallFreeLab extends Command
{
    protected $signature = 'free-lab:install';

    protected $description = '新增首批免费任务，保留已存在的课程与学习数据';

    public function handle(FreeLabInstaller $installer): int
    {
        $this->info('新增 '.$installer->install().' 个免费完整任务；已存在的课程保持原样。');

        return self::SUCCESS;
    }
}
