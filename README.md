# PHP Columnizer for ExpressionEngine 2.x

There are two tags for the plugin: pair and single.

```
{exp:columnize:pair columns="2" html='{html}'}
{columns}
	<div class="col col-{column_count}{if column_count == total_columns} last{/if}">{column}</div>
{/columns}
{/exp:columnize:pair}
```
```
{exp:columnize:single columns="2" before='<div class="col">' after='</div>' html='{html}'}
```

## Parameters

Both tags have the following parameters:

- html (string) - the html to be parsed.
- strip_tags (yes/no, true/false) - strip all html before splitting into columns.
- columns (number) - the number of columns to attempt to split the html into. Default if omitted is "2".

The single tag has the following additional parameters:

- before (string) -  html to open/come before the column's html
- after (string) - html to close/come after the column's html

## Pair Tag Single Variables

- {columns}{/columns} - variable pair for column variables output
-- {column} - generated html for the given column
-- {column_count} - iteration counter
-- {total_columns} - total number of columns that were created during the parsing process (*may not match set columns parameter)

## Gotchas

- The parser will not create a new column if the counter is within the following tags: span, img, li, figure, a, strong, b, em, i, u, table, h1, h2, h3, h4, h5, h6, big, small, tt, abbr, acronym, cite, code, dfn, kbd, samp, var, bdo', 'br, map, object, q, script, sub, sup, button, input, label, select, textarea
- This means that an html string may not be parsed into the requested number of columns if it cannot be done. The {total_columns} var is available to know what you are actually getting from the parser.
- Be careful for validation or JS if you're using IDs in the html content - because tags are opened and closed if spanning multiple columns, IDs can be duplicated.
