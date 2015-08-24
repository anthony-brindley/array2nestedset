<?php
/**
 * @author: Oliver Blum <blumanskim@gmail.com>
 * This is an exampel on how to translate a multi dimensional array to a flat
 * nested set array with left, right, level and parent.
 * 
 * In this example, the json string comes from the nestable drag and drop plugin
 * @see https://github.com/dbushell/Nestable
 * 
 * In my scenario, I have a menu based on the "nested set model" and combined this with the
 * nestable drag and drop. 
 * @see https://en.wikipedia.org/wiki/Nested_set_model
 * 
 * The json string with the array, coming from the drag and drop plugin, is getting translated to a nested set array with left and right.
 * The nested set table will then get updated using the left and right values.
 * 
 */

date_default_timezone_set('Australia/Brisbane');
ini_set('display_errors', 1);
error_reporting(E_ALL);

	/**
	 * get the count of children in a multi dimensional - nested - array
	 * @param array/string $data - array we want the count of
	 * @param bool $reset - reset count for a fresh start
	 */
	function getChildCount($data, $reset = false)
	{
    	// keep value
		static $count = 0;
       
		// if a reset is needed, for a fresh start
		if($reset === true) {
			$count  = 0;
		}    
		
		// validate array
		if(is_array($data) && count($data)) {
			// loop through array
			foreach($data AS $key => $value) {
				// increase count
				$count++;
				// if children are available, the process has to get restarted for deeper levels
				if(isset($value['children']) && is_array($value['children']) && count($value['children'])) {
					// resatrt the process
					getChildCount($value['children']);
				}
			}
		}
		
		// return
		return $count;
    }
    
    
    /**
     * Walk recursive through the array and translate the structure to a nested set "left" and "right" structure.
     * @note The $level parameter is 2 as the root itself is not included, the root itself would have left = 1 and right = (last right) + 1.
     * It is not included as, in my case, the root knows already left and right, this is used for updates only, therefor no need to change the root itself.
     * 
     * @param array/string $data
     * @param int $parent
     * @param int $level 
     * @return array
     */
	function recursiveChildren($data, $parent = 0, $level = 2)
	{
		// keep the array and build up
		static $array = array();
		// keep value
		static $left  = 1;
        
		// validate array
		if(is_array($data) && count($data)) {

			// walk through array
            foreach($data AS $key => $value) {
                // increase left
                $left++;
                
                // set up the new array
                $array[$value['id']]['left']    = $left;
                $array[$value['id']]['data']    = $value;
                $array[$value['id']]['menuid']  = $value['id'];
                $array[$value['id']]['level']   = $level;
                
                // set up the parent id
                if($parent > 0) {
                    $array[$value['id']]['parent']  = $parent;
                } else {
                    $array[$value['id']]['parent']  = $value['parent'];
                }
                
                // remove the children as not needed
                unset($array[$value['id']]['data']['children']);
                
                // if the item has children...
                if(isset($value['children']) && count($value['children'])) {
                    
                	// look up the count of children to get the "right" value
                    $countChildren = getChildCount($value['children'], true);
                    // as each item has two numbers, it needs to get multiplied by 2 and plus the 1 to close the open left
                    $countChildren = $countChildren * 2 + 1;
                    // the count has to get added the actual left to have the correct right
                    $array[$value['id']]['right'] = $countChildren + $left;
 					// restart the process
                    recursiveChildren($value['children'], $value['id'], ($array[$value['id']]['level'] + 1));
                    // set left after knowing the latest right value, the increment (++) will go from there
                    $left = $array[$value['id']]['right'];
                    
                } else {
                    
                    // no children, need to increase left and set it as right number
                    $left++;
                    $array[$value['id']]['right'] = $left;
                }
            }
        }
        
        return $array;
    }


// example json data
$json = '[
    {
        "type": "Page",
        "id": 2,
        "title": "Home",
        "parent": 1
    },
    {
        "type": "Module",
        "id": 6,
        "title": "Properties Menu",
        "parent": 1
    },
    {
        "type": "Module",
        "id": 13,
        "title": "Event Blog",
        "parent": 1
    },
    {
        "type": "Page",
        "id": 15,
        "title": "Category 1",
        "parent": 1,
        "children": [
            {
                "type": "Page",
                "id": 17,
                "title": "Page 3",
                "parent": 15,
                "children": [
                    {
                        "type": "Page",
                        "id": 18,
                        "title": "Page 4",
                        "parent": 17
                    }
                ]
            },
            {
                "type": "Page",
                "id": 19,
                "title": "Page 5",
                "parent": 15,
                "children": [
                    {
                        "type": "Page",
                        "id": 21,
                        "title": "Page 7",
                        "parent": 19,
                        "children": [
                            {
                                "type": "Page",
                                "id": 22,
                                "title": "Page 8",
                                "parent": 21
                            }
                        ]
                    }
                ]
            },
            {
                "type": "Page",
                "id": 23,
                "title": "Page 9",
                "parent": 15
            }
        ]
    },
    {
        "type": "Page",
        "id": 16,
        "title": "Category 2",
        "parent": 1,
        "children": [
            {
                "type": "Page",
                "id": 20,
                "title": "Page 6",
                "parent": 16
            }
        ]
    },
    {
        "type": "Page",
        "id": 14,
        "title": "Page 2",
        "parent": 16
    }
]';


print '<p>Translate a multi dimensional array, coming from nestable.js (http://dbushell.github.io) to a flat nested set model array with left, right and parent.</p>';
print '<h3>Json String</h3>';
print '<p>As it comes from the nestable js plugin</p>';

print '<pre>';
print_r($json);
print '</pre>';

// decode json to an array
$array 	= json_decode($json, true);
// translate array to nested set flat array with left, right, level
$turn 	= recursiveChildren($array);

print '<h3>translated to a PHP array</h3>';
print '<p>This can no get used to update the nested set menu tree.</p>';

print '<pre>';
print_r($turn);
print '</pre>';
