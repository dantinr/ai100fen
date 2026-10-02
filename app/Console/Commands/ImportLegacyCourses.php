<?php

namespace App\Console\Commands;

use App\Services\LegacyCourseImporter;
use Illuminate\Console\Command;

class ImportLegacyCourses extends Command
{
    protected $signature = 'courses:import-legacy {--dry-run : 仅检查计划，不写入数据库}';

    protected $description = '导入现有16门课程与已有课时，仅新增不存在的草稿，不覆盖人工编辑';

    public function handle(LegacyCourseImporter $importer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $report = $importer->run($dryRun);
        $this->table(['课程地址', '宪章类别', '操作', '新增课时'], $report['rows']);
        $this->info(($dryRun ? '预检：计划新增 ' : '完成：新增 ').$report['created'].' 门课程、'.$report['lessons'].' 个已有课时；保留 '.$report['skipped'].' 门同地址课程。');
        $this->line('课程均为草稿；仅网站首课已有完整正文，其余课时不自动补齐或发布。');

        return self::SUCCESS;
    }
}
