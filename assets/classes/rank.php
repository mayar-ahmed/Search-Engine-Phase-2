<?php
include_once 'PorterStemmer.php';
class rank{
    private $connection;
    public function __contruct(){
	}
    public function db_connect()
    {
        
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname="searchengine2";

        $conn = mysqli_connect($servername , $username , $password , $dbname);
        $this ->connection = $conn;


        if (mysqli_connect_errno()){
        die ("Database connection failed: " . mysqli_connect_error() .
            "(" . mysqli_connect_errno() . ")" );
        }
        
    }


	public function rankDocuments($Docs,$Query,$pq,$stops){
        //Get number of documents from database
        $sql = "SELECT count(1) FROM documents";
        $result = mysqli_query($this->connection, $sql);
        $row = mysqli_fetch_array($result);
        $N = $row[0];

		$processed=array();
        $docsInfo = array();
        if(is_null($Docs))
          {  
            return array();
          }
        //If phrase query of stop words only, Rank using page rank only i.e. No Relevance Rank  
       if($pq && $stops)
        {
            echo "Here" . "<br>";
            $r = array();
            foreach ($Docs as $key => $row)
            {
                $r[$key] = $row['rank'];
            }
            array_multisort($r, SORT_DESC, $Docs);

            return $Docs;
        }
        if(!$Docs)
            return;
        //Iterate on fetched results    
		while ($row = mysqli_fetch_assoc($Docs))
    	{   
    		$factor=1;$idf=0;$q;
            $idf = log($N/(1+$row['df']));
            //calculate wighting factors for each query term
    		foreach($Query as $k => $v)
    		{
    			if($k == $row['term']){
    				$factor = $row['tf']*$idf * $row['tf']*$idf;
                    $q = $k;
    				break;
    			}
                else if(!$pq)
                {
                     $s = PorterStemmer::Stem($k);
                     if($s == $row['stem'])
                     {
                        $q = $k;
                        $factor = 1;
                        break;
                     }
                }
    		}  
    		//Get documents' info to be accessed by their URLs
            if(!isset($docsInfo[$row['url']]))
            {
                $docsInfo[$row['url']]['title']= $row['title'];
                $docsInfo[$row['url']]['text']= $row['text'];
                $docsInfo[$row['url']]['rank']= $row['rank'];
            }

            //Calculating Score for each query term
            //Location: 0.75 - 0.5 - 0.25
            //Processed[url] = {q1->score, q2->score, ..}
            if( (isset($processed[$row['url']])) && (isset($processed[$row['url']][$q])) )
            {
                $processed[$row['url']][$q] += $factor*$row['tf']*$idf* ( (3 - $row['location'])*(3 - $row['location'])* (3 - $row['location'])/10.0);

            }
            else
            {   
                $processed[$row['url']][$q] = $factor*$row['tf']*$idf* ( (3 - $row['location'])*(3 - $row['location'])* (3 - $row['location'])/10.0);
            }
        }
        //Get total score for each document 
        $rankedDocs = array();
        foreach ($processed as $url => $value) {
            //$n = 0;
            $rankedDocs[$url]['title'] = $docsInfo[$url]['title'];
            $rankedDocs[$url]['text'] = $docsInfo[$url]['text'];
            $rankedDocs[$url]['rank'] = $docsInfo[$url]['rank'];
            foreach ($value as $q => $score) {
                if(!isset($rankedDocs[$url]['score']))
                    $rankedDocs[$url]['score'] = $score;
                else
                    $rankedDocs[$url]['score']+=$score;
            }
			//$rankedDocs[$url]['score'] = $rankedDocs[$url]['score'] * $rankedDocs[$url]['rank']; 
        }
        //For phrase queries, number of query terms has no weight (Document already has all query terms)
        if(!$pq)
        {
            foreach ($rankedDocs as $url => $rank) {
                $c = count($processed[$url]);
                $rankedDocs[$url]['score'] = $rank['score']*exp($c*$c)*$c*$c;
            }
        }
        
        //Sort Documents Descendingly according to their score
        $r = array();
        foreach ($rankedDocs as $key => $row)
        {
            $r[$key] = $row['score'];
        }
        array_multisort($r, SORT_DESC, $rankedDocs);

        return $rankedDocs;
	}

/*
    public function calculateDocsNorm($docs){
        $norms = array();
        $urls_sql=[];
        foreach($docs as $url => $score) {
            $urls_sql[] = '\''.mysqli_escape_string($this->connection,$url).'\'';
        }

        $urls = join(',', $urls_sql);
        $sql_query = "SELECT d.url,td.tf,t.df FROM terms t join term_doc td on t.term = td.term join documents d on d.id = td.doc_id where d.url in($urls) ";

        $result = mysqli_query($this->connection,$sql_query,MYSQLI_USE_RESULT); //to not buffer result set before usage

        echo mysqli_error ( $this->connection ). "<br>";
        if(!$result)
            echo "error fetching results in ranking ";
        else{
            echo "fetched result successfully in ranking";
        }

            while ($row =mysqli_fetch_assoc($result)) {
                if(!isset($norms[$row['url']]))
                {

                    $norms[$row['url']] = $row['tf']*(6009/log(1+$row['df'])) * $row['tf']*(6009/log(1+$row['df']));

                } 
                else{
                    $norms[$row['url']] += $row['tf']*(6009/log(1+$row['df'])) * $row['tf']*(6009/log(1+$row['df']));                    
                }
            }

            foreach($norms as $k => $v)
            {
                $v = sqrt($v);
            }
          return $norms;

        }


    public function vectorSpaceModel($processed,$Query,$qVector){
        
        $ranked = array();
        $qNorm = 0; $norms = array();        
        //Query Norm
        foreach($qVector as $k => $v)
        {
            $qNorm += ($v*$v);
        }
        $qNorm = sqrt($qNorm);

        //DocNorm 
        $norms = $this->calculateDocsNorm($processed);
        
        //For Each Document 
        foreach ($processed as $url => $data) {

            //Dot Product
            foreach($data as $q => $score){
                if(!isset($ranked[$url]))
                {
                    $ranked[$url] = $Query[$q]*$score;
                }
                else
                {
                    $ranked[$url] += $Query[$q]*$score;   
                }

                $ranked[$url] = $ranked[$url] / ($norms[$url]*$qNorm);
           }

        }

         arsort($ranked);
         return $ranked;
    }
    */
}
?>
