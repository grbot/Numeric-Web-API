<?php
/**
 * Step 1: Require the Slim PHP 5 Framework
 *
 * If using the default file layout, the `Slim/` directory
 * will already be on your include path. If you move the `Slim/`
 * directory elsewhere, ensure that it is added to your include path
 * or update this file path as needed.
 */
require 'Slim/Slim.php';

/**
 * Step 2: Instantiate the Slim application
 *
 * Here we instantiate the Slim application with its default settings.
 * However, we could also pass a key-value array of settings.
 * Refer to the online documentation for available settings.
 */
$app = new Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, and `Slim::delete`
 * is an anonymous function. If you are using PHP < 5.3, the
 * second argument should be any variable that returns `true` for
 * `is_callable()`. An example GET route for PHP < 5.3 is:
 *
 * $app = new Slim();
 * $app->get('/hello/:name', 'myFunction');
 * function myFunction($name) { echo "Hello, $name"; }
 *
 * The routes below work with PHP >= 5.3.
 */

/* * [J]
 * Here we map /hello/jared&callback=getData ==> index.php?name=jared&callback=getData
 * or in the latter case, we use an annonymous function...
 * */

//GET route
//JSONP
$app->get('/hello/:name&callback=:func', 'myFunction');
//next 2 lines set the content type to json.
$response = $app->response();
$response['Content-Type'] = 'application/json';
//then, since '$app->get('/hello/:name&callback=:func', 'myFunction');' calls myFunction, we'd better define myFunction! it takes 2 params as seen in hello/:name&callback=:func',
function myFunction($name,$callback) { 
	$arr = array($name => 'success!');
	$json_data = json_encode($arr);
	echo "$callback(" . "$json_data" . ");"; //the page returns getData([{"jared": 'success!'}]);
}

//NOT JSONP [J]
$app->get('/hello/:name', function($name){ 
echo "Hello there, $name";//just return something nice for now...
});


/*
 * 
 * RETURN A TREE [J]
 * 
 * */
function get_json_tree(){
	//$time_start = microtime(true);
	$connection = mysql_connect("localhost","root","qwe");
	if (!$connection){
		echo "error connecting mysql. :( ";
		die (mysql_error()); //exit out
	}
	$db_selected = mysql_select_db("numeric",$connection);//name of db 'numeric' in this case.
	if (!$db_selected){
		echo "error connecting to numeric db. ";
		die (mysql_error());
	}
	//for each grade, get the title, then
	//	for each term, get the title, then
	//		for each week, get the title and caps_id, then,
	//			for each video, get the title, url, youtube_id, ex_title, ex_url.
	$json = "";
	$json .= "[\n";
	$query = "SELECT DISTINCT grade FROM n_caps;";
	$grade_result = mysql_query($query,$connection);
	while ($grades = mysql_fetch_assoc($grade_result)) {
		/*GRADES*/
		$d_grade = $grades['grade'];
		$json .= "  {\n";//begin grade
		$json .= "    \"grade\": \"" . $grades['grade'] . "\",\n";
		$json .= "    \"children\": [\n";//begin terms
			/*TERMS*/
			$query = "SELECT DISTINCT term FROM n_caps WHERE grade=\"$d_grade\";";
			$term_result = mysql_query($query,$connection);
			while ($terms = mysql_fetch_assoc($term_result)) {
				$d_term = $terms['term'];
				$json .= "      {\n";
				$json .= "        \"term\": \"" . $d_term . "\",\n";
				$json .= "        \"children\": [\n";
				/*WEEKS/TOPICS*/
				$query = "SELECT n_caps.week, n_caps.topic FROM n_caps WHERE grade=\"$d_grade\" AND term=\"$d_term\";";
				$week_result = mysql_query($query,$connection);
				while ($weeks = mysql_fetch_assoc($week_result)) {
					$d_week = $weeks['week'];
					$d_topic = $weeks['topic'];
					$json .= "          {\n";
					$json .= "            \"week\": \"$d_week\",\n";
					$json .= "            \"topic\": \"$d_topic\",\n";
					$json .= "            \"children\": [\n";
					/*VIDEOS*/
					//get videos for this topic
					$query  = "SELECT n_videos_caps.orderNumber, n_videos_caps.exercise_title, n_videos_caps.exercise_url, n_caps.topic, n_videos.id, n_videos.title, n_videos.khan_link ";
					$query .= "FROM n_videos,n_caps,n_videos_caps ";
					$query .= "WHERE n_caps.id = n_videos_caps.caps_id ";
					$query .= "AND n_caps.grade = \"$d_grade\" ";
					$query .= "AND n_caps.term = \"$d_term\" ";
					$query .= "AND n_caps.week = \"$d_week\" ";
					$query .= "AND n_caps.topic = \"$d_topic\" ";
					$query .= "AND n_videos_caps.video_id = n_videos.id ";
					$query .= "ORDER BY n_videos_caps.orderNumber;;";
					$video_result = mysql_query($query,$connection);
					if ($video_result)
					{
						while ($videos = mysql_fetch_assoc($video_result)) {
							$d_video_title = $videos['title'];
							$d_video_url = $videos['khan_link'];
							$d_video_id = $videos['id'];
							$d_exercise_title = $videos['exercise_title'];
							$d_exercise_url = $videos['exercise_url'];
							$json .= "              {\n";
							$json .= "                \"video_title\": \"$d_video_title\",\n";
							$json .= "                \"video_url\": \"$d_video_url\",\n";
							$json .= "                \"youtube_id\": \"$d_video_id\",\n";
							$json .= "                \"exercise_title\": \"$d_exercise_title\",\n";
							$json .= "                \"exercise_url\": \"$d_exercise_url\"\n";
							$json .= "              },\n";
						}
						$json = rtrim(rtrim($json,"\n"),',') . "\n";//remove trailing ',' char
					}
					/*END_VIDEOS*/
					$json .= "            ]\n";
					$json .= "          },\n";
				}
				$json = rtrim(rtrim($json,"\n"),',') . "\n";//remove trailing ',' char
				/*END WEEKS/TOPICS*/
				$json .= "        ]\n";
				$json .= "      },\n";
			}
			$json = rtrim(rtrim($json,"\n"),',') . "\n";//remove trailing ',' char
			/*END TERMS*/
		$json .= "    ]\n";//end terms
		$json .= "  },\n";//end grade
	}
	$json = rtrim(rtrim($json,"\n"),',') . "\n";//remove trailing ',' char
	/*END GRADES*/
	$json .= "]";
	//$time_end = microtime(true);
	//$time = $time_end - $time_start;
	//echo "\ntime: $time\n";
	return $json;
}

