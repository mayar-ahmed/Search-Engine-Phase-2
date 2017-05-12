<?php
include_once 'PorterStemmer.php';
class relevanceRank{
    private $connection;
    public function __contruct(){
	}
    public function db_connect()
    {
        
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname="searchengine2";

        // change these parameters based on your password and username
        $conn = mysqli_connect($servername , $username , $password , $dbname);
        $this ->connection = $conn;


        if (mysqli_connect_errno()){
        die ("Database connection failed: " . mysqli_connect_error() .
            "(" . mysqli_connect_errno() . ")" );
        }
        
    }


	public function rank($Docs,$Query,$pq){

        $sql = "SELECT count(1) FROM documents";
        $result = mysqli_query($this->connection, $sql);
        $row = mysqli_fetch_array($result);
        $N = $row[0];

		$processed=array();
        $pqResults=array();
        $docsInfo = array();
        if(is_null($Docs))
          {  
            return array();
          }

        if($pq)
            {
                /////////////////////// Add Page Rank for phrase queries///////////////////////////
                return $Docs;
            }
		while ($row = mysqli_fetch_assoc($Docs))
    	{   
    		$factor=1;$idf=0;$q;
            $idf = log($N/(1+$row['df']));
    		foreach($Query as $k => $v)
    		{
    			if($k == $row['term']){
    				$factor = $row['tf']*$idf * $row['tf']*$idf;
                    $q = $k;
                    if(!isset($qVector[$k]))
                    {
                        $qVector[$k] = $v * $idf;
                    }
    				break;
    			}
                else
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
    		
            if(!isset($docsInfo[$row['url']]))
            {
                $docsInfo[$row['url']]['title']= $row['title'];
                $docsInfo[$row['url']]['text']= $row['text'];
                $docsInfo[$row['url']]['rank']= $row['rank'];
            }
    		//$processed[] = array('url' => $row['url'], 'rank' =>$factor*$row['tf']*$idf);
            //Location: 0.3 - 0.6 - 0.9 / 0.1 - 0.4 - 0.6 
            //Processed[url] = {q1->score, q2->score, ..}
            if( (isset($processed[$row['url']])) && (isset($processed[$row['url']][$q])) )
            {
                //$processed[$row['url']][$q] += $factor*$row['tf']*$idf*$idf*($row['location']+1);
                $processed[$row['url']][$q] += $factor*$row['tf']*$idf;

            }
            else
            {   
                //$processed[$row['url']][$q] = $factor*$row['tf']*$idf*$idf*($row['location']+1);
                $processed[$row['url']][$q] = $factor*$row['tf']*$idf;
            }
        }
        $ret = array();
        foreach ($processed as $url => $value) {
            //$n = 0;
            $ret[$url]['title'] = $docsInfo[$url]['title'];
            $ret[$url]['text'] = $docsInfo[$url]['text'];
            $ret[$url]['rank'] = $docsInfo[$url]['rank'];
            foreach ($value as $q => $score) {
                if(!isset($ret[$url]['score']))
                    $ret[$url]['score'] = $score;
                else
                    $ret[$url]['score']+=$score;
                //$n += $score * $score;
            }
           // $n = sqrt($n);
            //$ret[$url]/=$n;

        }
            foreach ($ret as $url => $rank) {
            //$ret[$url] = $rank*count($processed[$url]);
                $c = count($processed[$url]);
                $ret[$url]['score'] = $rank['score']*exp($c*$c)*$c*$c;
            }

        //arsort($ret);
            ///////////////////////////Add Page Rank for normal queries//////////////////////////////////
            $r = array();
        foreach ($ret as $key => $row)
        {
            $r[$key] = $row['score'];
        }
        array_multisort($r, SORT_DESC, $ret);

        return $ret;
/*
        foreach ($ret as $url => $rank) {
            echo($url);
            echo "<br>";
            echo (count($processed[$url]));
            echo "<br>";
            foreach ($processed[$url] as $key => $value) {
                
                echo "$key : $value";
                echo "<br>";
            }
            echo "$rank" . "<br>";
            echo "-------------------------------- " . "<br>";
        }
        */

    //return $this->vectorSpaceModel($processed,$Query,$qVector);


        /*
        $r = array();
        foreach ($ranked as $key => $row)
        {
            $r[$key] = $row['rank'];
        }
        array_multisort($r, SORT_DESC, $ranked);
*/  

       // arsort($ranked);
      
        
          
	}

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
}
?>
