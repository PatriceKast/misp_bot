<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\MISPBot\Service;

use OCA\MISPBot\AppInfo\Application;
use OCA\MISPBot\Model\Bot;
use OCA\Talk\Events\BotInstallEvent;
use OCA\Talk\Events\BotUninstallEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;

class BotService {
	public function __construct(
		protected IConfig $config,
		protected IURLGenerator $url,
		protected IEventDispatcher $dispatcher,
		protected IFactory $l10nFactory,
		protected ISecureRandom $random,
	) {
	}

	public function installBot(string $backend): void {
		$id = sha1($backend);

		$secretData = $this->config->getAppValue('misp_bot', 'secret_' . $id);
		if ($secretData) {
			$secretArray = json_decode($secretData, true, 512, JSON_THROW_ON_ERROR);
			$secret = $secretArray['secret'] ?? $this->random->generate(64, ISecureRandom::CHAR_HUMAN_READABLE);
		} else {
			$secret = $this->random->generate(64, ISecureRandom::CHAR_HUMAN_READABLE);
		}
		foreach (Bot::SUPPORTED_LANGUAGES as $lang) {
			$this->installLanguage($secret, $lang);
		}

		$this->config->setAppValue('misp_bot', 'secret_' . $id, json_encode([
			'id' => $id,
			'secret' => $secret,
			'backend' => $backend,
		], JSON_THROW_ON_ERROR));
	}

	protected function installLanguage(string $secret, string $lang): void {
		$libL10n = $this->l10nFactory->get('lib', $lang);
		$langName = $libL10n->t('__language_name__');
		if ($langName === '__language_name__') {
			$langName = $lang === 'en' ? 'British English' : $lang;
		}

		$l = $this->l10nFactory->get('misp_bot', $lang);

		$event = new BotInstallEvent( // Source: https://github.com/nextcloud/spreed/blob/f82d97a63730bb3f3219b94d4d4057312f040a6b/lib/Events/BotInstallEvent.php#L14
			'MISP IoC Importer Bot',
			$secret . str_replace('_', '', $lang),
			'nextcloudapp://' . Application::APP_ID . '/' . $lang,
			$l->t('bot_description'),
			features: 4, // EVENT
		);
		try {
			$this->dispatcher->dispatchTyped($event);
		} catch (\Throwable) {
		}
	}

	public function uninstallBot(string $secret): void {
		foreach (Bot::SUPPORTED_LANGUAGES as $lang) {
			$this->uninstallLanguage($secret, $lang);
		}
	}

	protected function uninstallLanguage(string $secret, string $lang): void {
		$event = new BotUninstallEvent(
			$secret . str_replace('_', '', $lang),
			'nextcloudapp://' . Application::APP_ID . '/' . $lang,
		);
		try {
			$this->dispatcher->dispatchTyped($event);
		} catch (\Throwable) {
		}

		// Also remove legacy secret bots
		$event = new BotUninstallEvent(
			$secret,
			'nextcloudapp://' . Application::APP_ID . '/' . $lang,
		);
		try {
			$this->dispatcher->dispatchTyped($event);
		} catch (\Throwable) {
		}
	}
}
