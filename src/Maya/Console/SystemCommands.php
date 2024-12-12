<?php

use Maya\Console\Nova;
use Maya\Console\ArgHandler;
use Maya\Console\Iris;
use Maya\Database\Migrations\DBBuilder;
use Maya\Disk\Directory;
use Maya\Disk\File;
use Maya\Support\Str;

Nova::command('serve', function (ArgHandler $request, Iris $iris) {
    $defaultPort = 7002;
    $port = $request->option('port') ?? $defaultPort;
    $maxRetries = 10;

    if (!is_numeric($port) || $port < 1024 || $port > 65535) {
        $iris->error("The port number should be between 1024 and 65535.");
        return;
    }

    function findAvailablePort($startPort, $maxRetries)
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $portToCheck = $startPort + $i;
            if (!isPortOpen('127.0.0.1', $portToCheck, 1)) {
                return $portToCheck;
            }
        }
        return null;
    }

    if (isPortOpen('127.0.0.1', $port, 1)) {
        $iris->warning("Port {$port} is already in use.");
        $port = findAvailablePort($port + 1, $maxRetries);

        if ($port === null) {
            $iris->error("No available ports found in the range after {$maxRetries} attempts.");
            return;
        }

        $iris->info("Using alternative port {$port}.");
    }

    $iris->info("Starting server on port {$port}...");
    try {
        exec('php -S 127.0.0.1:' . $port . ' -t public');
    } catch (\Exception $e) {
        $iris->error("Failed to start the server: " . $e->getMessage());
    }
})->description('Serve the application locally.')
    ->alias(['s', 'server']);



Nova::command('key:generate', function (ArgHandler $request, Iris $iris) {
    if (file_exists('.env')) {
        $key = bin2hex(random_bytes(16));
        file_put_contents('.env', preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . $key, file_get_contents('.env')));
    }
})->description('Generate new app key');




Nova::command('make:migration {name}', function (ArgHandler $request, Iris $iris) {
    $tableName = $request->hasOption('table') ? $request->option('table') : $request->name;

    $stubPath = mayaPath('stubs/createMigration.stub');
    $stubContent = File::read($stubPath);

    $migrationContent = Str::of($stubContent)
        ->replace('{tableName}', $tableName)
        ->get();

    $currentDate = now()->format('ymd');
    $dbPath = appPath('DB/');

    $files = Directory::read($dbPath);
    $currentCounter = -1;

    foreach ($files as $file) {
        if (Str::of($file)->startsWith($currentDate . '_')) {
            $parts = explode('_', $file);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $currentCounter = max($currentCounter, (int)$parts[1]);
            }
        }
    }

    $nextCounter = $currentCounter + 1;

    $formattedCounter = str_pad($nextCounter, 6, '0', STR_PAD_LEFT);
    $fileName = "{$currentDate}_{$formattedCounter}_{$request->name}.php";
    $filePath = "{$dbPath}{$fileName}";

    File::write($filePath, $migrationContent);
})->description('Create New Migration');

Nova::command('migrate', function (ArgHandler $request, Iris $iris) {
    try {
        (new DBBuilder)->up($request->option('database') ?? 'default');
        $iris->success('Run Migrations Successfuly');
    } catch (Exception $e) {
        $iris->error('Migrations', $e->getMessage());
    }
})->description('Run Migrations');

Nova::command('migrate:rollback', function (ArgHandler $request, Iris $iris) {
    try {
        $step = $request->hasOption('step') && is_numeric($request->option('step')) ? intval($request->option('step')) : 1;
        (new DBBuilder)->down($request->option('database') ?? 'default', $step);
        $iris->success('Rollback Successfuly');
    } catch (Exception $e) {
        $iris->error('Migrations', $e->getMessage());
    }
})->description('Rollback Migrations');

Nova::command('migrate:reset', function (ArgHandler $request, Iris $iris) {
    try {
        (new DBBuilder)->down($request->option('database') ?? 'default', null);
        $iris->success('Rollback Successfuly');
    } catch (Exception $e) {
        $iris->error('Migrations', $e->getMessage());
    }
})->description('Rollback All Migrations');

Nova::command('migrate:refresh', function (ArgHandler $request, Iris $iris) {
    try {
        (new DBBuilder)->down($request->option('database') ?? 'default', null);
        (new DBBuilder)->up($request->option('database') ?? 'default');
        $iris->success('Refresh Migrations Successfuly');
    } catch (Exception $e) {
        $iris->error('Migrations', $e->getMessage());
    }
})->description('Rollback All Migrations and Migrate Again');

Nova::command('help', function () {
    Nova::showHelp();
})->description('helps');
