<?php
include_once 'PorterStemmer.php';
class queryProcessor
{
    private $connection;
    private $stopWords;
    private $queryStems;
    private $queryTokens;
    function __construct($c)
    {
        $this->connection = $c;
        $this->stopWords= array();
        $this->loadStopWords();
    }
    public function loadStopWords()
    {
        $query = "SELECT stop_word from stop_words";
        $result = mysqli_query($this->connection , $query);
        while ($row = mysqli_fetch_array($result)) {
            $this->stopWords[]= $row[0];
        }
    }
    public function process($query)
    {
        $docs=null;
        $phrase =preg_match('/^(["\']).*\1$/m', $query);
        $tokens = $this->tokenizeQuery($query); //tokenize string
        if(!$phrase) //not surrounded by quotes
        {
            $rm=$this->removeStopWords($tokens); //remove stop words;
            $this->queryTokens =$rm;
            $stems=$this->getStems($rm); //get list of stems in query;
            $this->queryStems =$stems;
            $docs=$this->getDocuments($stems);
        }
        else{
            $rm=$this->removeStopWords($tokens); //get tokens withot stop words
            $stop= $this->getStopWords($tokens); //get stop words to search for them
            //get docs which contain stop words and terms from daabase
            $docs= $this->getPhraseDocuments($query, $rm,$stop);
        }
        //result set from database whether it's a phrase or normal query;
        return $docs;
    }
    public function countWords ($query)
    {
        $query=strtolower($query);
        $words = array_count_values(str_word_count($query, 1));
        return $words;
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
        $in=$this->joinArray($stems);
        $select = "SELECT a.term, a.stem,a.df,b.tf,b.location,c.url,c.title,c.rank FROM terms a JOIN term_doc b ON a.term = b.term JOIN documents c ON b.doc_id = c.id where a.stem IN ($in)";
        $result=mysqli_query($this->connection,$select,MYSQLI_USE_RESULT); //to not buffer result set before usage
        //echo mysqli_error ( $this->connection ). "<br>";
        if(!$result)
            echo "error fetching results";
        else{
            echo "fetched result successfully";
          return $result;
        }
    }
    public function joinArray($arr)
    {
        $arr_sql=[];
        foreach($arr as $s) {
            $arr_sql[] = '\''.mysqli_escape_string($this->connection,$s).'\'';
        }
        $in = join(',', $arr_sql);
        return $in;
    }
    public function getPhraseDocuments($query,$tokens, $stopwords)
    {
        $tokens_sql = $this->joinArray($tokens); //terms after removing stop words
        $stop_sql = $this->joinArray($stopwords); //stop words
        $terms = count($tokens); //number of terms
        $stops = count($stopwords); //number of stop words
       // $query =mysqli_escape_string($this->connection,$query); //original query
        $q = (trim($query,'"'));
        $sql = "Select url from documents where id in (\n"
            . "\n"
            . "SELECT documents.id from (\n"
            . " ( SELECT doc_id as id FROM stop_doc WHERE stop_word IN ($stop_sql) GROUP BY doc_id HAVING Count(doc_id) = {$stops})\n"
            . " UNION ALL \n"
            . " ( SELECT doc_id as id FROM term_doc WHERE term IN ($tokens_sql) GROUP BY doc_id HAVING Count(doc_id) = {$terms} )\n"
            . ") AS documents GROUP BY id HAVING count(*) >= 2\n"
            . ") \n"
            . "and content like '%{$q}%'";
        echo "<br>".$sql;
        $result = mysqli_query($this->connection, $sql , MYSQLI_USE_RESULT);
        if(!$result)
            echo "error fetching results";
        else{
            echo "fetched result successfully";
            return $result;
        }
    }
    public function getQueryStems()
    {
        return $this->queryStems;
    }
    public function getQueryTokens()
    {
        return $this->queryTokens;
    }
}