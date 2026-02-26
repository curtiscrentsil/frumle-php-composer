<?php

declare(strict_types=1);

namespace Frumle;

/**
 * CLI command handler for the Frumle PHP package.
 * Commands: add-key, login, status, analyze (default).
 */
class Cli
{
    private const VERSION = '0.1.0';

    public function run(array $argv): void
    {
        $args = array_slice($argv, 1);

        if (empty($args) || $args[0] === 'analyze' || $args[0][0] !== '-' && !in_array($args[0], ['add-key', 'login', 'status', 'help', '--help', '-h', '--version', '-v'], true)) {
            $this->cmdAnalyze($args);
            return;
        }

        $command = array_shift($args);

        switch ($command) {
            case 'add-key':
            case 'login':
                $this->cmdAddKey($args);
                break;
            case 'status':
                $this->cmdStatus();
                break;
            case '--version':
            case '-v':
                echo 'frumle ' . self::VERSION . PHP_EOL;
                break;
            case 'help':
            case '--help':
            case '-h':
                $this->showHelp();
                break;
            default:
                $this->error("Unknown command: {$command}");
                $this->showHelp();
                exit(1);
        }
    }

    private function cmdAddKey(array $args): void
    {
        if (empty($args)) {
            $this->error('Usage: frumle add-key <api-key>');
            exit(1);
        }

        $apiKey = trim($args[0]);
        if (strlen($apiKey) < 10) {
            $this->error('Invalid API key format');
            echo "   API key must be at least 10 characters long\n";
            exit(1);
        }

        echo "🔐 Verifying API key with server...\n";

        try {
            $api = new ApiClient();
            $status = $api->verifyApiKey($apiKey);
        } catch (\Throwable $e) {
            $this->error("Failed to add API key: {$e->getMessage()}");
            echo "\n💡 Make sure:\n";
            echo "   1. Your API key is correct\n";
            echo "   2. You registered at the Frumle dashboard\n";
            echo "   3. You have an internet connection\n";
            exit(1);
        }

        Config::setApiKey($apiKey);

        echo "\n✅ API key added successfully!\n";

        $quota = $status['quota'] ?? [];
        echo "\n📊 Quota: " . ($quota['analysesPerMonth'] ?? '?') . " analyses per month\n";
        echo "📈 Used: " . ($quota['used'] ?? '?') . " / " . ($quota['analysesPerMonth'] ?? '?') . "\n";
        echo "📉 Remaining: " . ($quota['remaining'] ?? '?') . "\n";
        echo "\n💡 You can now run: frumle\n";
    }

    private function cmdStatus(): void
    {
        try {
            $api = new ApiClient();
            $status = $api->checkStatus();
        } catch (\Throwable $e) {
            $this->error("Status check failed: {$e->getMessage()}");
            exit(1);
        }

        echo "\n📊 Account Status\n";
        echo str_repeat('━', 40) . "\n";
        echo "API Key: " . ($status['apiKey'] ?? '') . "...\n";

        $quota = $status['quota'] ?? [];
        echo "\nQuota:\n";
        echo "  Total: " . ($quota['analysesPerMonth'] ?? '?') . " analyses/month\n";
        echo "  Used: " . ($quota['used'] ?? '?') . "\n";
        echo "  Remaining: " . ($quota['remaining'] ?? '?') . "\n";

        $usage = $status['usage'] ?? [];
        echo "\nUsage:\n";
        echo "  Total Analyses: " . ($usage['totalAnalyses'] ?? '?') . "\n";
        if (!empty($usage['lastAnalysisAt'])) {
            echo "  Last Analysis: " . $usage['lastAnalysisAt'] . "\n";
        }
    }