function get_json_ncaps_tree(){
	//$time_start = microtime(true);
	$connection = mysql_connect("localhost","root","qwe");
	if (!$connection){
		echo "error connecting mysql. :( ";
		die (mysql_error()); //exit out
	}
	$db_selected = mysql_select_db("numeric",$connection);//name of db 'numeric' in this case.
	if (!$db_selected){
		echo "error connecting to numeric db. ";
		die (mysql_error());
	}
	//for each grade, get the title, then
	//	for each term, get the title, then
	//		for each week, get the title and caps_id, then,
	//			for each video, get the title, url, youtube_id, ex_title, ex_url.
	$json = "";
	$json .= "[\n";
	$query = "SELECT DISTINCT grade FROM n_caps;";
	$grade_result = mysql_query($query,$connection);
	while ($grades = mysql_fetch_assoc($grade_result)) {
		/*GRADES*/
		$d_grade = $grades['grade'];
		$json .= "  {\n";//begin grade
		$json .= "    \"grade\": \"" . $grades['grade'] . "\",\n";
		$json .= "    \"children\": [\n";//begin terms
			/*TERMS*/
			$query = "SELECT DISTINCT term FROM n_caps WHERE grade=\"$d_grade\";";
			$term_result = mysql_query($query,$connection);
			while ($terms = mysql_fetch_assoc($term_result)) {
				$d_term = $terms['term'];
				$json .= "      {\n";
				$json .= "        \"term\": \"" . $d_term . "\",\n";
				$json .= "        \"children\": [\n";
				/*WEEKS/TOPICS*/
				$query = "SELECT n_caps.id, n_caps.week, n_caps.topic FROM n_caps WHERE grade=\"$d_grade\" AND term=\"$d_term\";";
				$week_result = mysql_query($query,$connection);
				while ($weeks = mysql_fetch_assoc($week_result)) {
					$d_caps_id = $weeks['id'];
					$d_week = $weeks['week'];
					$d_topic = $weeks['topic'];
					$json .= "          {\n";
					$json .= "            \"caps_id\": \"$d_caps_id\",\n";
					$json .= "            \"week\": \"$d_week\",\n";
					$json .= "            \"topic\": \"$d_topic\"\n";
					$json .= "          },\n";
				}
				$json = rtrim(rtrim($json,"\n"),',') . "\n";//remove trailing ',' char
				/*END WEEKS/TOPICS*/
				$json .= "        ]\n";
				$json .= "      },\n";
			}
			$json = rtrim(rtrim($json,"\n"),',') . "\n";//remove trailing ',' char
			/*END TERMS*/
		$json .= "    ]\n";//end terms
		$json .= "  },\n";//end grade
	}
	$json = rtrim(rtrim($json,"\n"),',') . "\n";//remove trailing ',' char
	/*END GRADES*/
	$json .= "]";
	//$time_end = microtime(true);
	//$time = $time_end - $time_start;
	//echo "\ntime: $time\n";
	return $json;
}

