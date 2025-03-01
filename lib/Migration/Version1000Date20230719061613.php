<?php

declare(strict_types=1);

/**
 * This migration is used to set the feature type to 4 after Installation of the Bot (not needed after NextCloud v27, since feature already correctly passed to nextcloud on BotInstallEvent)
 */

namespace OCA\MISPBot\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * FIXME Auto-generated migration step: Please modify to your needs!
 */
class Version1000Date20230719061613 extends SimpleMigrationStep {
	public function __construct(
		protected IDBConnection $db,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$update = $this->db->getQueryBuilder();
		$update->update('talk_bots_server')
			->set('url', $update->createParameter('url'))
			->set('url_hash', $update->createParameter('url_hash'))
			->set('features', $update->createParameter('features'))
			->where($update->expr()->eq('id', $update->createParameter('id')));

		$select = $this->db->getQueryBuilder();
		$select->select('id', 'url')
			->from('talk_bots_server')
			->where($select->expr()->like('url', $select->createNamedParameter(
				'%' . $this->db->escapeLikeParameter('/ocs/v2.php/apps/misp_bot/api/v1/bot/') . '%'
			)));

		$result = $select->executeQuery();
		while ($row = $result->fetch()) { // loop through all versions of the installed bot (in all available languages)
			$urlParts = explode('/', $row['url']);
			$url = 'nextcloudapp://misp_bot/' . array_pop($urlParts);
			$update->setParameter('url', $url)
				->setParameter('url_hash', sha1($url))
				->setParameter('features', 4, IQueryBuilder::PARAM_INT) // Feature=4 (event) - Bot is invoked via PHP event OCA\Talk\Events\BotInvokeEvent; Source (https://nextcloud-talk.readthedocs.io/en/latest/constants/#bot-features)
				->setParameter('id', $row['id'], IQueryBuilder::PARAM_INT);
			$result1 = $update->executeStatement();
		}
		$result->closeCursor();
	}
}
