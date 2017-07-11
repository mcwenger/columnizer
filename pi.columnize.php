<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
	'pi_name'        => 'Columnizer',
	'pi_version'     => '1.0',
	'pi_author'      => 'Q Digital Stuio, Mike Wenger',
	'pi_author_url'  => 'http://www.qdigitalstudio.com',
	'pi_description' => 'Break an html string into multiple columns.',
	'pi_usage'       => columnize::usage()
);

class Columnize {

	/*
	 * Tag Pair
	 * @return string
	 */	
	public function pair()
	{
		
		// constructor
		$this->EE =& get_instance();
		
		// fetch the tagdata
		$tagdata = $this->EE->TMPL->tagdata;

		// fetch the params
		$columns = (is_numeric($this->EE->TMPL->fetch_param('columns'))) ? $this->EE->TMPL->fetch_param('columns') : 2;
		
		$strip_tags = ($this->EE->TMPL->fetch_param('strip_tags') === strtolower('yes') OR $this->EE->TMPL->fetch_param('strip_tags') === strtolower('true')) ? true : false;
		$html = $this->EE->TMPL->fetch_param('html');
		
		if (!$html) return;
		
		$column_data = $this->_create_columns($columns,$html,$strip_tags);
		$columns = $column_data['columns'];
		
		$variables[0]['total_columns'] = count($columns);
		foreach ($columns as $i=>$column) {
			$variables[0]['columns'][$i - 1]['column'] = $column;
			$variables[0]['columns'][$i - 1]['column_count'] = $i;
		}
		
		$parsed_string = $this->EE->TMPL->parse_variables($tagdata, $variables);
		
		return $parsed_string;
	}
	
	/*
	 * Single Tag
	 * @return string
	 */	
	public function single()
	{
	
		// constructor
		$this->EE =& get_instance();
		
		// fetch the tagdata
		$tagdata = $this->EE->TMPL->tagdata;

		// fetch the params
		$columns = (is_numeric($this->EE->TMPL->fetch_param('columns'))) ? $this->EE->TMPL->fetch_param('columns') : 2;
		$before = ($this->EE->TMPL->fetch_param('before')) ? $this->EE->TMPL->fetch_param('before') : '';
		$after = ($this->EE->TMPL->fetch_param('after')) ? $this->EE->TMPL->fetch_param('after') : '';
		$strip_tags = ($this->EE->TMPL->fetch_param('strip_tags') === strtolower('yes') OR $this->EE->TMPL->fetch_param('strip_tags') === strtolower('true')) ? true : false;
		$html = $this->EE->TMPL->fetch_param('html');
		
		$out = '';
		
		$column_data = $this->_create_columns($columns,$html,$strip_tags);
		$columns = $column_data['columns'];
		
		foreach ($columns as $i=>$column) {
			$out .= $before.$column.$after."\n";
		}

		return $out;
	}
	
