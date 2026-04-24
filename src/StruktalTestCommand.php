<?php

namespace struktal\devcore;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StruktalTestCommand extends Command {
    public function configure(): void {
        $this
            ->setDescription("Runs the project's tests");
    }

    public function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln("<info>🧪 [STRUKTAL] Running tests...</info>");

        $inGithubActions = getenv("GITHUB_ACTIONS") === "true";

        // Buildx setup
        $output->writeln("<info>🔨 [STRUKTAL] Setting up buildx builder...</info>");
        $buildxBuilderName = $inGithubActions ? "gha-builder" : "struktal-builder";
        $commands = [
            "docker buildx create --name " . $buildxBuilderName . " --driver docker-container --use || docker buildx use " . $buildxBuilderName,
            "docker buildx inspect --bootstrap"
        ];
        foreach ($commands as $command) {
            if (!self::executeCommand($command, $output)) {
                $output->writeln("<error>❌ [STRUKTAL] Failed to set up buildx builder</error>");
                return 1;
            }
        }

        // Build test image
        $output->writeln("<info>⏳ [STRUKTAL] Building test image...</info>");
        $buildCommand = "docker buildx build --tag struktaltest:latest --load";
        if ($inGithubActions) {
            $buildCommand .= " --cache-from type=gha,scope=test --cache-to type=gha,scope=test,mode=max --build-arg BUILDKIT_INLINE_CACHE=1";
        }
        $buildCommand .= " .";
        if (!self::executeCommand($buildCommand, $output)) {
            $output->writeln("<error>❌ [STRUKTAL] Failed to build test image</error>");
            return 1;
        }

        // Start test container
        $output->writeln("<info>⏳ [STRUKTAL] Starting test container...</info>");
        $runCommand = "docker compose -f docker-compose-test.yml up -d --wait";
        if (!self::executeCommand($runCommand, $output)) {
            $output->writeln("<error>❌ [STRUKTAL] Failed to start test container</error>");
            return 1;
        }

        // Run tests
        $output->writeln("<info>🧪 [STRUKTAL] Starting tests...</info>");
        $pathToTestCommand = [ ".", "vendor", "bin", "pest" ];
        $testCommand = implode(DIRECTORY_SEPARATOR, $pathToTestCommand);
        $testsSuccessful = true;
        if (!self::executeCommand($testCommand, $output)) {
            $output->writeln("<error>❌ [STRUKTAL] Failed to run tests or they completed with an error</error>");
            $testsSuccessful = false;
        }

        // Stop test container
        $output->writeln("<info>⏳ [STRUKTAL] Stopping test container...</info>");
        $stopCommand = "docker compose -f docker-compose-test.yml down";
        if (!self::executeCommand($stopCommand, $output)) {
            $output->writeln("<error>❌ [STRUKTAL] Failed to stop test container</error>");
            return 1;
        }

        if ($testsSuccessful) {
            $output->writeln("<info>✅ [STRUKTAL] Tests were executed successfully</info>");
            return 0;
        }

        return 1;
    }

    private static function executeCommand(string $command, OutputInterface $output): bool {
        $output->writeln("<comment>🤖 [STRUKTAL] $command</comment>");
        passthru($command, $exitCode);
        if ($exitCode !== 0) {
            $output->writeln("<error>❌ [STRUKTAL] Command failed with exit code $exitCode: $command</error>");
            return false;
        }

        return true;
    }
}
