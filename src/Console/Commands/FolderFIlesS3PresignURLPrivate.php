<?php

namespace Omnics\FileManagement\Console\Commands;

use Omnics\FileManagement\Http\Controllers\FileManager\General\S3FileManagementController;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FolderFilesS3PresignURLPrivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 's3:pre-sign-folder-files';

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
    public function handle():int
    {
        $date = Carbon::now()->format('Y-m-d H:i:s');
        $file_management_controller = new S3FileManagementController();
        $tables = [
            'files'
        ];
        foreach ($tables as $table) {
            if ($table === 'files') {
                $column = 'path';
            }

            $records = DB::table($table)->whereNotNull($column)->get();
            foreach ($records as $record) {
                $final = $record->{$column};
                $url = $file_management_controller->getNewPresignedURLForExistingFiles($final);
                DB::table($table)->where('id', $record->id)->update([
                    $column => $url,
                    'updated_at' => $date
                ]);
            }
        }
        return 1;
    } // End Function
}