	/*
	 * Create Columns
	 * @param $columns int/string  number of columns to attempt to create
	 * @param $html string  html to parse
	 * @param $strip_tags string  true/false, yes/no
	 * @return array
	 */	
	private function _create_columns($columns,$html,$strip_tags)
	{
		
	    $columns = (is_numeric($columns)) ? $columns : 2;
		$tagless = trim(strip_tags($html));
	    $html = ($strip_tags) ? $tagless : $html;
	    $html_length = strlen($html);
		$tagless_char_count = strlen($tagless);
	    $column_data = array('current_column' => 1, 'columns' => array(1 => ''));
	    $unclosed = array();
	    $unclosed_disallowed = array();
	    $reopen = array();
	    $open_sentence = false;
	    $column_counter = 0;
	    $counter = 0;
	    $i = 0;
	    
	    $chars_per_column = ceil($tagless_char_count / $columns);
		$chars_per_column_temp = 0;
		
		// div?
		$disallow_break_within = array('span','img','li','figure','a','strong','b','em','i','u','table','h1','h2','h3','h4','h5','h6','big','small','tt','abbr','acronym','cite','code','dfn','kbd','samp','var','bdo', 'br','map','object','q','script','sub','sup','button','input','label','select','textarea','area','base','col','command','embed','hr','keygen','link','meta','param','source','track','wbr','basefont','frame');
		$self_closing = array('img','button','input','br','area','base','col','command','embed','hr','keygen','link','meta','param','source','track','wbr','script','basefont','frame');
		$disallow_sentence_closure = array('mr','mrs','ms','miss','dr','phd','jr','sr','esq','mx','inc');
		
		// run through html characters
	    while ( $i < $html_length )
	    {

	        // if start of an html tag
	        if ( ($html[$i] == '<') && ($html[$i + 1] != ' ') )
	        {
	            
	            $i++;

	            // if we have an opening tag
	            if ( ($i < $html_length) && ($html[$i] != '/') && ($html[$i] != '>') )
	            {
		            
		            $current_tag = array('html' => '');
		            $current_tag['start'] = $i;
		            
		            // we're within the html tag - let's walk though it and grab the html
		            while ( ($i < $html_length) && ($html[$i] != '>') )
			        {
	                    
	                    $current_tag['html'] .= $html[$i];

	                    // detect tag name, log it
	                    if ( ($html[$i + 1] === ' ' OR $html[$i + 1] === '>' OR ($html[$i + 1] === '/' && $html[$i - 1] != '"' && $html[$i - 1] != "'")) && empty($current_tag['tag']) )
	                    {
		                    $current_tag['tag'] = $current_tag['html'];
	                    }
	                    
	                    // detect end of self-closing tag
	                    if ( ($html[$i] === '/') && ( ($html[$i + 1] === '>') OR ($html[$i + 1] === ' ' && $html[$i + 2] === '>') ) )
	                    {
		                    $current_tag['attrs'] = trim(rtrim($current_tag['html'],'/'));
		                    $current_tag['self_closing'] = true;
		                    $i++;
		                    break;
	                    }
	                    // or, if we have a self-closing tag *not* denoted by a '/'
	                   else if ( (!empty($current_tag['tag'])) && (in_array($current_tag['tag'], $self_closing)) )
	                    {
		                    $current_tag['self_closing'] = true;
	                    }
	                    
	                    // keep the iterator going
	                    $i++;
			        }
			        
			        // create rest of tagdata
			        $current_tag_data = explode(' ', $current_tag['html'], 2);
			        $current_tag['html'] = '<'.$current_tag['html'].'>';
			        
					if (empty($current_tag['attrs'])) $current_tag['attrs'] = (isset($current_tag_data[1])) ? $current_tag_data[1] : '';
	                
	                // if not self-closing, let's add the tag to the unclosed array
	                if ( empty($current_tag['self_closing']) ) $unclosed[] = $current_tag['tag'];
	                
	                // should we add this tag to the disallowed array?
	                if (in_array($current_tag['tag'], $disallow_break_within) OR in_array($current_tag['tag'], $self_closing))
	                {
			            if (!in_array($current_tag['tag'], $self_closing))
			            {
				        	$unclosed_disallowed[] = $current_tag['tag'];  
			            }
			            
	                // log tags allowed to reopen in another column
	                } else {
		                $reopen[] = $current_tag['html'];
	                }
	                
	                // if we're in a p tag, track sentences for closure
	                if ($current_tag['tag'] == 'p') $open_sentence = true;
	                
	                // always add tag html to column data
	                $column_data['columns'][$column_data['current_column']] .= $current_tag['html'];
			    }
	            
	            // closing tags
	            elseif ( $html[$i] == '/' OR $html[$i] == '>' )
	            {
		            
		            if ($html[$i] == '/')
		            {
			            $tag = '';
			            while ( ($i < $html_length) && ($html[$i] != '>') )
				        {
					    	if ($html[$i] != '/') $tag .= $html[$i];
					    	// iterate through the rest of the closing tag
					    	$i++;
					    }
			            $column_data['columns'][$column_data['current_column']] .= '</'.$tag.'>';
		            }
					
					// if tag is allowed to span columns and we're seeing a close tag for it, pop from re-open array
					if ( !in_array($tag, $disallow_break_within) )
					{
						array_pop($reopen);
					}

					// in normal run mode (not extended) - pop unclosed
					array_pop($unclosed);
					
	                // if we're closing a p tag
	                if ($current_tag['tag'] == 'p') $open_sentence = false;
					
					// pop disallowed if still in stream
		            if ( 
		            	(strval($columns) === '1') OR // only one column
		            	($column_counter < $chars_per_column) OR // normal mode
		            	($column_counter >= $chars_per_column && $unclosed_disallowed) // still disallowed
		               ) 
		            {
						array_pop($unclosed_disallowed);
						
						// we have adjacent tags beyond the column limit, end this tag and start a new column
						if ($column_counter >= $chars_per_column && $html[$i + 1] === '<')
						{
				            
				            // close close-able tags
						    foreach( array_reverse($unclosed) as $tag ) $column_data['columns'][$column_data['current_column']] .= '</'.$tag.'>';

				            // we need to check if the next tag is a closing one and if it's not a disallowed tag so we don't have an empty item starting the next column
				            if ($html[$i + 1] === '<' && $html[$i + 2] === '/')
				            {
					            $temp_i = $i + 3; // skip to the tagname
					            $temp_tag = '';
					            
					            do
					            {
						        	$temp_tag .= $html[$temp_i];
						        	$temp_i++;
					            }
					            while( ($temp_i < $html_length) && ($html[$temp_i]!= '>') );
					            
								if ( !in_array($temp_tag, $disallow_break_within) )
								{
									array_pop($reopen);
									
									// skip the iterator to close the tag
									$skip_length = (strlen($temp_tag) + 3);
									$i = $i + $skip_length;
								}								            
				            }
							
							// up the column counter and create the new index for the new column
				        	$column_data['current_column'] = $column_data['current_column'] + 1;
				            $column_data['columns'][$column_data['current_column']] = '';

				            // open re-openable tags
						    foreach( $reopen as $tag ) $column_data['columns'][$column_data['current_column']] .= $tag;

				            $column_counter = 0;
						}
					}
	            }
	        }
	        
	        // text
	        else
	        {
	            
	            $counter = $i;
	            
	            // add html to column, but don't break words
	            if ( 
	            	(strval($columns) === '1') OR // only one column
	            	($column_counter < $chars_per_column) OR // normal mode
	            	($column_counter >= $chars_per_column && $open_sentence) OR // unclosed sentence
	            	($column_counter >= $chars_per_column && $unclosed_disallowed) OR // still disallowed
	            	($column_counter >= $chars_per_column && $html[$counter] != ' ') // middle of a word
	               ) 
	            {
		            // add text
		            $column_data['columns'][$column_data['current_column']] .= $html[$counter];
	            	
	            	// if we're approaching the end of a sentence (on a period): add it to the markup, advance the counter and close the sentence tracker
	            	if ($html[$counter].$html[$counter+1] == '. ')
	            	{
	            		
	            		// detect false closures
	            		$_counter = $counter-1;
	            		$sentence_last_word = '';
	            		
	            		while ( ($_counter >= 0) && ($html[$_counter] != ' ') )
	            		{
							$sentence_last_word .= $html[$_counter];
		            		$_counter--;
	            		}
	            		$sentence_last_word = strrev($sentence_last_word);
	            		
	            		if (!in_array(strtolower($sentence_last_word), $disallow_sentence_closure))
	            		{
		            		$counter = $counter+1; // trim space at end of sentence
		            		$open_sentence = false;
	            		}
	            	}
	            	// normal tracking
	            	else
	            	{
	            		// if we're in a p tag, open the sentence tracker
	            		if (in_array('<p>', $reopen)) $open_sentence = true;
	            	}
	            }
	            
	            // start new column, reset counts/holders
	            else {
		            
		            // we've started a new column, so let's reset the sentence tracker
		            $open_sentence = false;
		            
		            // close close-able tags
				    foreach( array_reverse($unclosed) as $tag ) $column_data['columns'][$column_data['current_column']] .= '</'.$tag.'>';

		            if (!isset($column_data['current_column'][$column_data['current_column'] + 1]))
		            {
			        	$column_data['current_column'] = $column_data['current_column'] + 1;
			            $column_data['columns'][$column_data['current_column']] = '';
		            }
		            
		            // open re-openable tags
				    foreach( $reopen as $tag ) $column_data['columns'][$column_data['current_column']] .= $tag;
		            
		            // do not reset $unclosed or $reopen to span multiple columns
				    //$unclosed = array();
				    //$reopen = array();
		            $column_counter = 0;
		            $chars_per_column_temp = 0;
	            }
	            $counter++;
	            $column_counter++;
	        }

	        $i++;
	    }
	    
		return $column_data;
	} // end: _create_columns

