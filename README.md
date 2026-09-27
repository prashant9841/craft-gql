# GQL Docs for Craft CMS

**Stop hand-writing GraphQL for Craft.** Enter an entry ID and get ready-to-run queries and
mutations for it, plus a machine-readable schema of its fields.

Craft's GraphiQL explorer runs queries you have already written. GQL Docs writes them for you:
every field and every Matrix fragment, at any depth, along with the save and delete mutations.
Paste the result into GraphiQL or your front end and it works.

Built for headless Craft projects (Next.js, Nuxt, SPAs, mobile apps).

## Features

- **Control panel page** (**GQL Docs** in the sidebar). Enter an entry ID to get four tabs:
  - **GraphQL**: formatted *Get*, *Get all / n items*, *Create / Update* and *Delete* operations.
  - **Fields**: a compact table of the layout: label, handle, field type, GraphQL input type,
    and whether it's required. Matrix block types are nested under their field.
  - **Raw GQL**: every operation on a single line, ready to copy.
  - **Field JSON**: the field schema the front end can build forms and validation from.
- **Public endpoints** for tooling and codegen:
  - `GET /gqlraw/<entryId>`: the four operations as plain text, one per line.
  - `GET /gqlraw/<entryId>/fields`: the field schema as JSON.
- **Twig**: `craft.gqldocs.operations(entry)` and `craft.gqldocs.schema(entry)`.
- **Zero config**: no section templates, no `routes.php` and no `app.php` changes.

## Requirements

- Craft CMS 5.0+
- PHP 8.2+
- GraphQL enabled

## Installation

From the Plugin Store: search for "GQL Docs" and click **Install**.

With Composer:

```sh
composer require prashant/craft-gqldocs
php craft plugin/install gqldocs
```

## Generated operations

Example output for a `todo` entry. Relation fields get sensible sub-selections, and each
Matrix field gets a fragment per entry type.

```graphql
query MyQuery {
    entry(id: 3) {
        ... on todo_Entry {
            title
            dateCreated
            date
            desc { html }
            taskType { id title typeHandle url }
            taskStatus
        }
    }
}
```

```graphql
# Omit $id to create, pass it to update
mutation SaveEntry($id: ID, $title: String, $date: DateTime, $desc: String, $taskType: [Int], $taskStatus: String) {
    save_todo_todo_Entry(id: $id, title: $title, date: $date, desc: $desc, taskType: $taskType, taskStatus: $taskStatus) {
        id
        title
    }
}
```

## Field schema

`/gqlraw/<entryId>/fields` returns the entry type with its fields in layout order:

```json
{
	"section": "todo",
	"mutation": "save_todo_todo_Entry",
	"handle": "todo",
	"name": "Todo",
	"hasTitleField": true,
	"titleFormat": null,
	"fields": [
		{"handle": "title", "label": "Title", "type": "title", "gqlType": "String", "required": true},
		{"handle": "taskType", "label": "Task Type", "type": "craft\\fields\\Entries", "gqlType": "[Int]",
			"required": false, "sources": ["taskType"], "minRelations": null, "maxRelations": 1},
		{"handle": "taskStatus", "label": "Status", "type": "craft\\fields\\Dropdown", "gqlType": "String",
			"required": false, "options": [
				{"label": "Todo", "value": "todo", "default": true},
				{"label": "Done", "value": "done", "default": false}
			]}
	]
}
```

Every field has `handle`, `label`, `type`, `gqlType` (its argument type in the save mutation)
and `required`. Some field types add more keys:

| Field type | Extra keys |
| --- | --- |
| Dropdown, Radio Buttons, Checkboxes, Multi-select | `options: [{label, value, default}]` |
| Entries, Assets, Categories, Users | `sources` (handles, or `"*"`), `minRelations`, `maxRelations` |
| Date | `showDate`, `showTime`, `min`, `max`, `minuteIncrement` |
| Plain Text, CKEditor | `charLimit`, `wordLimit`, `multiline`, `html` |
| Number | `min`, `max`, `decimals` |
| Matrix | `minEntries`, `maxEntries`, `blockTypes` (each with its own `fields`, recursively) |

Other field types, including those from third-party plugins, get only the common keys for now.

## Access

- The control panel page is admin-only.
- The `/gqlraw` endpoints are public, the same as the site's other front-end routes. They
  expose field metadata and the operations, never content.

## Local development

To work on the plugin inside a DDEV project, point a Composer path repository at the plugin
folder:

```sh
composer config repositories.gqldocs path plugins/craft-gqldocs
composer require "prashant/craft-gqldocs:^1.0"
```

If the plugin source lives outside the project, mount it into the web container with
`.ddev/docker-compose.gqldocs.yaml`:

```yaml
services:
  web:
    volumes:
      - /path/to/GQLdocs:/var/www/html/plugins/craft-gqldocs
```

## Roadmap

- Settings, including a prefix for the `/gqlraw` routes
- Field metadata for popular third-party field types

## License

Free.
