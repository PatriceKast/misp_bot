<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\MISPBot\Tests\Service;

use OCA\MISPBot\Model\LogEntry;
use OCA\MISPBot\Model\LogEntryMapper;
use OCA\MISPBot\Service\ExtractionService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ExtractionServiceTest extends TestCase {
	protected $config;
	protected $mapper;
	protected $timeFactory;
	protected $dateFormatter;
	protected $l10nFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->mapper = $this->createMock(LogEntryMapper::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->dateFormatter = $this->createMock(IDateTimeFormatter::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
	}

	/**
	 * @param string[] $methods
	 * @return ExtractionService|MockObject
	 */
	protected function getService(array $methods = []) {
		if (!empty($methods)) {
			return $this->getMockBuilder(ExtractionService::class)
				->setConstructorArgs([
					$this->config,
				])
				->onlyMethods($methods)
				->getMock();
		}

		return new ExtractionService(
			$this->config,
		);
	}

	public static function dataExtractIPv4(): array {
		return [
			[
				'BGHJ VHGvbHGVHJ NHgjb GHjbnHG',
				[],
			],
			[
				'ajfshsakfj 10.0.5.1 vhgsahjbsakhHJKFhg ausf',
				["public_ips" => [], "private_ips" => ['10.0.5.1']],
			],
			[
				'ajfshsakfj 10.0.5.1 vhgsahjbsakhHJKFhg 192.168.0.1 asfkfsafsaasf 9.9.9.9 ausf',
				["public_ips" => ['9.9.9.9'], "private_ips" => ['10.0.5.1', '192.168.0.1']],
			],
			[
				'asfkfsafsaasf 9.9.9.9 ausf',
				["public_ips" => ['9.9.9.9'], "private_ips" => []],
			]
		];
	}

	/**
	 * @dataProvider dataExtractIPv4
	 */
	public function testExtractIPv4(string $message, array $ips): void {
		$service = $this->getService();
		self::assertEquals($ips, $service->extractIPv4($message));
	}
}
