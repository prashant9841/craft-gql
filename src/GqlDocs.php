<?php

namespace prashant\gqldocs;

use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use prashant\gqldocs\services\Operations;
use prashant\gqldocs\services\Schema;
use yii\base\Event;

/**
 * GQL Docs
 *
 * - CP page (GQL Docs → enter an entry ID): GraphQL operations, field list, raw GQL, field JSON.
 * - Site routes, public: /gqlraw/<entryId> (operations, one per line) and
 *   /gqlraw/<entryId>/fields (field schema JSON).
 * - Twig: craft.gqldocs.schema(entry), craft.gqldocs.operations(entry).
 *
 * @property-read Schema $schema
 * @property-read Operations $operations
 */
class GqlDocs extends Plugin
{
	public bool $hasCpSection = true;

	public static function config(): array
	{
		return [
			'components' => [
				'schema' => Schema::class,
				'operations' => Operations::class,
			],
		];
	}

	public function init(): void
	{
		parent::init();

		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
			$event->rules['gqlraw/<entryId:\d+>/fields'] = 'gqldocs/raw/fields';
			$event->rules['gqlraw/<entryId:\d+>'] = 'gqldocs/raw/operations';
		});

		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
			$event->rules['gqldocs'] = 'gqldocs/cp/index';
		});

		Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
			$event->sender->set('gqldocs', Variable::class);
		});
	}

	public function getCpNavItem(): ?array
	{
		$item = parent::getCpNavItem();
		$item['label'] = 'GQL Docs';
		return $item;
	}
}
