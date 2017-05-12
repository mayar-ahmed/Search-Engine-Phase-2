<?php
include_once 'classes/PorterStemmer.php';
 function db_connect()
    {
        
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname="se2";

        // change these parameters based on your password and username
        $conn = mysqli_connect($servername , $username , $password , $dbname);
        


        if (mysqli_connect_errno()){
        die ("Database connection failed: " . mysqli_connect_error() .
            "(" . mysqli_connect_errno() . ")" );
        }
        return $conn;
    }
function snippet($query,$document,$pq){
	$conn=db_connect();
	$substrs=$query;
	$snippets=array();
	$size=0;
	if(!$pq){
		foreach ($substrs as $ss)
		{
				/*check if word exists in document */
				$firstInd=stripos($document,$ss);
				if($firstInd!=false)
				{
					/*get some text around the word*/
					$start=$firstInd;
					if($firstInd-20>0)
						$start=$firstInd-20;
					$snippets[$size]=substr($document,$start,100);
					$size++;
					/*check if 3 snippets */
					if($size==2)
						return $snippets;
				}
				else{
					/*get the words of the same stem*/
					$stem=PorterStemmer::Stem($ss);
					
					$words=mysqli_query($conn, "select term from terms where stem=$stem");
					
					if(!$words)
						continue;
					while($word=mysqli_fetch_assoc($words))
					{
						
						/*check if word exists in document */
						$firstInd=stripos($document,$word);
						if($firstInd!=false)
						{	
							/*get some text around the word*/
							$start=$firstInd;
							if($firstInd-20>0)
							$start=$firstInd-20;
							$snippets[$size]=substr($document,$start,100);
							$size++;
							/*check if 3 snippets */
							if($size==2)
								return $snippets;
							if(count>1)
								break; /*to check other words in the query*/
						}
					}
				}
		}
	}
	else{
		
			$firstInd=stripos($document,trim($substrs,'"'));
				if($firstInd!=false)
				{
					/*get some text around the word*/
					$start=$firstInd;
					if($firstInd-20>0)
						$start=$firstInd-20;
					
					$snippets[$size]=substr($document,$start,100);
					$size++;
				}
	}
		return $snippets;
}
?>
