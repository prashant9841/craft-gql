<?php

namespace prashant\gqldocs;

use craft\elements\Entry;

/**
 * craft.gqldocs
 */
class Variable
{
	public function schema(Entry $entry): array
	{
		return GqlDocs::getInstance()->schema->forEntry($entry);
	}

	public function operations(Entry $entry): array
	{
		return GqlDocs::getInstance()->operations->forEntry($entry);
	}
}