    private function cmdAnalyze(array $args): void
    {
        // Strip 'analyze' if it's the first arg
        if (!empty($args) && $args[0] === 'analyze') {
            array_shift($args);
        }

        $directory = null;
        $projectName = null;
        $ignore = null;

        // Parse arguments
        for ($i = 0; $i < count($args); $i++) {
            if ($args[$i] === '--project-name' && isset($args[$i + 1])) {
                $projectName = $args[++$i];
            } elseif ($args[$i] === '--ignore' && isset($args[$i + 1])) {
                $ignore = $args[++$i];
            } elseif ($args[$i][0] !== '-' && $directory === null) {
                $directory = $args[$i];
            }
        }

        $directory = $directory ?? getcwd();
        $targetDir = realpath($directory);

        if ($targetDir === false || !is_dir($targetDir)) {
            $this->error("Directory \"{$directory}\" does not exist");
            exit(1);
        }

        $apiKey = Config::getApiKey();
        if ($apiKey === null) {
            $this->error('No API key found!');
            echo "\n💡 To get started:\n";
            echo "   1. Register at the Frumle dashboard\n";
            echo "   2. Add your API key: frumle add-key <your-api-key>\n";
            echo "   3. Or use: frumle login <your-api-key>\n";
            exit(1);
        }

        echo "🚀 Starting codebase analysis...\n";
        echo "📁 Directory: {$targetDir}\n\n";

        // Scan files
        echo "📂 Scanning files...\n";

        $ignoreDirs = $ignore !== null
            ? array_map('trim', explode(',', $ignore))
            : ['vendor', 'node_modules', '.git', 'storage', 'cache', 'var', 'tmp', 'temp', 'logs', 'dist', 'build', 'runtime', 'assets'];

        $fileExtensions = ['php', 'json', 'yaml', 'yml', 'xml', 'md', 'env', 'neon', 'ini', 'twig'];

        $files = Scanner::readCodebase($targetDir, $ignoreDirs, $fileExtensions);

        if (empty($files)) {
            $this->error('No files found to analyze');
            exit(1);
        }

        echo "✅ Found " . count($files) . " files\n\n";

        // Determine project name
        if ($projectName === null) {
            $composerPath = $targetDir . DIRECTORY_SEPARATOR . 'composer.json';
            if (file_exists($composerPath)) {
                $composer = json_decode(file_get_contents($composerPath) ?: '', true);
                if (is_array($composer) && !empty($composer['name'])) {
                    $projectName = $composer['name'];
                }
            }
            if ($projectName === null) {
                $projectName = basename($targetDir) ?: 'unknown-project';
            }
        }
        $projectName = trim($projectName) ?: 'unknown-project';

        // Detect base URLs
        echo "🔍 Detecting base URLs...\n";
        $baseUrls = FrumleConfig::initialize($targetDir);

        if (!empty($baseUrls)) {
            foreach ($baseUrls as $entry) {
                if (($entry['environment'] ?? '') === 'local' && !empty($entry['url'])) {
                    echo "✅ Local URL detected: {$entry['url']}\n";
                }
            }
            $hasProd = false;
            foreach ($baseUrls as $entry) {
                if (($entry['environment'] ?? '') === 'production' && !empty($entry['url'])) {
                    $hasProd = true;
                }
            }
            if (!$hasProd) {
                echo "💡 Tip: Add production URL in frumle.config.json to test APIs in production\n";
            }
        }

        echo "📦 Project: {$projectName}\n";
        echo "🤖 Analyzing with AI...\n";

        try {
            $api = new ApiClient();
            $response = $api->analyzeCodebase([
                'files'          => array_map(fn(array $f) => [
                    'path'         => $f['path'],
                    'relativePath' => $f['relativePath'],
                    'content'      => $f['content'],
                ], $files),
                'directory'      => $targetDir,
                'projectName'    => $projectName,
                'ignoreDirs'     => $ignoreDirs,
                'fileExtensions' => $fileExtensions,
                'baseUrls'       => !empty($baseUrls) ? $baseUrls : null,
            ]);
        } catch (\Throwable $e) {
            $this->error("Analysis error: {$e->getMessage()}");
            $msg = $e->getMessage();
            if (str_contains($msg, '401') || str_contains($msg, 'Unauthorized')) {
                echo "\n💡 Try: frumle add-key <your-api-key>\n";
            } elseif (str_contains($msg, '429') || str_contains($msg, 'Quota')) {
                echo "\n💡 Check quota: frumle status\n";
            }
            exit(1);
        }

        $status = $response['status'] ?? null;
        $result = $response['result'] ?? null;

        if ($status === 'processing' || $result === null) {
            echo "\n" . str_repeat('=', 60) . "\n";
            echo "✅ ANALYSIS STARTED\n";
            echo str_repeat('=', 60) . "\n";
            echo "\n📁 Directory: {$targetDir}\n";
            echo "📄 Files queued: " . ($response['fileCount'] ?? count($files)) . "\n";

            if (isset($response['quota'])) {
                echo "\n📊 Quota:\n";
                echo "   - Remaining: " . ($response['quota']['remaining'] ?? '?') . " analyses\n";
            }

            echo "\n🔄 Analysis in progress...\n";
            echo "📝 Your documentation will be available in your dashboard shortly.\n";
            echo "🌐 Check your dashboard at: https://frumle.tellecata.com\n";
            echo str_repeat('─', 60) . "\n";
            return;
        }

        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 ANALYSIS RESULTS\n";
        echo str_repeat('=', 60) . "\n";

        if (!empty($result['framework'])) {
            echo "\n🎯 Framework: {$result['framework']}\n";
        }

        $stats = $result['stats'] ?? [];
        echo "\n📈 Statistics:\n";
        echo "   - Total Files: " . ($stats['totalFiles'] ?? '?') . "\n";
        echo "   - Analyzed: " . ($stats['analyzedFiles'] ?? '?') . "\n";
        echo "   - Total Chunks: " . ($stats['totalChunks'] ?? '?') . "\n";

        if (isset($response['quota'])) {
            echo "\n📊 Quota:\n";
            echo "   - Remaining: " . ($response['quota']['remaining'] ?? '?') . " analyses\n";
        }

        echo "\n📝 Summary:\n";
        echo str_repeat('-', 60) . "\n";
        echo ($result['summary'] ?? '') . "\n";
        echo str_repeat('-', 60) . "\n";
        echo "\n✅ Analysis complete! Results saved to the dashboard.\n";
    }

    private function showHelp(): void
    {
        echo <<<HELP

frumle - AI-powered codebase analyzer for PHP

Usage:
  frumle [directory]                  Analyze a codebase (default: current directory)
  frumle analyze [directory]          Same as above
  frumle add-key <api-key>            Add your API key
  frumle login <api-key>              Login with API key (alias for add-key)
  frumle status                       Check API key status and quota

Options:
  --project-name <name>               Project name (defaults to composer.json name or directory)
  --ignore <dirs>                     Comma-separated directories to ignore
  --version, -v                       Show version
  --help, -h                          Show this help

Supported PHP Frameworks:
  Laravel, Symfony, CodeIgniter 4+, CakePHP, Yii, Laminas, Slim, Phalcon

Examples:
  frumle                              Analyze current directory
  frumle ./src                        Analyze src directory
  frumle --project-name my-api        Analyze with custom project name
  frumle --ignore tests,storage       Ignore tests and storage directories

HELP;
    }

    private function error(string $message): void
    {
        echo "\n❌ {$message}\n";
    }
}
