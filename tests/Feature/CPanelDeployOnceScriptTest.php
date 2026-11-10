<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class CPanelDeployOnceScriptTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = sys_get_temp_dir().'/accounting-deploy-gate-'.bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryDirectory);

        parent::tearDown();
    }

    public function test_it_waits_once_for_each_new_main_commit_and_skips_repeated_invocations(): void
    {
        $repository = $this->temporaryDirectory.'/repository';
        $bin = $this->temporaryDirectory.'/bin';
        $state = $this->temporaryDirectory.'/state';
        $sleepLog = $this->temporaryDirectory.'/sleep.log';

        mkdir($repository, 0777, true);
        mkdir($bin, 0777, true);
        file_put_contents($repository.'/current-sha', "commit-one\n");

        $this->writeExecutable($bin.'/git', <<<'SH'
#!/bin/sh
if [ "$1" = "-C" ] && [ "$3" = "rev-parse" ]; then
    cat "$2/current-sha"
    exit 0
fi
exit 1
SH);

        $this->writeExecutable($bin.'/sleep', <<<'SH'
#!/bin/sh
printf '%s\n' "$1" >> "$ACCOUNTING_TEST_SLEEP_LOG"
SH);

        $environment = [
            'PATH' => $bin.PATH_SEPARATOR.getenv('PATH'),
            'ACCOUNTING_REPOSITORY_PATH' => $repository,
            'ACCOUNTING_DEPLOY_STATE_DIR' => $state,
            'ACCOUNTING_DEPLOY_DELAY_SECONDS' => '60',
            'ACCOUNTING_DEPLOY_DRY_RUN' => '1',
            'ACCOUNTING_TEST_SLEEP_LOG' => $sleepLog,
        ];

        $firstRun = $this->runDeployScript($environment);

        $this->assertSame(0, $firstRun->getExitCode(), $firstRun->getErrorOutput());
        $this->assertStringContainsString('DRY RUN: deployment would run once for commit-one', $firstRun->getOutput());
        $this->assertSame("60\n", file_get_contents($sleepLog));
        $this->assertSame("commit-one\n", file_get_contents($state.'/last-attempted-main-sha'));

        $secondRun = $this->runDeployScript($environment);

        $this->assertSame(0, $secondRun->getExitCode(), $secondRun->getErrorOutput());
        $this->assertStringContainsString('already attempted', $secondRun->getOutput());
        $this->assertSame("60\n", file_get_contents($sleepLog));

        file_put_contents($repository.'/current-sha', "commit-two\n");
        $thirdRun = $this->runDeployScript($environment);

        $this->assertSame(0, $thirdRun->getExitCode(), $thirdRun->getErrorOutput());
        $this->assertStringContainsString('DRY RUN: deployment would run once for commit-two', $thirdRun->getOutput());
        $this->assertSame("60\n60\n", file_get_contents($sleepLog));
        $this->assertSame("commit-two\n", file_get_contents($state.'/last-attempted-main-sha'));
    }

    private function runDeployScript(array $environment): Process
    {
        $process = new Process(
            ['/bin/bash', base_path('scripts/cpanel-deploy-once.sh')],
            base_path(),
            $environment
        );
        $process->run();

        return $process;
    }

    private function writeExecutable(string $path, string $contents): void
    {
        file_put_contents($path, $contents."\n");
        chmod($path, 0755);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
