<?php

namespace prashant\gqldocs\services;

use Craft;
use craft\base\FieldInterface;
use craft\elements\Entry;
use craft\ckeditor\Field as CkeditorField;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\TitleField;
use craft\fields\BaseOptionsField;
use craft\fields\BaseRelationField;
use craft\fields\Categories;
use craft\fields\Date;
use craft\fields\Matrix;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use yii\base\Component;

/**
 * Machine-readable field schema for an entry type. The front end builds forms and
 * validation from it.
 */
class Schema extends Component
{
	/**
	 * Schema for an entry's type, plus its section and save mutation name.
	 */
	public function forEntry(Entry $entry, ?array $selected = null): array
	{
		$section = $entry->getSection()?->handle;
		$type = $entry->getType();
		$schema = $this->entryType($type);
		if ($selected !== null) {
			$schema['fields'] = $this->select($schema['fields'], $selected);
		}

		return [
			'section' => $section,
			// Same naming as templates/_gql/save.twig
			'mutation' => "save_{$section}_{$type->handle}_Entry",
		] + $schema;
	}

	/**
	 * Keeps `title` plus the fields in a selection tree (see Operations::tree()), recursing
	 * into Matrix block types.
	 */
	private function select(array $fields, array $selected): array
	{
		$kept = [];
		foreach ($fields as $field) {
			if ($field['handle'] !== 'title' && !isset($selected[$field['handle']])) {
				continue;
			}
			foreach ($field['blockTypes'] ?? [] as $i => $blockType) {
				$field['blockTypes'][$i]['fields'] = $this->select($blockType['fields'], $selected[$field['handle']][$blockType['handle']] ?? []);
			}
			$kept[] = $field;
		}

		return $kept;
	}

	/**
	 * Schema for an entry type: title settings plus its layout's fields, in layout order.
	 */
	public function entryType(EntryType $entryType): array
	{
		return [
			'handle' => $entryType->handle,
			'name' => $entryType->name,
			'hasTitleField' => $entryType->hasTitleField,
			'titleFormat' => $entryType->titleFormat ?: null,
			'fields' => $this->layout($entryType->getFieldLayout()),
		];
	}

	private function layout(FieldLayout $layout): array
	{
		$fields = [];

		foreach ($layout->getTabs() as $tab) {
			foreach ($tab->getElements() as $element) {
				if ($element instanceof TitleField) {
					$fields[] = [
						'handle' => 'title',
						'label' => $element->label() ?? Craft::t('app', 'Title'),
						'type' => 'title',
						'gqlType' => 'String',
						'required' => $element->required,
					];
				} elseif ($element instanceof CustomField) {
					$field = $element->getField();
					$fields[] = [
						'handle' => $field->handle,
						'label' => $element->label() ?? $field->name,
						'type' => get_class($field),
						'gqlType' => $this->gqlType($field),
						'required' => $element->required,
					] + $this->settings($field);
				}
			}
		}

		return $fields;
	}

	private function gqlType(FieldInterface $field): string
	{
		$type = $field->getContentGqlMutationArgumentType();

		return (string) (is_array($type) ? $type['type'] : $type);
	}

	private function settings(FieldInterface $field): array
	{
		if ($field instanceof BaseOptionsField) {
			$options = [];
			foreach ($field->options as $option) {
				if (isset($option['optgroup'])) {
					continue;
				}
				$options[] = [
					'label' => $option['label'],
					'value' => $option['value'],
					'default' => (bool) ($option['default'] ?? false),
				];
			}
			return ['options' => $options];
		}

		if ($field instanceof BaseRelationField) {
			$sources = $field instanceof Categories ? [$field->source] : $field->sources;
			return [
				'sources' => $sources === '*' ? '*' : array_map(fn($source) => $this->sourceHandle($source), (array) $sources),
				'minRelations' => $field->minRelations,
				'maxRelations' => $field->maxRelations,
			];
		}

		if ($field instanceof Date) {
			return [
				'showDate' => $field->showDate,
				'showTime' => $field->showTime,
				'min' => $field->min?->format(DATE_ATOM),
				'max' => $field->max?->format(DATE_ATOM),
				'minuteIncrement' => $field->minuteIncrement,
			];
		}

		if ($field instanceof PlainText) {
			return [
				'charLimit' => $field->charLimit,
				'wordLimit' => null,
				'multiline' => $field->multiline,
				'html' => false,
			];
		}

		if ($field instanceof CkeditorField) {
			return [
				'charLimit' => $field->characterLimit,
				'wordLimit' => $field->wordLimit,
				'multiline' => true,
				'html' => true,
			];
		}

		if ($field instanceof Number) {
			return [
				'min' => $field->min,
				'max' => $field->max,
				'decimals' => $field->decimals,
			];
		}

		if ($field instanceof Matrix) {
			return [
				'minEntries' => $field->minEntries,
				'maxEntries' => $field->maxEntries,
				'blockTypes' => array_map(fn(EntryType $type) => $this->entryType($type), $field->getEntryTypes()),
			];
		}

		return [];
	}

	/**
	 * `section:<uid>` → section handle, `volume:<uid>` → volume handle, `group:<uid>` →
	 * category/user group handle. Anything else (`singles`, `admins`…) passes through.
	 */
	private function sourceHandle(string $source): string
	{
		[$kind, $uid] = array_pad(explode(':', $source, 2), 2, null);

		$model = match ($kind) {
			'section' => Craft::$app->getEntries()->getSectionByUid($uid),
			'volume' => Craft::$app->getVolumes()->getVolumeByUid($uid),
			'group' => Craft::$app->getCategories()->getGroupByUid($uid)
				?? Craft::$app->getUserGroups()->getGroupByUid($uid),
			default => null,
		};

		return $model->handle ?? $source;
	}
}