	// ----------------------------------------------------------------
	
	/**
	 * Plugin Usage
	 */
	public static function usage()
	{
		ob_start();
?>

Columnizer
===========================

There are two tags for the plugin: pair and single.

{exp:columnize:pair columns="2" html='{html}'}
{columns}
	<div class="col col-{column_count}{if column_count == total_columns} last{/if}">{column}</div>
{/columns}
{/exp:columnize:pair}

{exp:columnize:single columns="2" before='<div class="col">' after='</div>' html='{html}'}


Parameters
===========================

Both tags have the following parameters:

- html (string) - the html to be parsed.
- strip_tags (yes/no, true/false) - strip all html before splitting into columns.
- columns (number) - the number of columns to attempt to split the html into. Default if omitted is "2".

The single tag has the following additional parameters:

- before (string) -  html to open/come before the column's html
- after (string) - html to close/come after the column's html


Pair Tag Single Variables
===========================

- {columns}{/columns} - variable pair for column variables output
-- {column} - generated html for the given column
-- {column_count} - iteration counter
-- {total_columns} - total number of columns that were created during the parsing process (*may not match set columns parameter)


Gotchas
===========================

- The parser will not create a new column if the counter is within the following tags: span, img, li, figure, a, strong, b, em, i, u, table, h1, h2, h3, h4, h5, h6, big, small, tt, abbr, acronym, cite, code, dfn, kbd, samp, var, bdo', 'br, map, object, q, script, sub, sup, button, input, label, select, textarea
- This means that an html string may not be parsed into the requested number of columns if it cannot be done. The {total_columns} var is available to know what you are actually getting from the parser.
- Be careful for validation or JS if you're using IDs in the html content - because tags are opened and closed if spanning multiple columns, IDs can be duplicated.


<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.columnize.php */
/* Location: /system/expressionengine/third_party/columnize/pi.columnize.php */