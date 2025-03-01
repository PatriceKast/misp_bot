<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\MISPBot\Service;

use OCP\IConfig;

class ExtractionService {
	public function __construct(
		protected IConfig $config,
	) {
	}

	public function extractIPv4(string $message): array {
		// Regular expression pattern for matching IPv4 addresses
		$ipv4_pattern = '/\b(?:\d{1,3}\.){3}\d{1,3}\b/';

		// Find all matches in the given payload
		preg_match_all($ipv4_pattern, $message, $matches);
		$potential_ips = $matches[0];
	
		// Anonymous function to classify IPs
		$classify_ip = function($ip) {
			$octets = array_map('intval', explode('.', $ip));
			
			if ($octets[0] == 10 ||
				($octets[0] == 172 && $octets[1] >= 16 && $octets[1] <= 31) ||
				($octets[0] == 192 && $octets[1] == 168) ||
				$octets[0] == 127 ||
				($octets[0] == 169 && $octets[1] == 254)) {
				return "private";
			}
			
			// Ensure all octets are in the range 0-255
			foreach ($octets as $octet) {
				if ($octet < 0 || $octet > 255) {
					return "invalid";
				}
			}
			return "public";
		};
	
		$public_ips = [];
		$private_ips = [];
		
		foreach ($potential_ips as $ip) {
			$type = $classify_ip($ip);
			if ($type === "public") {
				$public_ips[] = $ip;
			} elseif ($type === "private") {
				$private_ips[] = $ip;
			}
		}
	
		return ["public_ips" => $public_ips, "private_ips" => $private_ips];
	}
}
