<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reporting uploads go to a throwaway folder, not storage/app/reporting/uploads:
        // the real one holds the files behind the Download Reports ZIP.
        $dir = storage_path('framework/testing/reporting-uploads');
        File::deleteDirectory($dir);
        config(['reporting.uploads_dir' => $dir]);
    }
}
