<?php
namespace App\Console\Commands;

use App\Imports\StopTimesImport;
use Illuminate\Console\Command;
use App\Imports\ShapesImport;

class ImportExcel extends Command
{
    protected $signature = 'import:excel';

    protected $description = 'Laravel Excel importer';

    public function handle()
    {
        $this->output->title('Starting import');
        (new StopTimesImport)->withOutput($this->output)->import('C:\Users\artis\Downloads\stop_times.csv');
        $this->output->success('Import successful');
    }
}
