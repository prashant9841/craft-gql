<?php

namespace prashant\gqldocs\controllers;

use craft\elements\Entry;
use craft\web\Controller;
use prashant\gqldocs\GqlDocs;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Public site routes: /gqlraw/<entryId> and /gqlraw/<entryId>/fields.
 */
class RawController extends Controller
{
	protected array|bool|int $allowAnonymous = true;

	/**
	 * The entry's operations as plain text, one per line: get, list, save, delete.
	 */
	public function actionOperations(int $entryId): Response
	{
		$lines = array_column(GqlDocs::getInstance()->operations->forEntry($this->entry($entryId)), 'raw');

		$this->response->format = Response::FORMAT_RAW;
		$this->response->getHeaders()->set('Content-Type', 'text/plain; charset=UTF-8');
		$this->response->data = implode("\n", $lines) . "\n";

		return $this->response;
	}

	/**
	 * JSON field schema for the entry's type.
	 */
	public function actionFields(int $entryId): Response
	{
		return $this->asJson(GqlDocs::getInstance()->schema->forEntry($this->entry($entryId)));
	}

	private function entry(int $entryId): Entry
	{
		$entry = Entry::find()->id($entryId)->status(null)->one();

		if (!$entry) {
			throw new NotFoundHttpException('Entry not found');
		}

		return $entry;
	}
}
