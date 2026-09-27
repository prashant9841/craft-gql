<?php

namespace prashant\gqldocs\services;

use Craft;
use craft\elements\Entry;
use craft\web\View;
use yii\base\Component;

/**
 * An entry's GraphQL operations (get, list, save, delete), rendered from templates/_gql.
 */
class Operations extends Component
{
	private const OPERATIONS = [
		'get' => 'Get',
		'list' => 'Get all / n items',
		'save' => 'Create / Update',
		'delete' => 'Delete',
	];

	/**
	 * @return array<array{handle: string, title: string, code: string, raw: string}>
	 *   `code` is formatted with `#` hints; `raw` is the same operation on one line.
	 * @param string[]|null $paths Selected field paths (`handle`, `matrix.entryType.handle`…);
	 *   null selects every field.
	 */
	public function forEntry(Entry $entry, ?array $paths = null): array
	{
		$view = Craft::$app->getView();
		$selected = $paths === null ? null : $this->tree($paths);
		$fields = $entry->getFieldLayout()->getCustomFields();
		if ($selected !== null) {
			$fields = array_values(array_filter($fields, fn($field) => isset($selected[$field->handle])));
		}
		$variables = [
			'entry' => $entry,
			'fields' => $fields,
			'selected' => $selected,
		];

		$operations = [];
		foreach (self::OPERATIONS as $handle => $title) {
			$code = $view->renderTemplate("gqldocs/_gql/{$handle}", $variables, View::TEMPLATE_MODE_CP);
			// Template Comments wraps includes in HTML comments
			$code = trim(preg_replace('/<!--.*?-->/s', '', $code));

			$operations[] = [
				'handle' => $handle,
				'title' => $title,
				'code' => $code,
				'raw' => trim(preg_replace(['/#[^\n]*/', '/\s+/'], ['', ' '], $code)),
			];
		}

		return $operations;
	}

	/**
	 * `['a', 'm.type.b']` → `['a' => [], 'm' => ['type' => ['b' => []]]]`
	 */
	public function tree(array $paths): array
	{
		$tree = [];
		foreach ($paths as $path) {
			$node = &$tree;
			foreach (explode('.', (string) $path) as $segment) {
				$node[$segment] ??= [];
				$node = &$node[$segment];
			}
			unset($node);
		}

		return $tree;
	}
}
