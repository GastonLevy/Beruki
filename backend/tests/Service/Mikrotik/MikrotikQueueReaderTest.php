<?php

namespace App\Tests\Service\Mikrotik;

use App\Service\Mikrotik\MikrotikQueueReader;
use App\Service\Mikrotik\RouterOsClient;
use PHPUnit\Framework\TestCase;

final class MikrotikQueueReaderTest extends TestCase
{
    public function testNormalizesSingleHostTarget(): void
    {
        $reader = new MikrotikQueueReader($this->createMock(RouterOsClient::class));

        self::assertSame('10.10.9.67', $reader->normalizeTarget('10.10.9.67/32'));
    }

    public function testRejectsInvalidTargets(): void
    {
        $reader = new MikrotikQueueReader($this->createMock(RouterOsClient::class));

        self::assertNull($reader->normalizeTarget(''));
        self::assertNull($reader->normalizeTarget('not-an-ip/32'));
        self::assertNull($reader->normalizeTarget('10.10.9.67/24'));
        self::assertNull($reader->normalizeTarget('10.10.9.67/32,10.10.9.68/32'));
    }

    public function testReadsValidQueuesAndCountsInvalidQueuesWithoutUsingNames(): void
    {
        $routerOsClient = $this->createMock(RouterOsClient::class);
        $routerOsClient
            ->method('fetchSimpleQueues')
            ->willReturn([
                [
                    'target' => '10.10.9.67/32',
                    'max-limit' => '50M/100M',
                    'disabled' => 'no',
                    'name' => 'ignored-name',
                ],
                [
                    'target' => '10.10.9.68/24',
                    'max-limit' => '50M/100M',
                    'disabled' => 'yes',
                    'name' => 'also-ignored',
                ],
            ]);
        $routerOsClient
            ->method('fetchArpEntries')
            ->willReturn([
                [
                    'address' => '10.10.9.67',
                    'mac-address' => 'AA-BB-CC-DD-EE-FF',
                ],
            ]);

        $result = (new MikrotikQueueReader($routerOsClient))->readSimpleQueues();

        self::assertSame(2, $result->queuesRead);
        self::assertCount(1, $result->queues);
        self::assertSame(1, $result->invalidQueues);
        self::assertSame('10.10.9.67', $result->queues[0]->serviceIp);
        self::assertFalse($result->queues[0]->disabled);
        self::assertSame('100', $result->queues[0]->downloadRate);
        self::assertSame('50', $result->queues[0]->uploadRate);
        self::assertSame('100/50', $result->queues[0]->planKey);
        self::assertSame('aa:bb:cc:dd:ee:ff', $result->queues[0]->macAddress);
    }

    public function testArpEntriesWithoutMacDoNotBlockQueueImport(): void
    {
        $routerOsClient = $this->createMock(RouterOsClient::class);
        $routerOsClient
            ->method('fetchSimpleQueues')
            ->willReturn([
                [
                    'target' => '10.10.9.67/32',
                    'max-limit' => '50M/100M',
                    'disabled' => 'no',
                ],
                [
                    'target' => '10.10.9.68/32',
                    'max-limit' => '50M/100M',
                    'disabled' => 'no',
                ],
            ]);
        $routerOsClient
            ->method('fetchArpEntries')
            ->willReturn([
                [
                    'address' => '10.10.9.67',
                ],
                [
                    'address' => '10.10.9.68',
                    'mac-address' => 'not-a-mac',
                ],
                [
                    'mac-address' => 'aa:bb:cc:dd:ee:ff',
                ],
            ]);

        $result = (new MikrotikQueueReader($routerOsClient))->readSimpleQueues();

        self::assertCount(2, $result->queues);
        self::assertNull($result->queues[0]->macAddress);
        self::assertNull($result->queues[1]->macAddress);
    }

    public function testNormalizesMacAddress(): void
    {
        $reader = new MikrotikQueueReader($this->createMock(RouterOsClient::class));

        self::assertSame('aa:bb:cc:dd:ee:ff', $reader->normalizeMacAddress('AA-BB-CC-DD-EE-FF'));
        self::assertSame('aa:bb:cc:dd:ee:ff', $reader->normalizeMacAddress(' aa:bb:cc:dd:ee:ff '));
        self::assertNull($reader->normalizeMacAddress('not-a-mac'));
        self::assertNull($reader->normalizeMacAddress(''));
        self::assertNull($reader->normalizeMacAddress(null));
    }

    public function testNormalizesMaxLimitFromRouterOsUploadDownloadToBerukiDownloadUpload(): void
    {
        $reader = new MikrotikQueueReader($this->createMock(RouterOsClient::class));

        self::assertSame([
            'downloadRate' => '100',
            'uploadRate' => '50',
            'planKey' => '100/50',
        ], $reader->normalizeMaxLimit('50M/100M'));

        self::assertSame([
            'downloadRate' => '100',
            'uploadRate' => '100',
            'planKey' => '100/100',
        ], $reader->normalizeMaxLimit('100M/100M'));
    }

    public function testNormalizesRatesWithoutAssumingMegabitsOnly(): void
    {
        $reader = new MikrotikQueueReader($this->createMock(RouterOsClient::class));

        self::assertSame([
            'downloadRate' => '1000',
            'uploadRate' => '0.512',
            'planKey' => '1000/0.512',
        ], $reader->normalizeMaxLimit('512K/1G'));

        self::assertSame([
            'downloadRate' => '100',
            'uploadRate' => '50',
            'planKey' => '100/50',
        ], $reader->normalizeMaxLimit('50000000/100000000'));
    }

    public function testRejectsInvalidMaxLimit(): void
    {
        $reader = new MikrotikQueueReader($this->createMock(RouterOsClient::class));

        self::assertNull($reader->normalizeMaxLimit(''));
        self::assertNull($reader->normalizeMaxLimit('50M'));
        self::assertNull($reader->normalizeMaxLimit('50M/100M/200M'));
        self::assertNull($reader->normalizeMaxLimit('0M/100M'));
        self::assertNull($reader->normalizeMaxLimit('50X/100M'));
    }
}
