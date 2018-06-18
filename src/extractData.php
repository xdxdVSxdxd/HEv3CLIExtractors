<?php

require_once("db.php");
require_once("StopWords.php");

function utf8ize($d) {
    if (is_array($d)) 
        foreach ($d as $k => $v) 
            $d[$k] = utf8ize($v);

     else if(is_object($d))
        foreach ($d as $k => $v) 
            $d->$k = utf8ize($v);

     else 
        return utf8_encode($d);

    return $d;
}


$stopwords = new \StopWords();

setlocale(LC_ALL, 'ita', 'it_IT');
date_default_timezone_set("Europe/Rome");


if(isset( $argv[1] ) && isset($argv[2])  ){


	$researcharray = explode(",", $argv[2] ); 
	$researchidcondition = "research_id IN ( " . (  implode(",", $researcharray)  ) . " ) ";

	if($argv[1]=="timeline"){


		$res = array();
		$res[0] = array("date","close");

		$q1 = "SELECT DAY(created_at) as d, MONTH(created_at) as m, YEAR(created_at) as y, count(*) as c FROM contents c WHERE c." . $researchidcondition . " GROUP BY YEAR(created_at), MONTH(created_at), DAY(created_at) ORDER BY created_at DESC";
		$r1 = $pdo->query( $q1 );
		if($r1){
			foreach ($r1 as $row) {
				$line = array();
				$line[] = ($row["d"]<10?"0":"") . $row["d"] . "-" . ($row["m"]<10?"0":"") . $row["m"] . "-" . ($row["y"]<100?"19":"") . $row["y"] ;
				$line[] = intval($row["c"]);
				$res[] = $line;
			}
			$r1->closeCursor();
		}
		$fname = 'files/' . date('Ymd') . '-timeline-' . implode("_", $researcharray) . ".csv";
		if(file_exists($fname)){
			unlink($fname);
		}
		$fp = fopen( $fname , 'w');
		foreach ($res as $line) {
		    fputcsv($fp, $line, ',');
		}
		fclose($fp);

	} else if($argv[1]=="sentimenttimeline"){

		$negative_threshold = 20;

		$res = array();
		$res[0] = array("date","positive","negative","neutral");

		$tempa = array();

		$q1 = "SELECT DAY(created_at) as d, MONTH(created_at) as m, YEAR(created_at) as y, comfort, energy FROM contents c WHERE c." . $researchidcondition . " ORDER BY created_at DESC";
		$r1 = $pdo->query( $q1 );
		if($r1){
			foreach ($r1 as $row) {
				$key = ($row["d"]<10?"0":"") . $row["d"] . "-" . ($row["m"]<10?"0":"") . $row["m"] . "-" . ($row["y"]<100?"19":"") . $row["y"] ;
				$c = intval($row["comfort"]);
				$a = array( 0 , 0 , 0 );
				if($c<-$negative_threshold){
					$a[1] = 1;
				} else if($c>$negative_threshold){
					$a[0] = 1;
				} else {
					$a[2] = 1;
				}

				if(isset($tempa[$key])){
					$tempa[$key][0] = $tempa[$key][0] + $a[0];
					$tempa[$key][1] = $tempa[$key][1] + $a[1];
					$tempa[$key][2] = $tempa[$key][2] + $a[2];
				} else{
					$tempa[$key] = array();
					$tempa[$key][0] = $a[0];
					$tempa[$key][1] = $a[1];
					$tempa[$key][2] = $a[2];
				}

			}
			$r1->closeCursor();

			foreach ($tempa as $key => $value) {
				$a = array($key,$value[0],$value[1],$value[2]);
				$res[] = $a;
			}

		}
		$fname = 'files/' . date('Ymd') . '-sentiment-timeline-' . implode("_", $researcharray) . ".csv";
		if(file_exists($fname)){
			unlink($fname);
		}
		$fp = fopen( $fname , 'w');
		foreach ($res as $line) {
		    fputcsv($fp, $line, ',');
		}
		fclose($fp);


	} else if($argv[1]=="tag"){


		$res = array();
		$res[0] = array("tag","weight");

		$tempa = array();

		$q1 = "SELECT  e.entity as entity , count(*) as c FROM contents_entities ce ,entities e WHERE ce." . $researchidcondition . " AND e.id=ce.entity_id AND e.entity_type_id=1 GROUP BY entity ORDER BY entity ASC";
		$r1 = $pdo->query( $q1 );
		if($r1){
			foreach ($r1 as $row) {
				$res[] = array( $row["entity"] , intval($row["c"]) );

			}
			$r1->closeCursor();

			

		}
		$fname = 'files/' . date('Ymd') . '-tags-' . implode("_", $researcharray) . ".csv";
		if(file_exists($fname)){
			unlink($fname);
		}
		$fp = fopen( $fname , 'w');
		foreach ($res as $line) {
		    fputcsv($fp, $line, ',');
		}
		fclose($fp);



	} else if($argv[1]=="tagnetwork"){

		$maxweight = 0;

		$res = array();
		$res["nodes"] = array(); $res["nodes"][0] = array("id","label","weight","comfort","energy");
		$res["links"] = array(); $res["links"][0] = array("source","target","weight");

		$nodes = array();
		$links = array();


		$q1 = "SELECT  e.id as eid, e.entity as label, count(*) as weight, AVG(c.comfort) as comfort, AVG(c.energy) as energy FROM contents c, contents_entities ce, entities e WHERE ce." . $researchidcondition . " AND e.id=ce.entity_id AND e.entity_type_id=1 AND c.id=ce.content_id GROUP BY label";


		$r1 = $pdo->query( $q1 );


		if($r1){

			foreach ($r1 as $c) {
				
					$o = array();
					$o[] = $c["eid"];
					$o[] = $c["label"];
					$o[] = $c["weight"];
					$o[] = $c["comfort"];
					$o[] = $c["energy"];

					$res["nodes"][] = $o;

			}
			$r1->closeCursor();

			$q1 = "SELECT e1.entity as source, e2.entity as target , count(*) as weight FROM contents_entities ce1, contents_entities ce2, entities e1, entities e2 WHERE ce1." . $researchidcondition . " AND ce2." . $researchidcondition . " AND ce1.entity_id=e1.id AND ce2.entity_id=e2.id AND ce1.content_id=ce2.content_id AND e1.entity_type_id=1 AND e2.entity_type_id=1 group by source,target";


			$r1 = $pdo->query( $q1 );

		}

		if($r1){

			foreach ($r1 as $c) {
				
					$o = array();
					$o[] = $c["source"];
					$o[] = $c["target"];
					$o[] = $c["weight"];

					$res["links"][] = $o;

			}
			$r1->closeCursor();

		}
				
		
		
		$fname = 'files/' . date('Ymd') . '-tags-nodes-' . implode("_", $researcharray) . ".csv";
		if(file_exists($fname)){
			unlink($fname);
		}
		$fp = fopen( $fname , 'w');
		foreach ($res["nodes"] as $line) {
		    fputcsv($fp, $line, ',');
		}
		fclose($fp);

		$fname = 'files/' . date('Ymd') . '-tags-links-' . implode("_", $researcharray) . ".csv";
		if(file_exists($fname)){
			unlink($fname);
		}
		$fp = fopen( $fname , 'w');
		foreach ($res["links"] as $line) {
		    fputcsv($fp, $line, ',');
		}
		fclose($fp);


	} else if($argv[1]=="tagshierarchy"){


		
		if( isset($argv[2])  && isset($argv[3]) && isset($argv[4] )   ){

			$howmanytags = intval($argv[2]);
			$howmanysubtags = intval($argv[3]);

			$researcharray = explode(",", $argv[4] ); 
			$researchidcondition = "research_id IN ( " . (  implode(",", $researcharray)  ) . " ) ";

			echo("[howmanytags]" . $howmanytags . "\n");
			echo("[howmanysubtags]" . $howmanysubtags . "\n");
			echo("[researchidcondition]" . $researchidcondition . "\n");


			$res = new \stdClass();

			$res->topics = array();
			
			$q1 = "SELECT  e.id as eid, e.entity as label, count(*) as weight FROM contents_entities ce, entities e WHERE ce." . $researchidcondition . " AND e.id=ce.entity_id AND e.entity_type_id=1 GROUP BY label ORDER BY weight DESC limit 0," . $howmanytags;

			//echo($q1 . "\n\n");

			$r1 = $pdo->query( $q1 );
			if($r1){
				foreach ($r1 as $row) {
					
					$eid = $row["eid"];

					$o = new \stdClass();
					$o->topic = $row["label"];
					$o->weight = $row["weight"];
					$o->children = array();


					// cercare subtopics

					$q2 = "SELECT  e.id as eid, e.entity as label, count(*) as weight FROM contents_entities ce, entities e WHERE ce." . $researchidcondition . " AND NOT e.id=" . $eid . " AND e.id=ce.entity_id AND e.entity_type_id=1 AND ce.content_id IN (SELECT content_id FROM contents_entities ce2 WHERE ce2.entity_id=" . $eid  .  ") GROUP BY label ORDER BY weight DESC limit 0," . $howmanytags;

					//echo($q2 . "\n\n");

					$r2 = $pdo->query( $q2 );
					if($r2){
						foreach ($r2 as $row2) {
							$o2 = new \stdClass();
							$o2->topic = $row2["label"];
							$o2->weight = $row2["weight"];
							$o->children[] = $o2;
						}
						$r2->closeCursor();		
					}


					//print_r($o);

					$res->topics[] = $o;

				}
				$r1->closeCursor();

				

			}


			print_r($res);

			$fname = 'files/' . date('Ymd') . '-toptags-' . implode("_", $researcharray) . ".json";

			echo("[fname]" . $fname . "\n\n");

			if(file_exists($fname)){
				unlink($fname);
			}
			//$fp = fopen( $fname , 'w');

			file_put_contents($fname, json_encode( utf8ize($res) ) );
			//fclose($fp);



		} else {
echo '

ERROR!

with the tagshierarchy command you need to specify
the [x] and [y] parameters, as integer numbers
indicating the number of top tags you want and
the top sub-tags for each one of them, respectively

';
		}


	} else if($argv[1]=="topusers"){

		$res = array();
		$res[0] = array("id","name","nick","followers","friends","profile_url","profile_image","posts","favorites","shares");

		
		$q1 = "SELECT s.id as subject_id, s.name as name, s.screen_name as nick, s.followers_count as followers, s.friends_count as friends, s.profile_url as purl, s.profile_image_url as imageurl , count(c.id) as nposts, sum(c.favorite_count) as favorites , sum(retweet_count) as shares FROM subjects s, contents c WHERE c." . $researchidcondition . " AND c.subject_id=s.id GROUP BY subject_id ORDER BY name ASC";
		$r1 = $pdo->query( $q1 );
		if($r1){
			foreach ($r1 as $row) {
				$o = array();
				$o[] = $row["subject_id"];
				$o[] = $row["name"];
				$o[] = $row["nick"];
				$o[] = $row["followers"];
				$o[] = $row["friends"];
				$o[] = $row["purl"];
				$o[] = $row["imageurl"];
				$o[] = $row["nposts"];
				$o[] = $row["favorites"];
				$o[] = $row["shares"];

				$res[] = $o;

			}
			$r1->closeCursor();

		}
		$fname = 'files/' . date('Ymd') . '-topusers-' . implode("_", $researcharray) . ".csv";
		if(file_exists($fname)){
			unlink($fname);
		}
		$fp = fopen( $fname , 'w');
		foreach ($res as $line) {
		    fputcsv($fp, $line, ',');
		}
		fclose($fp);


	} else if($argv[1]=="userrelations"){



		$res = array();
		$res["nodes"] = array();
		$res["links"] = array();

		$res["nodes"][0] = array("id","weight");
		$res["links"][0] = array("source","target","weight");


		$tempnodes = array();

		
		$q1 = "SELECT DISTINCT s1.id as sourceid, s1.screen_name as sourcenick , s1.profile_url as sourceurl, s2.id as targetid, s2.screen_name as targetnick , s2.profile_url as targeturl , r.c as c FROM subjects s1, subjects s2, relations r WHERE r." . $researchidcondition . " AND  s1.id=r.subject_1_id AND s2.id=r.subject_2_id";
		$r1 = $pdo->query( $q1 );
		if($r1){
			foreach ($r1 as $row) {
				$o = array();
				$o[] = $row["sourcenick"];
				$o[] = $row["targetnick"];
				$o[] = $row["c"];

				if(isset($tempnodes[$row["sourcenick"]])){
					$tempnodes[$row["sourcenick"]] = $tempnodes[$row["sourcenick"]] + 1;
				} else {
					$tempnodes[$row["sourcenick"]] = 1;
				}

				if(isset($tempnodes[$row["targetnick"]])){
					$tempnodes[$row["targetnick"]] = $tempnodes[$row["targetnick"]] + 1;
				} else {
					$tempnodes[$row["targetnick"]] = 1;
				}

				$res["links"][] = $o;

			}
			$r1->closeCursor();

		}

		foreach ($tempnodes as $key => $value) {
			$res["nodes"][] = array($key,$value);
		}

		$fname = 'files/' . date('Ymd') . '-user-relations-nodes-' . implode("_", $researcharray) . ".csv";
		if(file_exists($fname)){
			unlink($fname);
		}
		$fp = fopen( $fname , 'w');
		foreach ($res["nodes"] as $line) {
		    fputcsv($fp, $line, ',');
		}
		fclose($fp);

		$fname = 'files/' . date('Ymd') . '-user-relations-links-' . implode("_", $researcharray) . ".csv";
		if(file_exists($fname)){
			unlink($fname);
		}
		$fp = fopen( $fname , 'w');
		foreach ($res["links"] as $line) {
		    fputcsv($fp, $line, ',');
		}
		fclose($fp);


	}

	 else if($argv[1]=="1topic"){

		if( isset($argv[2])  && isset($argv[3])  ){

			$topic = $argv[2];

			$researcharray = explode(",", $argv[3] ); 
			$researchidcondition = "research_id IN ( " . (  implode(",", $researcharray)  ) . " ) ";

			$maxweight = 0;

			$nodes = array();
			$links = array();

			$results = array();
			$resultsrel = array();

			$q1 = "SELECT c.id as cid, e.id as eid, e.entity as label FROM contents as c, contents_entities as ce, entities as e, contents_entities as ce2, entities as e2 WHERE c." . $researchidcondition . "  AND ce.content_id=c.id AND e.id=ce.entity_id AND e.entity_type_id=1 AND c.id = ce2.content_id AND UCASE(e2.entity) LIKE '" . strtoupper( str_replace("'", '', $topic) ) . "'  AND  ce2.entity_id=e2.id  ";


			//echo($q1 . "\n");

			$r1 = $pdo->query( $q1 );
			if($r1){
				foreach ($r1 as $c) {
					
					//echo(".");

					$o = new \stdClass();
					$o->id = $c["eid"];
					$o->label = $c["label"];
					$o->weight = 1;
					$o->cid = [ $c["cid"] ];

					$foundnode = false;
					for($kk = 0 ; $kk<count($nodes) && !$foundnode; $kk++){
						if($nodes[$kk]->label==$o->label){
							$foundnode = true;
							$nodes[$kk]->weight = $nodes[$kk]->weight + 1;
							if($nodes[$kk]->weight>$maxweight){
								$maxweight = $nodes[$kk]->weight;
							}
							if( !in_array( $c["cid"] , $nodes[$kk]->cid ) ){
								$nodes[$kk]->cid[] = $c["cid"];
							}
						}
					}

					if(!$foundnode){
						$nodes[] = $o;
					}

				}
				$r1->closeCursor();

				//echo("\n");

				for($i=0; $i<count($nodes);$i++){
					for($j=$i+1; $j<count($nodes);$j++){
						$intersect = array_intersect( $nodes[$i]->cid,$nodes[$j]->cid );
						if(  count(  $intersect  )!=0 ){
							$oo = new \stdClass();
							$oo->source = $nodes[$i]->label;
							$oo->target = $nodes[$j]->label;
							$oo->weight = count(  $intersect  );
							$links[] = $oo;
						}
					}
					unset($nodes[$i]->cid);			
				}
			}

			//print_r($nodes);

			$tfn = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $topic);
			$tfn = mb_ereg_replace("([\.]{2,})", '', $tfn);

			$fname = 'files/' . date('Ymd') . '-1topic-' . $tfn . '-nodes-' . implode("_", $researcharray) . ".json";
			if(file_exists($fname)){
				unlink($fname);
			}
			$fp = fopen( $fname , 'w');
			fwrite($fp, json_encode($nodes));
			fclose($fp);

			$fname = 'files/' . date('Ymd') . '-1topic-' . $tfn . '-links-' . implode("_", $researcharray) . ".json";
			if(file_exists($fname)){
				unlink($fname);
			}
			$fp = fopen( $fname , 'w');
			fwrite($fp, json_encode($links));
			fclose($fp);


		} else {
echo '

ERROR!

with the 1topic command you need to specify
the [topic] parameters, as a single character sting
without any spaces, which can include SQL LIKEE
wildcards, such as % and ?.

';
		}


	}





	else if($argv[1]=="exportresearch"){

		if( isset($argv[2])  ){

			$res = $argv[2];

			$researchidcondition = "research_id IN ( " . $res . " ) ";

			$csvfile = "";
			$csvfile = $csvfile . "\n\n-------------------------------------\n";
			$csvfile = $csvfile . "Exporting research id: " . $res;
			$csvfile = $csvfile . "\n";

			
			$q1 = "SELECT name FROM researches WHERE id=" . $res;
			$r1 = $pdo->query( $q1 );
			if($r1){
				foreach ($r1 as $c) {
					
					$csvfile = $csvfile . "Research name: " . $c["name"];
					$csvfile = $csvfile . "\n";
					
					/*
					$o = new \stdClass();
					$o->id = $c["eid"];
					$o->label = $c["label"];
					$o->weight = 1;
					$o->cid = [ $c["cid"] ];
					*/

				}
				$r1->closeCursor();
			}


			$q1 = 'SELECT content,lat,lng,language FROM research_elements WHERE research_id=' . $res;
			$r1 = $pdo->query( $q1 );
			if($r1){
				foreach ($r1 as $r) {
					
					$csvfile = $csvfile . "Research element: " . $r["content"] . ",(" . $r["lat"] . "," . $r["lng"] . ")," . $r["language"];
					$csvfile = $csvfile . "\n";
					
					/*
					$o = new \stdClass();
					$o->id = $c["eid"];
					$o->label = $c["label"];
					$o->weight = 1;
					$o->cid = [ $c["cid"] ];
					*/

				}
				$r1->closeCursor();
			}



			$csvfile = $csvfile . "\n\n";
			$csvfile = $csvfile . "CONTENT";
			$csvfile = $csvfile . "\nid,subject_id,link,content,created_at,language,favorite_count,share_count,lat,lng,comfort,energy\n";
			$q1 = 'SELECT id,subject_id,link,content,created_at,language,favorite_count,retweet_count,lat,lng,comfort,energy FROM contents WHERE research_id=' . $res;
			$r1 = $pdo->query( $q1 );
			if($r1){
				foreach ($r1 as $r) {
					
					$csvfile = $csvfile . $r["id"] . "," . $r["subject_id"] . "," . $r["link"] . "," . str_replace(",", " ", $r["content"] ) . "," . $r["created_at"] . "," . $r["language"] . "," . $r["favorite_count"] . "," . $r["retweet_count"] . ",(" . $r["lat"] . "," . $r["lng"] . ")," . $r["comfort"] . "," . $r["energy"];
					$csvfile = $csvfile . "\n";
				}
				$r1->closeCursor();
			}




			$csvfile = $csvfile . "\n\n";
			$csvfile = $csvfile . "ENTITIES";
			$csvfile = $csvfile . "\ncontent id,entity type,entity\n";
			$q1 = 'SELECT ce.content_id as content_id , e.entity_type_id as entity_type, e.entity as entity FROM contents_entities ce, entities e WHERE ce.research_id=' . $res . ' AND ce.entity_id=e.id';
			$r1 = $pdo->query( $q1 );
			if($r1){
				foreach ($r1 as $r) {
					$csvfile = $csvfile . $r["content_id"] . "," . $r["entity_type"] . "," . $r["entity"];
					$csvfile = $csvfile . "\n";
				}
				$r1->closeCursor();
			}


			$csvfile = $csvfile . "\n\n";
			$csvfile = $csvfile . "EMOTIONS";
			$csvfile = $csvfile . "\ncontent id,emotion\n";
			$q1 = 'SELECT e.content_id as content_id, et.label as emotion FROM emotions e, emotion_types et WHERE e.research_id=' . $res . ' AND e.emotion_type_id=et.id';
			$r1 = $pdo->query( $q1 );
			if($r1){
				foreach ($r1 as $r) {
					$csvfile = $csvfile . $r["content_id"] . "," . $r["emotion"];
					$csvfile = $csvfile . "\n";
				}
				$r1->closeCursor();
			}


			$csvfile = $csvfile . "\n\n";
			$csvfile = $csvfile . "RELATIONS";
			$csvfile = $csvfile . "\nsubject 1 id, subject 2 id,weight\n";
			$q1 = 'SELECT subject_1_id, subject_2_id, c FROM relations r WHERE r.research_id=' . $res ;
			$r1 = $pdo->query( $q1 );
			if($r1){
				foreach ($r1 as $r) {
					$csvfile = $csvfile . $r["subject_1_id"] . "," . $r["subject_2_id"] . "," . $r["c"];
					$csvfile = $csvfile . "\n";
				}
				$r1->closeCursor();
			}


			$csvfile = $csvfile . "\n\n";
			$csvfile = $csvfile . "SUBJECTS";
			$csvfile = $csvfile . "\nid,location,followers,friends,listed\n";
			$q1 = 'SELECT  id,location, followers_count, friends_count, listed_count, language,profile_url,profile_image_url FROM subjects s WHERE s.research_id=' . $res;
			$r1 = $pdo->query( $q1 );
			if($r1){
				foreach ($r1 as $r) {
					$csvfile = $csvfile . $r["id"] . "," . str_replace(",", " ", $r["location"] ). "," . $r["followers_count"] . "," . $r["friends_count"] . "," . $r["listed_count"];
					$csvfile = $csvfile . "\n";
				}
				$r1->closeCursor();
			}



			$tfn = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $res);
			$tfn = mb_ereg_replace("([\.]{2,})", '', $tfn);

			$fname = 'files/' . date('Ymd') . '-researchexport-' . $tfn . '-' . $res . ".csv";
			if(file_exists($fname)){
				unlink($fname);
			}
			$fp = fopen( $fname , 'w');
			fwrite($fp, $csvfile);
			fclose($fp);

		} else {
echo '

ERROR!

with the exportresearch command you need to specify
the [id research] parameters, which is the
id of the research that you wish to export

';
		}


	}








	else {
echo '

Command not recognized.


';
	}

    


 
} else {

echo '
extractData.php [dataset] [researches]

where [dataset] is one of:

timeline                     - timeline of date and number of contents
sentimenttimeline            - timeline of date and positive,negative,neutral sentiment
tag                          - list of tags and weight
tagnetwork                   - network of tags relations
tagshierarchy [x] [y]        - top [x] tags and for each the [y] subtags
topusers                     - top users lists
userrelations                - network of users relations
1topic [topic]               - extract 1 [topic]: a word with SQL LIKE wildcards
exportresearch [id research] - export an entire research (does not need any additional researches)

and [researches] is a comma separated list of the research IDs you want to generate data for
(the comma separated list should have no spaces)

';

}



?>