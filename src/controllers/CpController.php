<?php

namespace prashant\gqldocs\controllers;

use craft\elements\Entry;
use craft\web\Controller;
use prashant\gqldocs\GqlDocs;
use yii\web\Response;

/**
 * CP page: enter an entry ID, pick fields, get their operations, fields, raw GQL and field JSON as tabs.
 */
class CpController extends Controller
{
	public function actionIndex(): Response
	{
		$this->requireAdmin(false);

		$entryId = $this->request->getQueryParam('entryId');
		$entry = $entryId ? Entry::find()->id((int) $entryId)->status(null)->one() : null;
		$plugin = GqlDocs::getInstance();
		// Step 1: entry ID. Step 2: pick fields. Step 3 (`generate`): tabs for the picked fields.
		$generate = $entry && $this->request->getQueryParam('generate');
		$paths = (array) $this->request->getQueryParam('fields', []);

		return $this->renderTemplate('gqldocs/_index', [
			'entryId' => $entryId,
			'entry' => $entry,
			'generate' => $generate,
			'paths' => $paths,
			'operations' => $generate ? $plugin->operations->forEntry($entry, $paths) : [],
			'schema' => $entry ? $plugin->schema->forEntry($entry, $generate ? $plugin->operations->tree($paths) : null) : null,
		]);
	}
}