function get_json_video_from_capsid($caps_id){
	$connection = mysql_connect("localhost","root","qwe");
	if (!$connection){
		echo "error connecting mysql. :( ";
		die (mysql_error()); //exit out
	}
	$db_selected = mysql_select_db("numeric",$connection);//name of db 'numeric' in this case.
	if (!$db_selected){
		echo "error connecting to numeric db. ";
		die (mysql_error());
	}
	/*VIDEOS*/
	//get videos for this topic
	$query  = "SELECT n_videos_caps.orderNumber, n_videos_caps.exercise_title, n_videos_caps.exercise_url, n_caps.topic, n_videos.id, n_videos.title, n_videos.khan_link ";
	$query .= "FROM n_videos,n_caps,n_videos_caps ";
	$query .= "WHERE n_caps.id = n_videos_caps.caps_id ";
	$query .= "AND n_caps.id = \"$caps_id\" ";
	$query .= "AND n_videos_caps.video_id = n_videos.id ";
	$query .= "ORDER BY n_videos_caps.orderNumber;";
	$video_result = mysql_query($query,$connection);
	if ($video_result)
	{
		$json="[\n";
		while ($videos = mysql_fetch_assoc($video_result)) {
			$d_video_title = $videos['title'];
			$d_video_url = $videos['khan_link'];
			$d_video_id = $videos['id'];
			$d_exercise_title = $videos['exercise_title'];
			$d_exercise_url = $videos['exercise_url'];
			$json .= "  {\n";
			$json .= "    \"video_title\": \"$d_video_title\",\n";
			$json .= "    \"video_url\": \"$d_video_url\",\n";
			$json .= "    \"youtube_id\": \"$d_video_id\",\n";
			$json .= "    \"exercise_title\": \"$d_exercise_title\",\n";
			$json .= "    \"exercise_url\": \"$d_exercise_url\"\n";
			$json .= "  },\n";
		}
		$json = rtrim(rtrim($json,"\n"),',') . "\n";//remove trailing ',' char
		$json .= "]";
	}
	/*END_VIDEOS*/
return $json;
}
//GET routes [J]

//JSONP
//next 2 lines set the content type to json.
$response = $app->response();
$response['Content-Type'] = 'application/json';
$app->get('/tree&callback=:func', function($callback){
	echo "$callback(" . json_decode(json_encode(get_json_tree())) . ");";
});

//NOT JSONP
$app->get('/tree', function(){ 
	echo json_decode(json_encode(get_json_tree()));
});
/*
 * 
 * END TREE RETURN
 * 
 * */


//JSONP
//next 2 lines set the content type to json.
$response = $app->response();
$response['Content-Type'] = 'application/json';
$app->get('/topics&callback=:func', function($callback){
	echo "$callback(" . json_decode(json_encode(get_json_ncaps_tree())) . ");";
});

//NOT JSONP
$app->get('/topics', function(){ 
	echo json_decode(json_encode(get_json_ncaps_tree()));
});

//JSONP
//next 2 lines set the content type to json.
$response = $app->response();
$response['Content-Type'] = 'application/json';
$app->get('/caps_id/:id&callback=:func', function($caps_id,$callback){
	echo "$callback(" . json_decode(json_encode(get_json_video_from_capsid($caps_id))) . ");";
});

//NOT JSONP
$app->get('/caps_id/:id', function($caps_id){
	echo json_decode(json_encode(get_json_video_from_capsid($caps_id)));
});




/* * [J]
 * Let's just leave post put and delete alone for now... We may want to look again when we've developed the curation tool.
 * */

//POST route
$app->post('/post', function () {
    echo 'This is a POST route';
});

//PUT route
$app->put('/put', function () {
    echo 'This is a PUT route';
});

//DELETE route
$app->delete('/delete', function () {
    echo 'This is a DELETE route';
});

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This is responsible for executing
 * the Slim application using the settings and routes defined above.
 */
$app->run();
