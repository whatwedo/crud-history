<?php

declare(strict_types=1);

namespace whatwedo\CrudHistoryBundle\Tests\Helper;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Factory;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class DatabaseResetter
{
    /**
     * @var bool
     */
    private static $hasBeenReset = false;

    public static function hasBeenReset(): bool
    {
        if (isset($_SERVER['FOUNDRY_DISABLE_DATABASE_RESET'])) {
            return true;
        }

        return self::$hasBeenReset;
    }

    public static function resetDatabase(KernelInterface $kernel): void
    {
        if (! $kernel->getContainer()->has('doctrine')) {
            return;
        }

        $application = self::createApplication($kernel);
        $databaseResetter = new ORMDatabaseResetter($application, $kernel->getContainer()->get('doctrine'));

        $databaseResetter->resetDatabase();

        self::bootFoundry($kernel);

        self::$hasBeenReset = true;
    }

    public static function resetSchema(KernelInterface $kernel): void
    {
        foreach (self::schemaResetters($kernel) as $databaseResetter) {
            $databaseResetter->resetSchema();
        }

        self::bootFoundry($kernel);
    }

    /**
     * @retrun array<SchemaResetterInterface>
     */
    private static function schemaResetters(KernelInterface $kernel): array
    {
        $application = self::createApplication($kernel);
        $databaseResetters = [];

        if ($kernel->getContainer()->has('doctrine')) {
            $databaseResetters[] = new ORMDatabaseResetter($application, $kernel->getContainer()->get('doctrine'));
        }

        return $databaseResetters;
    }

    private static function bootFoundry(KernelInterface $kernel): void
    {
        if (! Factory::isBooted()) {
            TestState::bootFromContainer($kernel->getContainer());
        }

        TestState::flushGlobalState();
    }

    private static function createApplication(KernelInterface $kernel): Application
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        return $application;
    }
}
