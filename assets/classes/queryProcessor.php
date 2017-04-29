<?php
include_once 'PorterStemmer.php';

class queryProcessor
{
    private $connection;
    private $stopWords;

    function __construct($c)
    {
        $this->connection = $c;
        $this->stopWords= array();
        $this->loadStopWords();


    }

    public function loadStopWords()
    {
        $starttime = microtime(true);
        $count =0;

        $query = "SELECT stop_word from stop_words";
        $result = mysqli_query($this->connection , $query);
        while ($row = mysqli_fetch_array($result)) {
            $this->stopWords[]= $row[0];
            //  echo end($stopWords) . "</br>";
            $count=$count+1;
        }
        $endtime = microtime(true);
        $duration = $endtime - $starttime; //calculates total time taken
        // echo $duration . " " . $count;

    }

    public function process($query)
    {
        $docs=null;
        $phrase =preg_match('/^(["\']).*\1$/m', $query);
        $tokens = $this->tokenizeQuery($query); //tokenize string
        if(!$phrase) //not surrounded by quotes
        {
            $rm=$this->removeStopWords($tokens); //remove stop words;
            $stems=$this->getStems($rm); //get list of stems in query;
            $docs=$this->getDocuments($stems);
        }
        else{

            $rm=$this->removeStopWords($tokens); //get tokens withot stop words
            $stop= $this->getStopWords($tokens); //get stop words to search for them
            //get docs which contain stop words and terms from daabase
            //remove docs that don't contain the exact query
            $docs=null;
        }

        //result set from database whether it's a phrase or normal query;
        return $docs;
    }



    public function tokenizeQuery($q)
    {


        $q = preg_replace("/\\.\\s+/", " ",$q); //remove . followed by space with space

        $q= str_replace([".", "'"],"",$q); //remove . not followed by space and '
        $q =strtolower($q);


        $q= preg_split("/[^a-zA-Z0-9-]/", $q); //split on anything that's not a alphanumeric or hyphen

        $tokens= [];
        foreach ($q as $t)
        {
            if((trim($t) == "") or is_numeric($t)) //if word is empty or numeric,skip
                continue;

            if(strpos($t, '-') !== false) //if it contains hyphen
            {
                //contains hyphens
                $t1 = explode('-',$t); //split once
                $t2=str_replace("-","",$t); //concatinate once
                $tokens[]=$t2;

                foreach($t1 as $hy)
                    $tokens[]=$hy;
            }
            else{
                $tokens[] = $t;
            }
        }

        print_r($tokens);
        return $tokens;

    }

    public function removeStopWords($tokens){

            $difference= array_diff($tokens ,$this->stopWords);
            echo "<br>";
            print_r($difference);
            return $difference;


    }

    public function getStopWords($tokens)
    {
        $intersection = array_intersect($tokens, $this->stopWords); //get intersection between tokens and stop words
        echo "<br>";
        print_r($intersection);
        return $intersection;
    }
    public function getStems($tokens) //after removing stop words
    {
        $stems=[];
        echo "<br>";
        foreach ($tokens as $t){
            $s = PorterStemmer::Stem($t);
            $stems[]=$s;
        }


        print_r($stems);
        return $stems;

    }

    public function getDocuments($stems)
    {
        $documents=[];

        $stems_sql=[];
        foreach($stems as $s) {

            $stems_sql[] = '\''.mysqli_escape_string($this->connection,$s).'\'';
        }


        $in = join(',', $stems_sql);
        $select = "SELECT a.term,a.df,b.tf,b.location,c.url FROM terms a JOIN term_doc b ON a.term = b.term JOIN documents c ON b.doc_id = c.id where a.stem IN ($in)";
        echo "<br>". $select;

        $result=mysqli_query($this->connection,$select,MYSQLI_USE_RESULT); //to not buffer result set before usage
        echo "hi";


        echo mysqli_error ( $this->connection ). "<br>";
        if(!$result)
            echo "error fetching results";
        else{
            echo "fetched result successfully";
          return $result;

        }




    }



}