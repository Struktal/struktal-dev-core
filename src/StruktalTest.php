<?php

namespace struktal\devcore;

class StruktalTest {
    public static function test(): void {
        echo "<info>🧪 [STRUKTAL] Running tests...</info>";

        $inGithubActions = getenv("GITHUB_ACTIONS") === "true";

        // Buildx setup
        echo "<info>🔨 [STRUKTAL] Setting up buildx builder...</info>";
        $buildxBuilderName = $inGithubActions ? "gha-builder" : "struktal-builder";
        $commands = [
            "docker buildx create --name " . $buildxBuilderName . " --driver docker-container --use || docker buildx use " . $buildxBuilderName,
            "docker buildx inspect --bootstrap"
        ];
        foreach ($commands as $command) {
            if (!self::execute($command)) {
                echo "<error>❌ [STRUKTAL] Failed to set up buildx builder</error>";
                exit(1);
            }
        }

        // Build test image
        echo "<info>⏳ [STRUKTAL] Building test image...</info>";
        $buildCommand = "docker buildx build --tag struktaltest:latest --load";
        if ($inGithubActions) {
            $buildCommand .= " --cache-from type=gha,scope=test --cache-to type=gha,scope=test,mode=max --build-arg BUILDKIT_INLINE_CACHE=1";
        }
        if (!self::execute($buildCommand)) {
            echo "<error>❌ [STRUKTAL] Failed to build test image</error>";
            exit(1);
        }

        // Start test container
        echo "<info>⏳ [STRUKTAL] Starting test container...</info>";
        $runCommand = "docker compose -f docker-compose-test.yml up -d --wait";
        if (!self::execute($runCommand)) {
            echo "<error>❌ [STRUKTAL] Failed to start test container</error>";
            exit(1);
        }

        // Run tests
        echo "<info>🧪 [STRUKTAL] Starting tests...</info>";
        $pathToTestCommand = [ ".", "vendor", "bin", "pest" ];
        $testCommand = implode(DIRECTORY_SEPARATOR, $pathToTestCommand);
        $testsSuccessful = true;
        if (!self::execute($testCommand)) {
            echo "<error>❌ [STRUKTAL] Failed to run tests or they completed with an error</error>";
            $testsSuccessful = false;
        }

        // Stop test container
        echo "<info>⏳ [STRUKTAL] Stopping test container...</info>";
        $stopCommand = "docker compose -f docker-compose-test.yml down";
        if (!self::execute($stopCommand)) {
            echo "<error>❌ [STRUKTAL] Failed to stop test container</error>";
            exit(1);
        }

        if ($testsSuccessful) {
            echo "<info>✅ [STRUKTAL] Tests were executed successfully</info>";
        }
    }

    private static function execute(string $command): bool {
        echo "<comment>🤖 [STRUKTAL] $command</comment>";
        passthru($command, $exitCode);
        if ($exitCode !== 0) {
            echo "<error>❌ [STRUKTAL] Command failed with exit code $exitCode: $command</error>";
            return false;
        }

        return true;
    }
}
